<?php
$user = \OCP\User::getUser();
$userStorage  = array();
$year = isset($_GET['year'])?$_GET['year']:date('Y');
$average_lines = \OCA\Files_Accounting\Util::userBill($user, $year);
foreach ($average_lines as $line) {
	//$userRows = explode(" ", $line);
	//if ($userRows[0] == $user) {
	$month =  $line['month'];
	if ($month != date('m')) {
		$averageMonth = (int)$line['average'];
		$averageMonthTrash = (int)$line['trashbin'];
		$fullmonth = date('F', strtotime("2000-$month-01"));
		$fullmonth = substr($fullmonth, 0, 3);
		$userStorage[] = array($fullmonth, $averageMonth, $averageMonthTrash);

	}

}

$userStorage[] = OCA\Files_Accounting\Storage_Lib::dailyUsageSum($user, $year);
OCP\JSON::success(array('data' => $userStorage));
//echo json_encode($userStorage);
?>
