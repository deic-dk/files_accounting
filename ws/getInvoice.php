<?php

OCP\JSON::checkAppEnabled('files_accounting');
OCP\JSON::checkAppEnabled('files_sharding');
 
if(!OCA\FilesSharding\Lib::checkIP()){
        http_response_code(401);
        exit;
}
$filename = isset($_GET['filename'])?$_GET['filename']:null;
$user = isset($_GET['user'])?$_GET['user']:null;
$ret = OCA\Files_Accounting\Util::dbDownloadInvoice($filename, $user);
OCP\JSON::encodedPrint($ret);
