<?php

OCP\JSON::checkAppEnabled('files_accounting');
OCP\JSON::checkAppEnabled('files_sharding');

if(!OCA\FilesSharding\Lib::checkIP()){
        http_response_code(401);
        exit;
}

$userid = isset($_GET['user_id'])?$_GET['user_id']:\OC_User::getUser();
$status = isset($_GET['status'])?$_GET['status']:null;
$year = isset($_GET['year'])?$_GET['year']:null;
$month = isset($_GET['month'])?$_GET['month']:null;
$timestamp = isset($_GET['timestamp'])?$_GET['timestamp']:null;
$timedue = isset($_GET['time_dur'])?$_GET['time_dur']:null;
$average = isset($_GET['home_files_usage'])?$_GET['home_files_usage']:null;
$averageTrash = isset($_GET['home_trash_usage'])?$_GET['home_trash_usage']:null;
$averageBackup = isset($_GET['backup_files_usage'])?$_GET['backup_files_usage']:null;
$homeId = isset($_GET['home_id'])?$_GET['home_id']:null;
$backupId = isset($_GET['backup_id'])?$_GET['backup_id']:null;
$homeUrl = isset($_GET['home_url'])?$_GET['home_url']:null;
$backupUrl = isset($_GET['backup_url'])?$_GET['backup_url']:null;
$homeSite = isset($_GET['home_site'])?$_GET['home_site']:null;
$backupSite = isset($_GET['backup_site'])?$_GET['backup_site']:null;
$amountDue = isset($_GET['amount_due'])?$_GET['amount_due']:null;
$referenceId = isset($_GET['reference_id'])?$_GET['reference_id']:null;

$ret = OCA\Files_Accounting\Storage_Lib::dbUpdateMonth($userid, $status, $year, $month, $timestamp, $timedue,
		$average, $averageTrash, $averageBackup, $homeId, $backupId, $homeUrl, $backupUrl, $homeSite, $backupSite,
		$amountDue, $referenceId);
OCP\JSON::encodedPrint($ret);
