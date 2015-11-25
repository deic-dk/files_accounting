<?php

OCP\JSON::checkAppEnabled('files_accounting');
OCP\JSON::checkAppEnabled('files_sharding');

if(!OCA\FilesSharding\Lib::checkIP()){
	http_response_code(401);
	exit;
}
$data = array();
$data['txn_id'] = isset($_GET['txn_id'])?$_GET['txn_id']:null;
$data['item_number'] = isset($_GET['item_number'])?$_GET['item_number']:null;
$data['payment_amount'] = isset($_GET['payment_amount'])?$_GET['payment_amount']:null;
$data['payment_status'] = isset($_GET['payment_status'])?$_GET['payment_status']:null; 
$ret = OCA\Files_Accounting\Util::dbUpdatePayments($data);
OCP\JSON::encodedPrint($ret);

