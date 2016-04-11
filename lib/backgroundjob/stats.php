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
		// We need the previous month/year from now
		if ((int)date("m", $this->timestamp) != 01) {
			$this->billingMonth = (string)((int)date("m", $this->timestamp) - 01);
			$this->billingYear = date('Y', $this->timestamp);
		}
		else{
			$this->billingMonth = "12";
			$this->billingYear = (int)date('Y', $this->timestamp) - 1;
		}
		
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
			// logDailyUsage checks if the line already exists and bails of so.
			\OCP\Util::writeLog('Files_Accounting', 'Logging usage of user: '.$user, \OCP\Util::WARN);
			\OCA\Files_Accounting\Storage_Lib::logDailyUsage($user);
		}
		// Only run billing on the billing day
		if((int)date("d", $this->timestamp) != $this->billingDay){
			return true;
		}
		foreach ($users as $user) {
			updateAndBillUser($user);
		}
	}
	
	private function updateAndBillUser($user){

		if(!\User::userExists($user)){
			return;
		}
		\OCP\Util::writeLog('Files_Accounting', 'Billing user: '.$user, \OCP\Util::WARN);
		$personalStorage = \OCA\Files_Accounting\Storage_Lib::personalStorage($user);
		if(isset($personalStorage['freequota'])) {
			$freequota_exceeded = \OC_Preferences::getValue($user, 'files_accounting', 'freequotaexceeded', false);
			// bytes to gigabytes
			$freequotaBytes = (float) \OCP\Util::computerFileSize($personalStorage['freequota']);
			if($personalStorage['files_usage'] > $freequotaBytes){
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

		// Check if already logged monthly average to DB
		$stmt = DB::prepare ( "SELECT `month` FROM `*PREFIX*files_accounting` WHERE `user` = ? AND `year` = ? AND `month` = ?" );
		$result = $stmt->execute ( array ($user, $this->billingYear, $this->billingMonth) );
		if($result->fetchRow ()){
			return;
		}
		// Log monthly average to DB on master
		$charge = \OCA\Files_Accounting\Storage_Lib::getChargeForUserServers($user);
		$monthlyUsageAverage = \OCA\Files_Accounting\Storage_Lib::currentUsageAverage($user, $this->billingYear, $this->billingMonth);
		
		$totalAverageHome = ((float)$monthlyUsageAverage['home']['files_usage_average']) +
			((float)$monthlyUsageAverage['home']['trash_usage_average']);
		$totalAverageBackup = ((float)$monthlyUsageAverage['backup']['files_usage_average']) +
			((float)$monthlyUsageAverage['backup']['trash_usage_average']);
		$averageTrash = ((float)$monthlyUsageAverage['home']['trash_usage_average']) +
			((float)$monthlyUsageAverage['backup']['trash_usage_average']);
		
		// kilobytes to gigabytes
		$homeGB = $totalAverageHome/pow(1024, 2); 
		$backupGB = $totalAverageBackup/pow(1024, 2); 
		$trashGB = $averageTrash/pow(1024, 2); 

		$totalSumDue = round($homeGB*$charge['charge_home'], 2) + round($backupGB*$charge['charge_backup'], 2);
		$filename = $reference_id = $this->invoice($user,
				round($homeGB, 2), round($backupGB, 2), $totalSumDue,
				$charge['charge_home'], $charge['charge_backup'],
				$charge['site_home'], $charge['site_backup']);

		if(empty($filename)){
			\OCP\Util::writeLog('Files_Accounting', 'ERROR: could not create invoice for '.$user.', '.$this->billingMonth.', '.$totalSumDue, \OCP\Util::ERROR);
			return;
		}

		// This goes to master
		\OCA\Files_Accounting\Storage_Lib::updateMonth($user, \OCA\Files_Accounting\Storage_Lib::PAYMENT_STATUS_PENDING,
				$this->billingYear, $this->billingMonth, $this->timestamp, $this->dueTimestamp, $homeGB, $backupGB, $trashGB,
				$charge['id_home'], $charge['id_backup'], $charge['url_home'], $charge['url_backup'], $charge['site_home'],
				$charge['site_backup'], $totalSumDue, $reference_id);

		// Create invoice and store locally. It can always be recreated DB on master.
		ActivityHooks::invoiceCreate($user, $this->monthName);
		// If there's a non-zero bill, email the user regardless of activity settings
		if($totalSumDue>0){
			sendNotificationMail($user, $totalSumDue, $filename, $charge['site_home']);
		}

	}

	public function sendNotificationMail($user, $amount, $filename, $senderName) {
		$realName = User::getDisplayName($user);
		$path = \OCA\Files_Accounting\Storage_Lib::getInvoiceDir($user);
		$file = $path . "/" . $filename;
		$defaults = new \OCP\Defaults();
		$url =  
		$senderEmail = \OCA\Files_Accounting\Storage_Lib::getIssuerEmail();
		$subject = 'Invoice Payment for '.$this->monthName;
		$message = 'Dear '.$realName.','.'\n \nThe bill for '.$this->monthName.' is '.$amount.' '.
			$this->billingCurrency.'. An invoice is attached.\n'.
			(empty($url)?'Please complete payment in your account settings':'To complete payment please visit '.$url).'.\n\n'.
			'Thank you for using our services.';
		Mail::send($user, $username, $subject, $message, $senderEmail, $senderName, $file);
	}

	private function invoice($user, $homeGB, $backupGB, $totalAmountDue,
			$homeAmountDue, $backupAmountDue, $homeSite, $backupSite){
		
		$vat = (float) \OCA\Files_Accounting\Storage_Lib::getBillingVAT();
		$vat = $vat*0.01;
		
		$articles = array(
			array('item'=>$homeGB.' GB cloud storage, '.$this->billingMonthName.' - '.$this->billingYear.' at '.$homeSite,
					'price'=>$homeAmountDue)
			);
		if(!empty($backupSite) && isset($backupGB)){
			array_push($articles, array('item'=>$backupGB. 'GB cloud storage, '.$this->billingMonthName.' - '.$this->billingYear.' at '.$backupSite,
				'price'=>$backupAmountDue)
			);
		}
		\OCP\Util::writeLog('Files_Accounting', 'HOME: '.$homeGB.' '.$homeAmountDue.' '.$homeSite, \OCP\Util::WARN);
		\OCP\Util::writeLog('Files_Accounting', 'BACKUP: '.$backupGB.' '.$backupAmountDue.' '.$backupSite, \OCP\Util::WARN);
		$referenceHash = md5($year.$user.$month);
		$reference = $year.'-'.$month.'-'.substr($referenceHash, 0, 8);
		$total = 0;
		for ($i=0; $i<count($articles); $i++){
			$total += $articles[$i][3];
		}
		$filename = $reference.'.pdf';
		$userEmail = \OCP\Config::getUserValue($userid, 'settings', 'email');
		$userRealName = User::getDisplayName($user);
		$fromEmail = \OCA\Files_Accounting\Storage_Lib::getIssuerEmail();
		$fromAddress = \OCA\Files_Accounting\Storage_Lib::getIssuerAddress();
		
		$this->writeInvoice(
								$userEmail,
								$userRealName,
								$fromEmail,
								$fromAddress,
								date("F j, Y", $this->timestamp), // issue date
								date("F j, Y", $this->dueTimestamp), // due date
								$reference,
								$articles,
								$vat,
								$totalAmountDue,
								"Thanks for using our services.",
								$filename
		);
		return $filename;
	}

	private function writeInvoice($userEmail, $userRealName, $email, $address, $date, $dueDate, $ref,
			$articles, $vat, $total, $comment, $filename){
		
		$pdf = new PDF();
		$pdf->AliasNbPages();
		$pdf->AddPage();
		$pdf->SetFont('Arial','',12);
		$pdf->SetTextColor(32);
		$pdf->Cell(0,5,$address,0,1,'R');
		$pdf->Cell(0,5,$email,0,1,'R');
		$pdf->Cell(0,30,'',0,1,'R');
		$pdf->SetFillColor(200,220,255);
		$pdf->ChapterTitle('Email ',$userEmail);
		$pdf->ChapterTitle('Name ',$userRealName);
		$pdf->ChapterTitle('Invoice Number ',$ref);
		$pdf->ChapterTitle('Invoice Date ',$date);
		$pdf->ChapterTitle('Due Date ',$dueDate);
		$pdf->Cell(0,20,'',0,1,'R');
		$pdf->SetFillColor(211,211,211);
		$pdf->SetDrawColor(192,192,192);
		$pdf->Cell(170,7,'Item',1,0,'L');
		$pdf->Cell(20,7,'Price',1,1,'C');
		foreach($articles as $article){
			$pdf->Cell(170,7,$article['item'],1,0,'L',0);
			$pdf->Cell(20,7,$article['price']." ".$this->billingCurrency,1,1,'R',0);
		}
		$pdf->Cell(0,0,'',0,1,'R');
		$pdf->Cell(170,7,'VAT',1,0,'R',0);
		$pdf->Cell(20,7,$vat,1,1,'R',0);
		$pdf->Cell(170,7,'Total',1,0,'R',0);
		$pdf->Cell(20,7,$total." ".$this->billingCurrency,1,0,'R',0);
		$pdf->Cell(0,20,'',0,1,'R');
		$pdf->Cell(190,40,$comment,0,0,'C');
		
		$path = \OCA\Files_Accounting\Storage_Lib::getInvoiceDir($user);
		if(!file_exists($usageFilePath)){
			mkdir($usageFilePath, 0777, false);
		}
		$pdf->Output($path.'/'.$filename, 'F');
	}
}

