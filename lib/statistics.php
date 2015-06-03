<?php

class Statistics {
	protected function updateiDailyAverage(){
		$user= OCP\USER::getUser();
		$dailyAverageFile = fopen("/tank/data/owncloud/".$user."/diskUsageDailyAverage.txt", "a") or die("Unable to open file!");	
	 	$lines = file('/tank/data/owncloud/'.$user.'/diskUsageDaily.txt');
		$dailyUsage = array();
		$averageToday = 0 ;
		$averageTodayTrash = 0;
            	foreach ($lines as $line_num => $line) {
                    $userRows = explode(" ", $line);
                    if ($userRows[0] == $user) {
				$month =  substr($userRows[1], 0, 2);
				if ($month == date('m')) { 
			           $month = (int)$month;
				   $dailyUsage[] = array('usage' => (int)$userRows[2], 'trash' => (int)$userRows[3], 'month' => $month);
				   $averageToday = array_sum(array_column($dailyUsage, 'usage')) / count(array_column($dailyUsage, 'usage'));
				   $averageTodayTrash = array_sum(array_column($dailyUsage, 'trash')) / count(array_column($dailyUsage, 'trash'));	
				}
		    }
               }
		$file = "/tank/data/owncloud/".$user."/diskUsageDailyAverage.txt";
		$file = escapeshellarg($file); 
		$line = `tail -n 1 $file`;
		$txt = $user.' '.date("Y-m-d").' '.$averageToday.' '.$averageTodayTrash."\n";
		if ($averageToday != 0 && $line != $txt) {
			fwrite($dailyAverageFile, $txt);	
			fclose($dailyAverageFile);
		}

	}	
	protected function updateMonthlyAverage() {
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
			$txt = $user.' '.date("m").' '.$averageToday.' '.$averageTodayTrash."\n";	
			if ($averageToday != 0 && $line != $txt) {
                        	fwrite($monthlyAverageFile, $txt);
                        	fclose($monthlyAverageFile);
                	}
		}
	}
}
	


