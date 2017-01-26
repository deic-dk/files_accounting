<?php

OCP\JSON::checkAppEnabled('files_accounting');
OCP\JSON::checkAppEnabled('files_sharding');

if(!OCA\FilesSharding\Lib::checkIP()){
        http_response_code(401);
        exit;
}

$user =$_GET['user'];
$amount = isset($_GET['amount'])?$_GET['amount']:'';

$ret = \OCA\Files_Accounting\Storage_Lib::dbSetPrePaid($user, $amount);

if($ret){
	OCP\JSON::success();
}
else{
	OCP\JSON::error();
}

