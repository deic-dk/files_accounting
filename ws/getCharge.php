<?php
 
OCP\JSON::checkAppEnabled('files_accounting');
OCP\JSON::checkAppEnabled('files_sharding');
 
if(!OCA\FilesSharding\Lib::checkIP()){
        http_response_code(401);
        exit;
}

$serverId = isset($_GET['server_id'])?$_GET['server_id']:null;
$ret = OCA\Files_Accounting\Storage_Lib::dbGetCharge($serverId);
OCP\JSON::encodedPrint($ret);

