<?php

OCP\JSON::checkAppEnabled('files_accounting');
OCP\JSON::checkAppEnabled('files_sharding');

if(!OCA\FilesSharding\Lib::checkIP()){
        http_response_code(401);
        exit;
}

$amount = isset($_POST["amount"])?$_POST["amount"]:"";
$claimExpiresDays = isset($_POST["expires"])?$_POST["expires"]:"";
$suffix = isset($_POST["suffix"])?$_POST["suffix"]:"";

$ret = \OCA\Files_Accounting\Storage_Lib::dbMakeStorageGift($amount, $claimExpires, $suffix);

if($ret){
	OCP\JSON::success();
}
else{
	OCP\JSON::error();
}

