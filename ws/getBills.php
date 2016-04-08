<?php

OCP\JSON::checkAppEnabled('files_accounting');
OCP\JSON::checkAppEnabled('files_sharding');

if(!OCA\FilesSharding\Lib::checkIP()){
	http_response_code(401);
	exit;
}
$userid = isset($_GET['userid'])?$_GET['userid']:null;
$year = isset($_GET['year'])?$_GET['year']:null;
$status = isset($_GET['status'])?$_GET['status']:null;
$ret = OCA\Files_Accounting\Storage_Lib::dbGetBills($userid, $year, $status);
OCP\JSON::encodedPrint($ret);

