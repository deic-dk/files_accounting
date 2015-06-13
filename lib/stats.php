<?php

//namespace OCA\FilesAccounting\BackgroundJob;

class Stats extends \OC\BackgroundJob\QueuedJob {
	protected function run($argument) {
		if (\OC::$CLI) {
				$file_update = $this->updateMonthlyAverage();
		}
	}

	public function updateMonthlyAverage() {
		$user= OCP\USER::getUser();
		$monthlyAverageFile = fopen("/tank/data/owncloud/".$user."/diskUsageAverage.txt", "a") or die("Unable to open file!");
		$file = "/tank/data/owncloud/".$user."/diskUsageAverage.txt";
                $file = escapeshellarg($file);
                $line = `tail -n 1 $file`;
		if (date("d") == "01") {
		       $lines = file('/tank/data/owncloud/'.$user.'/diskUsageDaily.txt');
                	$dailyUsage = array();
                	$averageToday = 0 ;
                	$averageTodayTrash = 0;
                	foreach ($lines as $line_num => $line) {
                    		$userRows = explode(" ", $line);
                    		if ($userRows[0] == $user) {
                                	$month =  (int)substr($userRows[1], 0, 2);
					if ($month == ((int)date("m") - 01)) {
						$dailyUsage[] = array('usage' => (int)$userRows[2], 'trash' => (int)$userRows[3], 'month' => $month);
                                   		$averageToday = array_sum(array_column($dailyUsage, 'usage')) / count(array_column($dailyUsage, 'usage'));
                                   		$averageTodayTrash = array_sum(array_column($dailyUsage, 'trash')) / count(array_column($dailyUsage, 'trash'));
	
					}
				}		 
			}
			$monthToSave = (string)((int)date("m") - 01);
			$averageToday = (string)$averageToday;
			$averageTodayTrash = (string)$averageTodayTrash;
			$txt = $user.' '.$monthToSave.' '.$averageToday.' '.$averageTodayTrash."\n";	
			if ($averageToday != '0' && $line != $txt) {
                        	$rv = fwrite($monthlyAverageFile, $txt);
				if ( ! $rv ){
        				die("unable to write to file");
				}
                        	fclose($monthlyAverageFile);
                	}
			$updateDb = Stats::addToDb($user, $monthToSave, $averageToday, $averageTodayTrash); 
		}
	}
	public function addToDb($user, $month, $average, $averageTrash) {
	// Check for existence
                $stmt = OC_DB::prepare ( "SELECT `month` FROM `*PREFIX*files_accounting` WHERE `user` = ? AND `month` = ?" );
                $result = $stmt->execute ( array (
                                $user,
				$month
                ) );
                if ($result->fetchRow ()) {
                        return false;
                }else {	
			$bill = ((int)$average/1000000)*2;
			$bill = (string)$bill;
			$stmt = OC_DB::prepare ( "INSERT INTO `*PREFIX*files_accounting` ( `user`, `status`, `month`, `average`, `trashbin`, `bill` ) VALUES( ? , ? , ? , ? , ?, ?  )" );
			$result = $stmt->execute ( array (
				$user,
				'0',
				$month,
				$average,
				$averageTrash,
				$bill 
			) );

			$notifyUser = Stats::sendNotificationMail($user, $month, $bill);

			return $result ? true : false;	
		}
	} 
	public function sendNotificationMail($user, $month, $bill) {
		$fullmonth = date('F', strtotime("2000-$month-01"));
		$url = 	'https://test.data.deic.dk/index.php/settings/personal';
		$sender = 'cloud@data.deic.dk';
		$subject = 'Bill for '.$fullmonth;
		$message = 'The bill for '.$fullmonth.' is '.$bill.' krones.
		Go to '.url.' to complete payment'; 
		
		$headers = 'From: '.$sender . "\r\n" . 'Reply-To: ' .$sender. "\r\n" . 'X-Mailer: PHP/' . phpversion ();
		try {
			mail ( $user, $subject, $message, $headers, "-r " . $user );
		} catch (\Exception $e) {
			\OCP\Util::writeLog('FilesAccounting', 'A problem occurred while sending the e-mail. Please revisit your settings.', \OCP\Util::ERROR);
		}
	}
}

