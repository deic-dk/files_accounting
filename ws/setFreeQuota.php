<?php

OCP\JSON::checkAppEnabled('files_accounting');
OCP\JSON::checkAppEnabled('files_sharding');

if(!OCA\FilesSharding\Lib::checkIP()){
        http_response_code(401);
        exit;
}

$user =$_GET['user'];
$freeQuota = isset($_GET['freequota'])?$_GET['freequota']:'';

$ret = \OCA\Files_Accounting\Storage_Lib::dbSetFreeQuota($user, $freeQuota);

if($ret){
	OCP\JSON::success();
}
else{
	OCP\JSON::error();
}

