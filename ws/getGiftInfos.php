<?php

OCP\JSON::checkAppEnabled('files_accounting');
OCP\JSON::checkAppEnabled('files_sharding');

if(!OCA\FilesSharding\Lib::checkIP()){
        http_response_code(401);
        exit;
}

$code = empty($_GET['code'])?'':$_GET['code'];

$ret = OCA\Files_Accounting\Storage_Lib::dbGetPrePaid($code);

OCP\JSON::encodedPrint($ret);

