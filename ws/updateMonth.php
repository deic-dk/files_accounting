<?php

OCP\JSON::checkAppEnabled('files_accounting');
OCP\JSON::checkAppEnabled('files_sharding');

if(!OCA\FilesSharding\Lib::checkIP()){
        http_response_code(401);
        exit;
}

$userid = isset($_GET['userid'])?$_GET['userid']:\OC_User::getUser();
$status = isset($_GET['status'])?$_GET['status']:null;
$month = isset($_GET['month'])?$_GET['month']:null;
$year = isset($_GET['year'])?$_GET['year']:null;
$average = isset($_GET['average'])?$_GET['average']:null;
$averageBackup = isset($_GET['averageBackup'])?$_GET['averageBackup']:null;
$averageTrash = isset($_GET['averageTrash'])?$_GET['averageTrash']:null;
$bill = isset($_GET['bill'])?$_GET['bill']:null;
$reference_id = isset($_GET['reference_id'])?$_GET['reference_id']:null;

$ret = OCA\Files_Accounting\Util::dbUpdateMonth($userid, $status, $month, $year, $average, $averageBackup, $averageTrash, $bill, $reference_id);
OCP\JSON::encodedPrint($ret);
