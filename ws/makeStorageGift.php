<?php

OCP\JSON::checkAppEnabled('files_accounting');
OCP\JSON::checkAppEnabled('files_sharding');

if(!OCA\FilesSharding\Lib::checkIP()){
        http_response_code(401);
        exit;
}

$size = isset($_POST["size"])?$_POST["size"]:"";
$site = isset($_POST["site"])?$_POST["site"]:"";
$days = isset($_POST["days"])?$_POST["days"]:"";
$claimExpiresDays = isset($_POST["expires"])?$_POST["expires"]:"";
$suffix = isset($_POST["suffix"])?$_POST["suffix"]:"";

$ret = \OCA\Files_Accounting\Storage_Lib::dbMakeStorageGift($size, $site, $days, $claimExpires, $suffix);

if($ret){
	OCP\JSON::success();
}
else{
	OCP\JSON::error();
}

