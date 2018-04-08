<?php

OCP\JSON::checkAppEnabled('files_accounting');
OCP\JSON::checkAppEnabled('files_sharding');

if(!OCA\FilesSharding\Lib::checkIP()){
	http_response_code(401);
	exit;
}
$reference_id = isset($_GET['reference_id'])?$_GET['reference_id']:'';
$ret = OCA\Files_Accounting\Storage_Lib::dbGetBill($reference_id);
OCP\JSON::encodedPrint($ret);

