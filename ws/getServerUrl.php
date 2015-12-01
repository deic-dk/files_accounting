<?php
 
OCP\JSON::checkAppEnabled('files_accounting');
OCP\JSON::checkAppEnabled('files_sharding');
 
if(!OCA\FilesSharding\Lib::checkIP()){
        http_response_code(401);
        exit;
}
$link = isset($_GET['link'])?$_GET['link']:null;
$ret = OCA\Files_Accounting\Util::dbGetServerUrl($link);
OCP\JSON::encodedPrint($ret);

