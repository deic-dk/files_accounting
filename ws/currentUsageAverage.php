<?php

OCP\JSON::checkAppEnabled('files_accounting');
OCP\JSON::checkAppEnabled('files_sharding');

if(!OCA\FilesSharding\Lib::checkIP()){
	http_response_code(401);
	exit;
}
$userid = isset($_GET['userid'])?$_GET['userid']:null;
$month = isset($_GET['month'])?$_GET['month']:null;
$year = isset($_GET['year'])?$_GET['year']:null;
$ret = OCA\Files_Accounting\Storage_Lib::localCurrentUsageAverage($userid, $year, $month);
OCP\JSON::encodedPrint($ret);
