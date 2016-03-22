<?php

OCP\JSON::checkAppEnabled('files_accounting');
OCP\JSON::checkAppEnabled('files_sharding');

if(!OCA\FilesSharding\Lib::checkIP()){
	http_response_code(401);
	exit;
}

$action = isset($_GET['action'])?$_GET['action']:null;
$userid = isset($_GET['userid'])?$_GET['userid']:'';
$trashbin = isset($_GET['trashbin'])?$_GET['trashbin']:false;
switch ($action) {
	case "userStorage":
		$result = \OCA\Files_Accounting\Storage_Lib::dbUserStorage($userid, $trashbin);
		break;
	case "relativeSpace":
		$totalUsed = isset($_GET['totalUsed'])?$_GET['totalUsed']:null;
		$result = \OCA\Files_Accounting\Storage_Lib::dbRelativeSpace($userid, $totalUsed);
		break;
	case "freeQuotaConfig":
		$result = \OC_Preferences::getValue($userid, 'files_accounting', 'freequota');
		break;
	case "backupInternalUrl":
		$backupServerId = \OCA\FilesSharding\Lib::dbLookupServerIdForUser($userid, 1);
                if(!empty($backupServerId)){
                        $result = \OCA\FilesSharding\Lib::dbLookupInternalServerURL($backupServerId);
                }
}
OCP\JSON::encodedPrint($result);