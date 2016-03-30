<?php
$user = \OCP\User::getUser();
$userStorage  = array();
$year = isset($_GET['year'])?$_GET['year']:date('Y');
$average_lines = \OCA\Files_Accounting\Util::userBill($user, $year, true);
foreach ($average_lines as $line) {
	//$userRows = explode(" ", $line);
	//if ($userRows[0] == $user) {
	$month =  $line['month'];
	if ($month != date('m')) {
		$averageHomeMonth = $line['average'];
		$averageMonthTrash = $line['trashbin'];
		$averageBackupMonth = $line['average_backup']; 
		$fullmonth = date('F', strtotime("2000-$month-01"));
		$fullmonth = substr($fullmonth, 0, 3);
		if (isset($averageBackupMonth)) { 
			$userStorage[] = array($fullmonth, $averageHomeMonth, $averageMonthTrash, $averageBackupMonth);
		}else {
			$userStorage[] = array($fullmonth, $averageMonth, $averageMonthTrash);
		}

	}

}

$dailyUsage = OCA\Files_Accounting\Storage_Lib::dailyUsage($user, date("m"), $year);
if (isset($dailyUsage[1])){
	array_push($dailyUsage[0], $dailyUsage[1][1]);
}
//$userStorage[] = $dailyUsage[0]; 
OCP\JSON::success(array('data' => $userStorage));
//echo json_encode($userStorage);
?>
