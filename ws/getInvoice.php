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
//OCA\Files_Accounting\Util::dbDownloadInvoice($filename, $user);
//OC_Files::get('/tank/data/owncloud/s141277@student.dtu.dk/', array($filename), $_SERVER['REQUEST_METHOD'] == 'HEAD');
//$filename = json_decode($filename);
$format = str_replace(array('/', '\\'), '', $filename);
$file = "/tank/data/owncloud/" . $user . "/" . $format;
if(!file_exists($file)) die("I'm sorry, the file doesn't seem to exist.");
$type = filetype($file);
header("Content-type: $type");
header("Content-Disposition: attachment;filename=$filename");
readfile($file);

//$file = json_decode($ret);
//\OCP\Util::writeLog('FILES_ACCOUNTING', 'DOWNLOAD INVOICE '.$ret, \OC_Log::WARN);
//OCP\JSON::encodedPrint($ret);
