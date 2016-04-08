<?php

OCP\JSON::checkAppEnabled('files_accounting');
OCP\JSON::checkAppEnabled('files_sharding');

if(!OCA\FilesSharding\Lib::checkIP()){
        http_response_code(401);
        exit;
}
$id = isset($_GET['id'])?$_GET['id']:null;
$ret = OCA\Files_Accounting\Storage_Lib::dbUpdateStatus($id);
OCP\JSON::encodedPrint($ret);

