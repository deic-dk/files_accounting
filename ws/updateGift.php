<?php

OCP\JSON::checkAppEnabled('files_accounting');
OCP\JSON::checkAppEnabled('files_sharding');

if(!OCA\FilesSharding\Lib::checkIP()){
        http_response_code(401);
        exit;
}

$code =$_POST['code'];
$status =$_POST['status'];
$user = isset($_POST['user'])?$_POST['user']:'';
$redemptionTime = isset($_POST['redemption_time'])?$_POST['redemption_time']:'';

$ret = \OCA\Files_Accounting\Storage_Lib::dbUpdateGift($code, $status, $user, $redemptionTime);

OCP\JSON::encodedPrint($ret);

