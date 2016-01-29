<?php

OCP\JSON::checkAppEnabled('files_accounting');
OCP\JSON::checkAppEnabled('files_sharding');

if(!OCA\FilesSharding\Lib::checkIP()){
	http_response_code(401);
	exit;
}
$serverid = isset($_GET['serverid'])?$_GET['serverid']:null;
$ret = \OCA\Files_Accounting\Storage_Lib::getChargeFromDb($serverid);
OCP\JSON::encodedPrint($ret);
