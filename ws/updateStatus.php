<?php

OCP\JSON::checkAppEnabled('files_accounting');
OCP\JSON::checkAppEnabled('files_sharding');

if(!OCA\FilesSharding\Lib::checkIP()){
        http_response_code(401);
        exit;
}
$id = !empty($_GET['id'])?$_GET['id']:null;
$status = !empty($_GET['status'])?$_GET['status']:null;
$ret = OCA\Files_Accounting\Storage_Lib::dbUpdateStatus($id, $status);
OCP\JSON::encodedPrint($ret);

