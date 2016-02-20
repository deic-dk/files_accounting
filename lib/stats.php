<?php

namespace OCA\Files_Accounting;

use \OCP\DB;
use \OCP\User;
use \OCP\Config;
use \OCA\Files_Accounting\ActivityHooks;
use \OC_Preferences;
use Mail;
use \OCP\Defaults;

require_once('deicfpdf.php');

class Stats extends \OC\BackgroundJob\QueuedJob {
	protected function run($argument) {
		$file_update = $this->updateMonthlyAverage();
	}
	public function updateMonthlyAverage() {
		if(!\OCP\App::isEnabled('files_sharding') || \OCA\FilesSharding\Lib::isMaster()){
			if (date("d") == "20") {
				if(\OC_User::isAdminUser(\OC_User::getUser())){
					$users = User::getUsers();
				}
				else{
					$users = array(\OC_User::getUser());
				}
				$totalAverageUsers = array();
				if ((int)date("m") != 01) { 
					$monthToSave = (string)((int)date("m") - 01);
                        		$fullmonth = date('F', strtotime("2000-$monthToSave-01"));
					$year = date('Y');
				}else {
					$monthToSave = "12";
					$fullmonth = date('F', strtotime("2000-$monthToSave-01"));	
					$year = (int)date('Y') - 1;	
				}
				foreach ($users as $user) {
					if (User::userExists($user)) {
						$dailyUsage = \OCA\Files_Accounting\Storage_Lib::dailyUsage($user, $monthToSave, $year);
						if (!empty($dailyUsage[0])){
							$averageTodayHome = $dailyUsage[0][1];
							$averageTodayTrashHome = $dailyUsage[0][2];
						}
						if (!empty($dailyUsage[1])) {
							$averageTodayBackup = $dailyUsage[1][1];
							$averageTodayTrashBackup = $dailyUsage[1][2];
						}
						$averageToday = array(isset($averageTodayHome)?$averageTodayHome:null, 
									isset($averageTodayBackup)?$averageTodayBackup:null);
						$averageTodayTrash = array(isset($averageTodayTrashHome)?$averageTodayTrashHome:null,
									isset($averageTodayTrashBackup)?$averageTodayTrashBackup:null);

						$updateDb = Stats::addToDb($user, $monthToSave, $year, $averageToday, $averageTodayTrash);
					}
				}
			}
		}
	}

	public function addToDb($user, $month, $year, $average, $averageTrash) {
		// Check for existence
                $stmt = DB::prepare ( "SELECT `month` FROM `*PREFIX*files_accounting` WHERE `user` = ? AND `month` = ?" );
                $result = $stmt->execute ( array (
                                $user,
				$year."-".$month
                ) );
                if ($result->fetchRow ()) {
                        return false;
                }else {	
			//todo
			// check for freequota instead
			$gift_card = OC_Preferences::getValue($user, 'files_accounting', 'freequotaexceed');
			$charge = \OCA\Files_Accounting\Storage_Lib::getChargeForUserServers($user);
			$homeServerCharge = isset($charge["home"])?$charge["home"]:null;
			$backupServerCharge = isset($charge["backup"])?$charge["backup"]:null;
			$totalAverageHome = (isset($average[0])?$average[0]:0) + (isset($averageTrash[0])?$averageTrash[0]:0);
			$totalAverageBackup = (isset($average[1])?$average[1]:null) + (isset($averageTrash[1])?$averageTrash[1]:null);
			$quantityHome = ((float)$totalAverageHome/pow(1024, 2)); //kilobytes to gigabytes
			$quantityBackup = ((float)$totalAverageBackup/pow(1024, 2)); 
			$quantity = $quantityHome + (isset($quantityBackup)?$quantityBackup:0);
			$totalAverage = (isset($average[0])?$average[0]:0) + (isset($average[1])?$average[1]:0);
			$totalAverageTrash = (isset($averageTrash[0])?$averageTrash[0]:0) + (isset($averageTrash[1])?$averageTrash[1]:0); 
			if (isset($gift_card)){
			    //bytes to gigabytes
				$gift_card = (float) \OCP\Util::computerFileSize($gift_card)/pow(1024, 3);
				if ($quantity > $gift_card) {
					$bill = self::getBillingInServers($quantityHome, $quantityBackup, $homeServerCharge, $backupServerCharge);
					$totalBill = array_sum($bill);
					$reference_id = Stats::createInvoice($month, $year, $user, round($quantityHome, 2), round($quantityBackup, 2), 
							$bill, $homeServerCharge, $backupServerCharge);
					$result = self::setBill($user, '0', $month, $year, $quantity, $charge, $totalAverage, $totalAverageTrash, $totalBill, $reference_id); 
				}else {
					$result = \OCA\Files_Accounting\Util::updateMonth($user, '2', $month, $totalAverage, $totalAverageTrash, '', '');
				}
			}else{
				//todo
				$bill = self::getBillingInServers($quantityHome, $quantityBackup, $homeServerCharge, $backupServerCharge);
				$totalBill = array_sum($bill);
				$reference_id = Stats::createInvoice($month, $year, $user, round($quantityHome, 2), round($quantityBackup, 2),
                                                        $bill, $homeServerCharge, $backupServerCharge);
				\OCP\Util::writeLog('Files_Accounting', 'Trash: '.($average[0]+$average[1]), \OCP\Util::ERROR);
				$result = self::setBill($user, '0', $month, $year, $quantity, $charge, $totalAverage, $totalAverageTrash, $totalBill, $reference_id);
			}
			return $result ? true : false;
		}
	} 

	public static function getBillingInServers($quantityHome, $quantityBackup, $homeServerCharge, $backupServerCharge) {
	  	if (isset($homeServerCharge)) {
	      		$billHome = round($quantityHome*$homeServerCharge, 2);
      		}   
      		if (isset($backupServerCharge) && isset($quantityBackup)) {
    	  		$billBackup = round($quantityBackup*$backupServerCharge, 2);
      		}   
      		$bill = array(isset($billHome)?$billHome:0, isset($billBackup)?$billBackup:null);
	  	return $bill;
	} 

	public static function setBill($user, $status, $month, $year, $quantity, $charge, $totalAverage, $totalAverageTrash, $bill, $reference_id) {
		$fullmonth = date('F', strtotime("2000-$month-01"));
        	$result = \OCA\Files_Accounting\Util::updateMonth($user, $status, $month, $year, $totalAverage, $totalAverageTrash, $bill, $reference_id);
        	$notification = ActivityHooks::invoiceCreate($user, $fullmonth);

		return true;
	}  

	public static function sendNotificationMail($user, $fullmonth, $bill, $filename) {
                $username = User::getDisplayName($user);
                $path = '/tank/data/owncloud/'.$user;
                $file = $path . "/" . $filename;
                $defaults = new \OCP\Defaults();
                $senderName = $defaults->getName();
                $url =  Config::getAppValue('files_accounting', 'url', '');
		$senderAddress = \OCP\Config::getSystemValue('fromaddress', 'cloud@deic.dk');
                $subject = 'Invoice Payment for '.$fullmonth;
                $message = 'Dear '.$username.','."\n \n".'The bill for '.$fullmonth.' is '.$bill.' DKK. Please find an invoice in the attachments.'."\n".'To complete payment click the following link:'."\n
\n".$url."\n \n".'Thank you for choosing our services.';
                Mail::send($user, $username, $subject, $message, $senderAddress, $senderName, $file);
        }


	public function createInvoice($month, $year, $user, $quantityHome, $quantityBackup, $bill, $homeServerCharge, $backupServerCharge){	
		$serverNames = \OCA\Files_Accounting\Storage_Lib::getServerNamesForInvoice($user);
		$vat = (float) Config::getAppValue('files_accounting', 'tax', '');
		// from DB
	 	$monthname = date('F', strtotime("2000-$month-01"));
		if (date("m") != '12') {
			$duemonth = (int)date("m") + 01;
			$dueyear = (int)date("Y");
		} else {
			$duemonth = 01;
			$dueyear = (int)date("Y") + 1;
		}
		$duemonthname = date('F', strtotime("2000-$duemonth-01"));
		$articles = array(
                                array('Cloud storage '.$monthname.' '.$year.' '.$serverNames["home"][1], 
                                        $quantityHome, $homeServerCharge, $bill[0], $serverNames["home"][0])
				);
		if (isset($serverNames["backup"]) && isset($quantityBackup)){
			array_push($articles, array('Cloud storage '.$monthname.' '.$year.' '.$serverNames["backup"][1],
                                                $quantityBackup, $backupServerCharge, $bill[1], $serverNames["backup"][0])
				);
		}
		\OCP\Util::writeLog('Files_Accounting', 'HOME: '.$quantityHome.' '.$homeServerCharge.' '.$bill[0], \OCP\Util::ERROR);
		\OCP\Util::writeLog('Files_Accounting', 'BACKUP: '.$quantityBackup.' '.$backupServerCharge.' '.$bill[1], \OCP\Util::ERROR);
		$referenceHash = md5( $year.$user.$month );
		$reference = $year.'-'.$month.'-'.substr( $referenceHash, 0, 8 );
		$total = 0;
		for ($i=0; $i<count($articles); $i++){
			$total += $articles[$i][3];
		}
		$filename = $reference.'.pdf';
		Stats::writeInvoice(	User::getDisplayName($user), // Name
								$user,		// eMail
								"Institute address:\n7200 DeIC\nDTU\nBygning 305\n2800 Kongens Lyngby\n+45 3588 8200", // deic address
								date("F j, Y"),	// date
								$duemonthname." ".date("j").", ".$dueyear,	// due date
								$reference,			// reference #
								$articles,
								$vat,
								"",
								$total,
								"",
								$filename);

		//Stats::sendNotificationMail($user, $monthname, $bill, $filename);	

		return $reference;
	}

	public function writeInvoice($name, $email, $address, $date, $dueDate, $ref, $articles, $vat, $charge, $total, $comment, $filename){
		$pdf = new PDF();
		// Logo
		$pdf->AddPage();
		$pdf->Image('/usr/local/www/owncloud/apps/files_accounting/img/logo.png', 190-2*30+10, 10, 2*30, 2*17.1, 'PNG');
		$pdf->Cell(190,2*17.1,'',0,0,0);
		$pdf->Ln();

		// Addresses
		$pdf->AddressTable($address,$name);

		// Table 1
		$header = array(
			array('Date', 'Customer eMail'),
			array('Due date', 'Reference #'),
			);
		/* DATA */
		$data = array(
			array($date, $email),
			array($dueDate, $ref),
			);
		/* DATA */
		$widths = array(90, 100);
		$borders = array(
			array('LT','LTR'),
			array('LB','LBR'),
			);
		$pdf->UserTable($widths,$borders,$header,$data);

		// Gap
		$pdf->Ln();

		// Table 2
		$header = array('Description', 'Quantity', 'Unit price', 'Total amount');
		$footer = array('', 'Subtotal', 'VAT', 'Total');
		/* DATA */
		$data = $articles;
		$sumData = array('', 'DKK '.($total*(1-$vat)), 'DKK '.($total*$vat), 'DKK '.$total);
		/* DATA */
		$widths = array(
					array(90, 34, 33, 33),
					array(45, 45, 50, 50),
					);
		$borders = array(
			array('LTB','LTB','LTB','LTBR'),
			array('L','L','L','LR'),
			array('T','LT','LT','LTR'),
			array('','LB','LB','LBR')
			);
		$aligns = array(
			array('L','L','L','L','L'),
			array('L','R','R','R','R'),
			array('L','L','L','L'),
			array('R','R','R','R')
			);
		$pdf->ProductTable($widths,$borders,$aligns,$header,$data,$footer,$sumData);

		// Gap
		$pdf->Ln();

		// Table 3
		$width = 190;
		$borders = array('LTR', 'LBR');
		$header = 'Comments';
		$data = $comment;
		if($comment){
			$pdf->CommentsTable($width,$borders,$header,$data);
		}
		$pdf->Output('/tank/data/owncloud/'.$email.'/'.$filename, 'F');
	}
}
	
