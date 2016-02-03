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
		if (date("d") == "01") {
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
					$dailyUsage = \OCA\Files_Accounting\Storage_Lib::dailyUsage($user, $year);
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
			//remove duplicate code and check for freequota instead
			$gift_card = OC_Preferences::getValue($user, 'files_accounting', 'freequotaexceed');
			$charge = (float) \OCA\Files_Accounting\Storage_Lib::getChargeForUserServers($user);
			$homeServerCharge = isset($charge["home"])?$charge["home"]:null;
			$backupServerCharge = isset($charge["backup"])?$charge["backup"]:null;
			$totalAverageHome = isset($average[0])?$average[0]:0 + isset($averageTrash[0])?$averageTrash[0]:0;
			$totalAverageBackup = isset($average[1])?$average[1]:null + isset($averageTrash[1])?averageTrash[1]:null
			$quantityHome = ((float)$totalAverageHome/1048576);
			$quantityBackup = ((float)$totalAverageBackup/1048576); 
			$quantity = $quantityHome + isset($quantityBackup)?$quantityBackup:0;
			$totalAverage = isset($average[0])?$average[0]:0 + isset($average[1])?$average[1]:0;
			$totalAverageTrash = isset($averageTrash[0])?$averageTrash[0]:0 + isset($averageTrash[1])?averageTrash[1]:0; 
			//todo
			//convert to computerfilesize instead and compare	
			if (isset($gift_card)){
				$str_to_ar = explode(" ", $gift_card);
                		$gift_card = (float) $str_to_ar[0];
                		$size = $str_to_ar[1];
                		if ($size == 'MB'){
                        		$gift_card = $gift_card/1024;
				}else if ($size == 'TB') {
					$gift_card = $gift_card * 1024;
				}

				if ($quantity > $gift_card) {
					if (isset($homeServerCharge)) {	
						$billHome = $quantityHome*$homeServerCharge;
					}
					if (isset($backupServerCharge) && isset($quantityBackup)) {
						$billBackup = $quantityBackup*$backupServerCharge;
					}
					$bill = isset($billHome)?$billHome:0 + isset($billBackup)?$billBackup:0;
					$bill = round($bill, 2);
					$fullmonth = date('F', strtotime("2000-$month-01"));
					$invoice = Stats::createInvoice($month, $year, $user, round($quantity, 2), $bill, $charge);
					$reference_id = $invoice;

					$result = Stats::updateMonth($user, '0', $month, $year, $totalAverage, $totalAverageTrash, $bill, $reference_id);	
					$notification = ActivityHooks::invoiceCreate($user, $fullmonth);

				}else {
					$result = Stats::updateMonth($user, '2', $month, $totalAverage, $totalAverageTrash, '', '');
				}
			}else{
				//todo
				$bill = $quantity*$charge;
				$bill = round($bill, 2);
				$fullmonth = date('F', strtotime("2000-$month-01"));
                		$invoice = Stats::createInvoice($month, $year, $user, round($quantity, 2), $bill, $charge);
                		$reference_id = $invoice;
				//todo	
				$result = Stats::updateMonth($user, '0', $month, $year, $totalAverage, $totalAverageTrash, $bill, $reference_id);
				$notification = ActivityHooks::invoiceCreate($user, $fullmonth);
			}
			return $result ? true : false;
		}
	} 

	public static function updateMonth($user, $status, $month, $year, $average, $averageTrash, $bill, $reference_id){
                $stmt = DB::prepare ( "INSERT INTO `*PREFIX*files_accounting` ( `user`, `status`, `month`, `average`, `trashbin`, `bill`, `reference_id`) VALUES( ? , ? , ?
, ? , ?, ?, ? )" );
                $result = $stmt->execute ( array (
                                                  $user,
                                                  $status,
                                                  date("$year-$month"),
                                                  $average,
                                                  $averageTrash,
                                                  $bill,
                                                  $reference_id
                                             ) );
         
                return $result;
        }

	public static function sendNotificationMail($user, $fullmonth, $bill, $filename) {
                $username = User::getDisplayName($user);
                $path = '/tank/data/owncloud/'.$user;
                $file = $path . "/" . $filename;
		//TODO
                $senderAddress = 'cloud@data.deic.dk';
                $defaults = new \OCP\Defaults();
                $senderName = $defaults->getName();
                $url =  Config::getAppValue('files_accounting', 'url', '');
                $sender = 'cloud@data.deic.dk';
                $subject = 'DeIC Data: Invoice Payment for '.$fullmonth;
                $message = 'Dear '.$username.','."\n \n".'The bill for '.$fullmonth.' is '.$bill.' DKK. Please find an invoice in the attachments.'."\n".'To complete payment click the following link:'."\n
\n".$url."\n \n".'Thank you for choosing our services.';
                Mail::send($user, $username, $subject, $message, $senderAddress, $senderName, $file);
        }


	public function createInvoice($month, $year, $user, $quantity, $bill, $charge){	
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
				array('Cloud storage '.$monthname.' '.$year, $quantity, $charge, $bill),
				//array('', '', '', '')
				);
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
								$charge,
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
	
