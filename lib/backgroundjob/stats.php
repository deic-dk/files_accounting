<?php

namespace OCA\Files_Accounting;

use \OCP\DB;
use \OCP\User;
use \OCP\Config;
use \OCA\Files_Accounting\ActivityHooks;
use \OC_Preferences;
use Mail;
use \OCP\Defaults;

require_once  __DIR__ . '/../invoicepdf.php';

class Stats extends \OC\BackgroundJob\TimedJob {

	private $billingCurrency;
	private $billingDay;
	private $netDays;
	private $timestamp;
	private $dueTimestamp;
	private $billingMonth;
	private $billingMonthName;
	private $billingYear;

	public function __construct() {
		$this->billingCurrency = \OCA\Files_Accounting\Storage_Lib::getBillingCurrency();
		$this->billingDay = \OCA\Files_Accounting\Storage_Lib::getBillingDayOfMonth();
		$this->netDays = \OCA\Files_Accounting\Storage_Lib::getBillingNetDays();
		$this->timestamp = time();
		$this->dueTimestamp = $this->timestamp + 60*60*24*$this->netDays;
		$this->billingMonth = (string)((int)date("m", $this->timestamp));
		$this->billingYear = date('Y', $this->timestamp);
		$this->billingMonthName = date('F', strtotime("2000-$this->billingMonth-01"));

		$this->setInterval(6 * 60 * 60);
	}

	protected function run($argument) {
		$file_update = $this->updateAndBill();
	}

	private function updateAndBill() {
		$users = \OC_User::getUsers();
		foreach ($users as $user) {
			// Add a line to usage-201*.txt locally.
			// logDailyUsage checks if the line already exists and bails if so.
			\OCP\Util::writeLog('Files_Accounting', 'Logging usage of user: '.$user, \OCP\Util::WARN);
			\OCA\Files_Accounting\Storage_Lib::logDailyUsage($user);
			// Log group folders usage. This will not be billed to group members, but, collected, to the group owner
			if(\OCP\App::isEnabled('user_group_admin')){
				require_once 'apps/user_group_admin/lib/util.php';
				$memberGroups = \OC_User_Group_Admin_Util::getUserGroups($user, true, true, true);
				if(!empty($memberGroups)){
					foreach($memberGroups as $group){
						if(!empty($group['user_freequota'])){
							\OCA\Files_Accounting\Storage_Lib::logDailyUsage($user, false, $group['gid']);
						}
					}
				}
			}
			// Update DB on master and bill
			if(\OCP\App::isEnabled('files_sharding') && !\OCA\FilesSharding\Lib::onServerForUser($user)){
				\OC_Log::write('files_accounting',"Not billing on non-home server for ".$user, \OC_Log::WARN);
				continue;
			}
			$this->updateAndBillUser($user);
		}
	}

	private function updateAndBillUser($user){
		if(!\OC_User::userExists($user)){
			\OC_Log::write('files_accounting',"ERROR: Cannot bill non-existing user. ".$user, \OC_Log::ERROR);
			return;
		}
		// First close any expired gifts
		\OCA\Files_Accounting\Storage_Lib::expireStorageGifts($user);
		$personalStorage = \OCA\Files_Accounting\Storage_Lib::personalStorage($user);
		$freequotaBytes = (float) \OCP\Util::computerFileSize($personalStorage['freequota']);
		if(isset($personalStorage['freequota'])) {
			$freequota_exceeded = \OC_Preferences::getValue($user, 'files_accounting', 'freequotaexceeded', false);
			// bytes to gigabytes
			if($personalStorage['files_usage'] > $freequotaBytes && $freequotaBytes > 0){
				// Usage above free and user has not yet been notified
				if(!$freequota_exceeded){
					\OCA\Files_Accounting\Storage_Lib::addQuotaExceededNotification($user, $personalStorage['freequota']);
					// Make sure user is only notified once
					\OC_Preferences::setValue($user, 'files_accounting', 'freequotaexceeded', true);
				}
			}
			else{
				// Usage back below free, clear freequotaexceeded
				\OC_Preferences::deleteKey($user, 'files_accounting', 'freequotaexceeded');
			}
		}
		
		// Only run billing on the billing day
		if(date("j", $this->timestamp) != $this->billingDay){
			\OCP\Util::writeLog('Files_Accounting', 'Not billing user: '.$user.' today', \OCP\Util::WARN);
			return;
		}
		
		$referenceHash = md5($user.$this->billingYear.$this->billingMonth);
		$reference_id = $this->billingYear.'-'.$this->billingMonth.'-'.substr($referenceHash, 0, 8);
		
		// A user who has a not expired  preapproval key is charged.
		$hasPreapprovalKey = \OCA\Files_Accounting\Storage_Lib::getPreapprovalKey($user, $this->billingMonth,
				$this->billingYear);
		if($hasPreapprovalKey){
			ActivityHooks::automaticPaymentComplete($user,
			array('month'=>$this->billingMonthName, 'year'=>$this->billingYear, 'item_number'=>$reference_id));
		}

		// Check if already logged and billed monthly
		$path = \OCA\Files_Accounting\Storage_Lib::getInvoiceDir($user);
		if(!file_exists($path)){
			mkdir($path, 0777, false);
		}
		$files = scandir($path);
		$currentMonthFiles = preg_grep("/^".$this->billingYear."-".$this->billingMonth."-.*\.pdf$/", $files);
		if(!empty($currentMonthFiles)){
			\OCP\Util::writeLog('Files_Accounting', 'Already billed user: '.$user.' for '.$this->billingMonthName, \OCP\Util::WARN);
			return;
		}

		// Log monthly average to DB on master
		$charge = \OCA\Files_Accounting\Storage_Lib::getChargeForUserServers($user);
		$monthlyUsageAverage = \OCA\Files_Accounting\Storage_Lib::currentUsageAverage(
				$user, $this->billingYear, $this->billingMonth, $this->timestamp);

		$filesHome = (float)$monthlyUsageAverage['home']['files_usage'];
		$filesBackup = (float)$monthlyUsageAverage['backup']['files_usage'];
		$trash = (float)$monthlyUsageAverage['home']['trash_usage'];
		
		// kilobytes to gigabytes
		$homeGB = round($filesHome/pow(1024, 3), 3);
		$backupGB = round($filesBackup/pow(1024, 3), 3);
		$trashGB = round($trash/pow(1024, 3), 3);
		
		$remainingFreequotaBytes = $freequotaBytes;
		if($freequotaBytes > 0 && $filesHome+$trash >= $freequotaBytes){
			$homeAndTrashBilledGB = round(($filesHome+$trash-$freequotaBytes)/pow(1024, 3), 3);
			$homeDue = round($homeAndTrashBilledGB*$charge['charge_home'], 2);
			$backupDue = round($backupGB*$charge['charge_backup'], 2);
		}
		elseif($freequotaBytes > 0 && $filesHome+$trash < $freequotaBytes){
			$homeDue = 0;
			$remainingFreequotaBytes = $freequotaBytes - ($filesHome + $trash);
			if($filesBackup > $remainingFreequotaBytes){
				$backupBilledGB = round(($filesBackup-$remainingFreequotaBytes)/pow(1024, 3), 3);
				$backupDue = round($backupBilledGB*$charge['charge_backup'], 2);
			}
			else{
				$backupDue = 0;
			}
		}
		else{
			$homeDue = round(($homeGB+$trashGB)*$charge['charge_home'], 2);
			$backupDue = round($backupGB*$charge['charge_backup'], 2);
		}
		
		$sumDue = $homeDue + $backupDue;
		
		$prePaid = \OCA\Files_Accounting\Storage_Lib::getPrePaid($user);
		if($prePaid>$sumDue){
			$totalSumDue = 0;
			$newPrePaid = $prePaid - $sumDue;
		}
		elseif($prePaid>0){
			$newPrePaid = 0;
			$totalSumDue = $sumDue - $prePaid;
		}
		else{
			$totalSumDue = $sumDue;
		}
		if(isset($newPrePaid)){
			\OCA\Files_Accounting\Storage_Lib::setPrePaid($user, $newPrePaid);
		}
		
		\OCP\Util::writeLog('Files_Accounting', 'Personal due of user: '.$user.': '.
				$filesHome.': '.$homeGB.': '.$charge['charge_home'].
				' : '.$backupGB.' : '.$trashGB.': '.$homeDue.' : '.$backupDue.': '.$totalSumDue.' : '.$totalSumDue, \OCP\Util::WARN);
		
		if($this->billingMonth==$monthlyUsageAverage['home']['first_month']){
			if($this->billingDay<$monthlyUsageAverage['home']['first_day']){
				// Not through a full month yet - reduce bill
				$homeDue = round($monthlyUsageAverage['home']['days']/28*$homeDue);
				$backupDue = round($monthlyUsageAverage['home']['days']/28*$backupDue);
				$totalSumDue = round($monthlyUsageAverage['home']['days']/28*$totalSumDue);
			}
		}

		// Get current collected group usage for owned groups
		// TODO: calculate averages here too...
		if(\OCP\App::isEnabled('user_group_admin')){
			$ownerGroups = \OC_User_Group_Admin_Util::getOwnerGroups($user, true);
			$groupUsagesGB = array();
			$groupCharges = array();
			if(!empty($ownerGroups)){
				foreach($ownerGroups as $group){
					if(!empty($group['user_freequota'])){
						$groupUsage = \OC_User_Group_Admin_Util::getGroupUsage($group['gid']);
						if(!empty($groupUsage)){
							$groupUsageGB = round($groupUsage/pow(1024, 3), 3);
							$groupUsagesGB[$group['gid']] = $groupUsageGB;
							$groupChargeArr = \OC_User_Group_Admin_Util::getGroupUsageCharge($group['gid'], $remainingFreequotaBytes);
							$groupCharges[$group['gid']] = floatval($groupChargeArr['charge']);
							$remainingFreequotaBytes = (int) $groupChargeArr['remaining_free_bytes'];
							$totalSumDue += round($groupCharges[$group['gid']], 2);
							\OCP\Util::writeLog('Files_Accounting', 'Usage of group: '.$group['gid'].
									': '.$groupUsage.' : '.$groupUsagesGB[$group['gid']], \OCP\Util::WARN);
						}
					}
				}
			}
		}

		$podsUse = \OCA\Files_Accounting\Storage_Lib::getPodsMonthlyUse($user);
		$totalSumDue += round($podsUse['total_charge'], 2);

		if($totalSumDue==0){
			\OCP\Util::writeLog('Files_Accounting', 'Usage charge 0 for user: '.$user, \OCP\Util::WARN);
			//return; // Nope - Invoices of 0 could be needed by some - they will contain non-zero usage.
		}
		
		// This goes to master
		\OCA\Files_Accounting\Storage_Lib::updateMonth($user,
				$hasPreapprovalKey||$totalSumDue==0?\OCA\Files_Accounting\Storage_Lib::PAYMENT_STATUS_PAID:
				\OCA\Files_Accounting\Storage_Lib::PAYMENT_STATUS_PENDING,
				$this->billingYear, $this->billingMonth, $this->timestamp, $this->dueTimestamp, $homeGB, $backupGB, $trashGB,
				$charge['id_home'], $charge['id_backup'], $charge['url_home'], $charge['url_backup'], $charge['site_home'],
				$charge['site_backup'], $totalSumDue, $reference_id);
		
		// Create invoice and store locally. It can always be recreated from DB on master (storage)
		// and the files "files_accounting/pods/podsusage\_[year]\_[month].txt" on the home silo.
		$filename = $this->invoice($user, $reference_id,
				$homeGB+$trashGB, $backupGB, $totalSumDue,
				$homeDue, $backupDue,
				$charge['site_home'], $charge['site_backup'],
				$groupUsagesGB, $groupCharges, $podsUse);

		if(empty($filename)){
			\OCP\Util::writeLog('Files_Accounting', 'ERROR: could not create invoice for '.$user.', '.$this->billingMonth.', '.$totalSumDue, \OCP\Util::ERROR);
			return;
		}

		// Notify
		ActivityHooks::invoiceCreate($user,
			array('month'=>$this->billingMonthName, 'year'=>$this->billingYear, 'item_number'=>$reference_id,
					'priority'=>($totalSumDue==0?\OCA\UserNotification\Data::PRIORITY_MEDIUM:\OCA\UserNotification\Data::PRIORITY_VERYHIGH)
			));
		
		// TODO: uncomment when this goes into production
		//$this->sendNotificationMail($user, $totalSumDue, $filename, $charge['site_home']);
	}

	public function sendNotificationMail($user, $amount, $filename, $senderName) {
		$userEmail = \OCP\Config::getUserValue($user, 'settings', 'email');
		if(!empty($userEmail)){
			return;
		}
		$realName = User::getDisplayName($user);
		$url = \OCA\Files_Accounting\Storage_Lib::getBillingURL($user);
		$path = \OCA\Files_Accounting\Storage_Lib::getInvoiceDir($user);
		$file = $path . "/" . $filename;
		$senderEmail = \OCA\Files_Accounting\Storage_Lib::getIssuerEmail();
		$subject = "Invoice for ".$this->billingMonthName;
		$message = "Dear ".$realName.",\n\nThe bill for ".$this->billingMonthName." is ".$amount." ".
				$this->billingCurrency.". An invoice is attached.\n".
				(empty($url)?"Please complete payment in your account settings":"To complete payment please visit ".
						$url).".\n\nThank you for using our services.";
		Mail::send($userEmail, $realName, $subject, $message, $senderEmail, $senderName, $file);
	}

	private function invoice($user, $reference, $homeGB, $backupGB, $totalAmountDue,
			$homeAmountDue, $backupAmountDue, $homeSite, $backupSite, $groupUsagesGB,
			$groupCharges, $podsUse){
		
		\OCP\Util::writeLog('Files_Accounting', 'Billing user: '.$user.' for '.$this->billingMonthName, \OCP\Util::WARN);

		$articles = array(
				array('item'=>$homeGB.' GB cloud storage, '.$this->billingMonthName.' '.$this->billingYear.
						' at '.$homeSite, 'price'=>$homeAmountDue)
		);
		if(!empty($backupSite) && isset($backupGB)){
			array_push($articles, array('item'=>$backupGB. ' GB cloud backup storage, '.$this->billingMonthName.
				' - '.$this->billingYear.' at '.$backupSite, 'price'=>$backupAmountDue)
			);
		}
		foreach($groupUsagesGB as $group=>$groupUsageGB){
			array_push($articles, array('item'=>$groupUsageGB. ' GB cloud storage of group '.$group.
			', '.$this->billingMonthName.' - '.$this->billingYear,
			'price'=>$groupCharges[$group])
			);
		}
		foreach($podsUse['charges'] as $image=>$cost){
			array_push($articles, array('item'=>'Pod image '.$image. '  '.$podsUse['seconds'][$image].
					' seconds, '.$this->billingMonthName.' - '.$this->billingYear,
					'price'=>$cost)
					);
		}
		\OCP\Util::writeLog('Files_Accounting', 'HOME: '.$homeGB.' '.$homeAmountDue.' '.$homeSite, \OCP\Util::WARN);
		\OCP\Util::writeLog('Files_Accounting', 'BACKUP: '.$backupGB.' '.$backupAmountDue.' '.$backupSite, \OCP\Util::WARN);
		\OCP\Util::writeLog('Files_Accounting', 'PODS: '.$podsUse['total_charge'], \OCP\Util::WARN);
		/*$total = 0;
		for ($i=0; $i<count($articles); $i++){
			$total += $articles[$i]['price'];
		}*/
		$filename = $reference.'.pdf';
		$userEmail = \OCP\Config::getUserValue($user, 'settings', 'email');
		$userRealName = User::getDisplayName($user);
		$fromEmail = \OCA\Files_Accounting\Storage_Lib::getIssuerEmail();
		$fromAddress = \OCA\Files_Accounting\Storage_Lib::getIssuerAddress();

		$this->writeInvoice(
				$user,
				$userEmail,
				$userRealName,
				$fromEmail,
				$fromAddress,
				date("F j, Y", $this->timestamp), // issue date
				date("F j, Y", $this->dueTimestamp), // due date
				$reference,
				$articles,
				$totalAmountDue,
				"Thank you for using our services.",
				$filename
		);
		return $filename;
	}

	private function writeInvoice($user, $userEmail, $userRealName, $email, $address, $date, $dueDate, $ref,
			$articles, $total, $comment, $filename){

		$pdf = new PDF();
		$pdf->AliasNbPages();
		$pdf->AddPage();

		$logoUrl = \OCP\Config::getSystemValue('billinglogo', '');
		$logoFileType = strtoupper(pathinfo(parse_url($logoUrl, PHP_URL_PATH) , PATHINFO_EXTENSION));
		$pdf->Image($logoUrl, 10, 10, 30, 17.1, $logoFileType/*PNG*/);
		
		$vat = (float) \OCA\Files_Accounting\Storage_Lib::getBillingVAT();

		$x=$pdf->GetX();
		$y=$pdf->GetY();
		$pdf->SetXY($x+120,$y);

		$pdf->SetFont('Arial','',12);
		$pdf->SetTextColor(32);
		$formattedAddress = str_replace(", ", "\n", $address);
		$pdf->MultiCell(0,5,$formattedAddress,0,'L',0);

		$x=$pdf->GetX();
		$y=$pdf->GetY();
		$pdf->SetXY($x+120,$y);

		$pdf->Cell(0,5,$email,0,1,'L');

		$pdf->Cell(0,30,'',0,1,'R');
		$pdf->SetFillColor(230,230,230);
		$pdf->ChapterTitle('Email: ',$userEmail);
		$pdf->ChapterTitle('Name: ',$userRealName);
		$pdf->ChapterTitle('Invoice Number: ',$ref);
		$pdf->ChapterTitle('Invoice Date: ',$date);
		$pdf->ChapterTitle('Due Date: ',$dueDate);
		$pdf->Cell(0,20,'',0,1,'R');
		$pdf->SetFillColor(211,211,211);
		$pdf->SetDrawColor(192,192,192);
		$pdf->Cell(150,7,'Item',1,0,'L');
		$pdf->Cell(40,7,'Price',1,1,'L');
		foreach($articles as $article){
			$pdf->Cell(150,7,$article['item'],1,0,'L',0);
			$pdf->Cell(40,7,$article['price']." ".$this->billingCurrency,1,1,'R',0);
		}
		$pdf->Cell(0,0,'',0,1,'R');
		$pdf->Cell(150,7,'VAT included',1,0,'R',0);
		$pdf->Cell(40,7,$vat.'%',1,1,'R',0);
		$pdf->Cell(150,7,'Total',1,0,'R',0);
		$pdf->Cell(40,7,$total." ".$this->billingCurrency,1,0,'R',0);
		$pdf->Cell(0,20,'',0,1,'R');
		$pdf->Cell(190,40,$comment,0,0,'C');

		$path = \OCA\Files_Accounting\Storage_Lib::getInvoiceDir($user);
		if(!file_exists($path)){
			mkdir($path, 0777, false);
		}
		$pdf->Output($path.'/'.$filename, 'F');
	}
}

