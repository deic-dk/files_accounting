<?php
 
OCP\JSON::checkAppEnabled('files_accounting');
OCP\JSON::checkAppEnabled('files_sharding');
 
if(!OCA\FilesSharding\Lib::checkIP()){
        http_response_code(401);
        exit;
}
$action = isset($_GET['action'])?$_GET['action']:null;
if ($action == 'checkPrice') {
	$price = isset($_GET['price'])?$_GET['price']:null;
	$id = isset($_GET['id'])?$_GET['id']:null;
	$ret = OCA\Files_Accounting\Util::dbCheckPrice($price, $id);
} else if ($action == 'getTaxRate') {
	$ret = (float) \OCP\Config::getAppValue('files_accounting', 'tax', '');
}
OCP\JSON::encodedPrint($ret);

