<?php

OCP\JSON::checkAppEnabled('files_accounting');
OCP\JSON::checkAppEnabled('files_sharding');

if(!OCA\FilesSharding\Lib::checkIP()){
        http_response_code(401);
        exit;
}

$userid = isset($_POST['user_id'])?$_POST['user_id']:\OC_User::getUser();
$status = isset($_POST['status'])?$_POST['status']:null;
$year = isset($_POST['year'])?$_POST['year']:null;
$month = isset($_POST['month'])?$_POST['month']:null;
$timestamp = isset($_POST['timestamp'])?$_POST['timestamp']:null;
$timedue = isset($_POST['time_due'])?$_POST['time_due']:null;
$average = isset($_POST['home_files_usage'])?$_POST['home_files_usage']:null;
$averageTrash = isset($_POST['home_trash_usage'])?$_POST['home_trash_usage']:null;
$averageBackup = isset($_POST['backup_files_usage'])?$_POST['backup_files_usage']:null;
$homeId = isset($_POST['home_id'])?$_POST['home_id']:null;
$backupId = isset($_POST['backup_id'])?$_POST['backup_id']:null;
$homeUrl = isset($_POST['home_url'])?$_POST['home_url']:null;
$backupUrl = isset($_POST['backup_url'])?$_POST['backup_url']:null;
$homeSite = isset($_POST['home_site'])?$_POST['home_site']:null;
$backupSite = isset($_POST['backup_site'])?$_POST['backup_site']:null;
$amountDue = isset($_POST['amount_due'])?$_POST['amount_due']:null;
$referenceId = isset($_POST['reference_id'])?$_POST['reference_id']:null;

$ret = OCA\Files_Accounting\Storage_Lib::dbUpdateMonth($userid, $status, $year, $month, $timestamp, $timedue,
		$average, $averageTrash, $averageBackup, $homeId, $backupId, $homeUrl, $backupUrl, $homeSite, $backupSite,
		$amountDue, $referenceId);
OCP\JSON::encodedPrint($ret);
