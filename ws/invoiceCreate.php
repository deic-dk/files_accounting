<?php

OCP\JSON::checkAppEnabled('files_accounting');
OCP\JSON::checkAppEnabled('files_sharding');

if(!OCA\FilesSharding\Lib::checkIP()){
        http_response_code(401);
        exit;
}
$userid = isset($_GET['userid'])?$_GET['userid']:null;
$params = isset($_GET['params'])?$_GET['params']:null;
$ret = OCA\Files_Accounting\ActivityHooks::dbInvoiceCreate($userid, $params);
OCP\JSON::encodedPrint($ret);

