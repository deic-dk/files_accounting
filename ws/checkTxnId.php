<?php

OCP\JSON::checkAppEnabled('files_accounting');
OCP\JSON::checkAppEnabled('files_sharding');

if(!OCA\FilesSharding\Lib::checkIP()){
        http_response_code(401);
        exit;
}
$txnid = isset($_GET['txnid'])?$_GET['txnid']:null;
$ret = OCA\Files_Accounting\Util::dbCheckTxnId($txnid);
OCP\JSON::encodedPrint($ret);

