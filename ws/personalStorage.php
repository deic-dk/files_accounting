<?php

OCP\JSON::checkAppEnabled('files_accounting');
OCP\JSON::checkAppEnabled('files_sharding');

if(!OCA\FilesSharding\Lib::checkIP()){
	http_response_code(401);
	exit;
}

$key = isset($_GET['key'])?$_GET['key']:null;
$userid = isset($_GET['userid'])?$_GET['userid']:'';
switch ($key) {
	case "usage":
		$trashbin = isset($_GET['trashbin'])?$_GET['trashbin']:false;
		$result = \OCA\Files_Accounting\Storage_Lib::getLocalUsage($userid, $trashbin);
		break;
	case "quotas":
		$quota = \OC_Preferences::getValue($userid, 'files', 'quota');
		$freeQuota = \OC_Preferences::getValue($userid, 'files_accounting', 'freequota');
		$result = array('quota'=>$quota, 'freequota'=>$freeQuota);
		break;
	case "defaultQuotas":
		$defaultQuota = OC_Appconfig::getValue('files', 'default_quota');
		$defaultFreeQuota = OC_Appconfig::getValue('files_accounting', 'default_freequota');
		$result = array('default_quota'=>$defaultQuota, 'default_freequota'=>$defaultFreeQuota);
		break;
}
OCP\JSON::encodedPrint($result);
