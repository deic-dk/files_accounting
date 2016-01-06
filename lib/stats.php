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
		if (date("d") == "06") {
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

			$monthlyUsage = array("Storage use for ".$fullmonth."\n"."\n".str_pad('User',30,' ')."Usage(KB)"."\n");
			foreach ($users as $user) {
				if (User::userExists($user)) {
					$averageFilePath = "/tank/data/owncloud/".$user."/diskUsageAverage".$year.".txt";
					if(!file_exists($averageFilePath)){
						touch($averageFilePath);
					}
					$dailyFilePath = "/tank/data/owncloud/".$user."/diskUsageDaily".$year.".txt";
					if(!file_exists($dailyFilePath)){
						touch($dailyFilePath);
					}
					$file = file_get_contents($averageFilePath);
					$lines = file($dailyFilePath);
					$dailyUsage = array();
					$averageToday = 0 ;
					$averageTodayTrash = 0;
					foreach ($lines as $line) {
						$userRows = explode(" ", $line);
						if ($userRows[0] == $user) {
							$month =  (int)substr($userRows[1], 0, 2);
							if ($month == (int)$monthToSave) {
								$dailyUsage[] = array('usage' => (float)$userRows[2], 'trash' => (float)$userRows[3], 'month' => $month);
								$averageToday = array_sum(array_column($dailyUsage, 'usage')) / count(array_column($dailyUsage, 'usage'));
								$averageTodayTrash = array_sum(array_column($dailyUsage, 'trash')) / count(array_column($dailyUsage, 'trash'));
							}
						}
					}
					$totalAverage = $averageToday + $averageTodayTrash;
					array_push($totalAverageUsers, $totalAverage);
					$txt = $user.' '.$monthToSave.' '.$averageToday.' '.$averageTodayTrash;	
					if ($averageToday != '0' && strpos($file, $txt) === false ) {
						$monthlyAverageFile = fopen($averageFilePath, "a") or die("Unable to open file!");
						$stringData = $txt . "\n";
						$rv = fwrite($monthlyAverageFile, $stringData);
						if ( ! $rv ){
							die("unable to write to file");
						}
						fclose($monthlyAverageFile);
					}
					$updateDb = Stats::addToDb($user, $monthToSave, $year, $averageToday, $averageTodayTrash);
					$result = Util::inGroup($user);
					if ($result) {
						$saveText = "\n".str_pad($user,30," ").$totalAverage;	
						array_push($monthlyUsage, $saveText);
					}
				}
			}
			if(\OC_User::isAdminUser(\OC_User::getUser())){
				$totalAverageFilePath = "/tank/data/owncloud/".$user."/totalDiskUsageAverage".$year.".txt";
				if(!file_exists($totalAverageFilePath)){
					touch($totalAverageFilePath);
				}
				$saveText = "\n"."Total usage: ".array_sum($totalAverageUsers)." KB";
				array_push($monthlyUsage, $saveText);
				$info = implode(" ",$monthlyUsage);
	//		   	file_put_contents("/tank/data/owncloud/s141277@student.dtu.dk/useAverageDtu".$fullmonth.".txt", $info);
				file_put_contents($totalAverageFilePath, $info, FILE_APPEND);
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
			$gift_card = OC_Preferences::getValue($user, 'files_accounting', 'freequotaexceed');
			$charge = (float) Config::getAppValue('files_accounting', 'dkr_perGb', '');
			$quantity = ((float)$average/1048576);
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
				
					$bill = ($quantity - $gift_card)*$charge;
					$bill = round($bill, 2);
					$fullmonth = date('F', strtotime("2000-$month-01"));
					$invoice = Stats::createInvoice($month, $year, $user, round($quantity, 2), $bill, $charge);
					$reference_id = $invoice;

					$result = Stats::updateMonth($user, '0', $month, $year, $average, $averageTrash, $bill, $reference_id);	
					$notification = ActivityHooks::invoiceCreate($user, $fullmonth);

				}else {
					$result = Stats::updateMonth($user, '2', $month, $average, $averageTrash, '', '');
				}
			}else{
				$bill = $quantity*$charge;
				$bill = round($bill, 2);
				$fullmonth = date('F', strtotime("2000-$month-01"));
                		$invoice = Stats::createInvoice($month, $year, $user, round($quantity, 2), $bill, $charge);
                		$reference_id = $invoice;
				//todo	
				$result = Stats::updateMonth($user, '0', $month, $year, $average, $averageTrash, $bill, $reference_id);
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
	
