<?php

namespace OCA\Files_Accounting;

use \OCP\DB;
use \OCP\User;
use \OCP\Util;
use \OCP\Config;

//require('invoice.php');

class Stats extends \OC\BackgroundJob\QueuedJob {
	protected function run($argument) {
		$file_update = $this->updateMonthlyAverage();
	}
	public function updateMonthlyAverage() {
		$year = date('Y');
		$users= User::getUsers();
		foreach ($users as $user) { 
			if (User::userExists($user)) {
				$file = file_get_contents("/tank/data/owncloud/".$user."/diskUsageAverage".$year.".txt");
				if (date("d") == "01") {
		        		$lines = file('/tank/data/owncloud/'.$user.'/diskUsageDaily'.$year.'.txt');
                			$dailyUsage = array();
                			$averageToday = 0 ;
                			$averageTodayTrash = 0;
                			foreach ($lines as $line) {
                    				$userRows = explode(" ", $line);
                    				if ($userRows[0] == $user) {
                                			$month =  (int)substr($userRows[1], 0, 2);
							if ($month == ((int)date("m") - 01)) {
								$dailyUsage[] = array('usage' => (float)$userRows[2], 'trash' => (float)$userRows[3], 'month' => $month);
                                   				$averageToday = array_sum(array_column($dailyUsage, 'usage')) / count(array_column($dailyUsage, 'usage'));
                                   				$averageTodayTrash = array_sum(array_column($dailyUsage, 'trash')) / count(array_column($dailyUsage, 'trash'));
	
							}
						}		 
					}
					$monthToSave = (string)((int)date("m") - 01);
					$averageToday = (string)$averageToday;
					$averageTodayTrash = (string)$averageTodayTrash;
					$txt = $user.' '.$monthToSave.' '.$averageToday.' '.$averageTodayTrash;	
					if ($averageToday != '0' && strpos($file, $txt) === false ) {
						$monthlyAverageFile = fopen("/tank/data/owncloud/".$user."/diskUsageAverage".$year.".txt", "a") or die("Unable to open file!");
						$stringData = $txt . "\n";
                        			$rv = fwrite($monthlyAverageFile, $stringData);
						if ( ! $rv ){
        						die("unable to write to file");
						}
                        			fclose($monthlyAverageFile);
                			}
					$updateDb = Stats::addToDb($user, $monthToSave, $averageToday, $averageTodayTrash); 
				}
			}
		}
	}
	public function addToDb($user, $month, $average, $averageTrash) {
	// Check for existence
                $stmt = DB::prepare ( "SELECT `month` FROM `*PREFIX*files_accounting` WHERE `user` = ? AND `month` = ?" );
                $result = $stmt->execute ( array (
                                $user,
				$month
                ) );
                if ($result->fetchRow ()) {
                        return false;
                }else {	
			$charge = (float) Config::getAppValue('files_accounting', 'dkr_perGb', '');
			$bill = ((float)$average/1000000)*$charge;
			$bill = round($bill, 2);
			$bill = (string)$bill;
			$stmt = DB::prepare ( "INSERT INTO `*PREFIX*files_accounting` ( `user`, `status`, `month`, `average`, `trashbin`, `bill`, `year` ) VALUES( ? , ? , ? , ? , ?, ?,?  )" );
			$result = $stmt->execute ( array (
				$user,
				'0',
				$month,
				$average,
				$averageTrash,
				$bill,
				date("Y") 
			) );
			
			$fullmonth = date('F', strtotime("2000-$month-01"));
			$notifyUser = Stats::sendNotificationMail($user, $fullmonth, $bill);
			$notification = ActivityHooks::invoiceCreate($fullmonth);

			return $result ? true : false;	
		}
	} 
	public function sendNotificationMail($user, $fullmonth, $bill) {
		$username = User::getDisplayName($user);
		
		$url = 	Config::getAppValue('files_accounting', 'url', '');
		$sender = 'cloud@data.deic.dk';
		$subject = 'Data DeIC: Invoice Payment for '.$fullmonth;
		$message = 'Dear '.$username.','."\n \n".'The bill for '.$fullmonth.' is '.$bill.' DKK. Please find in the attachments an invoice.'."\n".'To complete payment click the following link:'."\n \n".$url."\n \n".'Thank you for choosing our services'."\n \n".'Data DeIC';
		$headers = 'From: '.$sender . "\r\n" . 'Reply-To: ' .$sender. "\r\n" . 'X-Mailer: PHP/' . phpversion ();
		try {
			mail ( $user, $subject, $message, $headers, "-r " . $user );
		} catch (\Exception $e) {
			Util::writeLog('FilesAccounting', 'A problem occurred while sending the e-mail. Please revisit your settings.', Util::ERROR);
		}
	}
	public function createInvoice(){

	}
}

