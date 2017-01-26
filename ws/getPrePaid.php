<?php

OCP\JSON::checkAppEnabled('files_accounting');
OCP\JSON::checkAppEnabled('files_sharding');

if(!OCA\FilesSharding\Lib::checkIP()){
        http_response_code(401);
        exit;
}

$user = $_GET['user'];

$ret = OCA\Files_Accounting\Storage_Lib::dbGetPrePaid($user);

OCP\JSON::encodedPrint($ret);

