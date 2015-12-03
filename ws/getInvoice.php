<?php

OCP\JSON::checkAppEnabled('files_accounting');
OCP\JSON::checkAppEnabled('files_sharding');
 
if(!OCA\FilesSharding\Lib::checkIP()){
        http_response_code(401);
        exit;
}
$filename = isset($_GET['filename'])?$_GET['filename']:null;
$user = isset($_GET['user'])?$_GET['user']:null;
\OCP\Util::writeLog('FILES_ACCOUNTING', $filename.$user, \OC_Log::WARN);
OCA\Files_Accounting\Util::dbDownloadInvoice($filename, $user);
