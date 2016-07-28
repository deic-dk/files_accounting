<?php
OCP\JSON::checkAppEnabled('files_accounting');
OCP\JSON::checkAppEnabled('files_sharding');
if(!OCA\FilesSharding\Lib::checkIP()){
	http_response_code(401);
	exit;
}
$action = isset($_GET['action'])?$_GET['action']:null;
$user = isset($_GET['userid'])?$_GET['userid']:'';
switch ($action) {
	case "setPreapprovalKey":
		$preapprovalKey = isset($_GET['preapproval_key'])?$_GET['preapproval_key']:'';
		$expiration = isset($_GET['expiration'])?$_GET['expiration']:'';
		$result = \OCA\Files_Accounting\Storage_Lib::dbSetPreapprovalKey($user, $preapprovalKey, $expiration);
		break;
	case "getPreapprovalKey":
		$month = isset($_GET['month'])?$_GET['month']:'';
		$year = isset($_GET['year'])?$_GET['year']:'';
		$result = \OCA\Files_Accounting\Storage_Lib::dbGetPreapprovalKey($user, $month, $year);
		break;
}
OCP\JSON::encodedPrint($result);
