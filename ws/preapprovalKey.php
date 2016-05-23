<?php
OCP\JSON::checkAppEnabled('files_accounting');
OCP\JSON::checkAppEnabled('files_sharding');
if(!OCA\FilesSharding\Lib::checkIP()){
	http_response_code(401);
	exit;
}
$key = isset($_GET['key'])?$_GET['key']:null;
$user = isset($_GET['userid'])?$_GET['userid']:'';
switch ($key) {
	case "setPreapprovalKey":
		$preapprovalKey = isset($_GET['preapproval_key'])?$_GET['preapproval_key']:'';
		$expiration = isset($_GET['expiration'])?$_GET['expiration']:'';
		$result = \OCA\Files_Accounting\Storage_Lib::dbSetPreapprovalKey($user, $preapprovalKey, $expiration);
		break;
	case "getPreapprovalKey":
		$amount = isset($_GET['amount'])?$_GET['amount']:'';
		$reference_id = isset($_GET['reference_id'])?$_GET['reference_id']:'';
		$result = \OCA\Files_Accounting\Storage_Lib::dbGetPreapprovalKey($user, $amount, $reference_id);
		break;
}
OCP\JSON::encodedPrint($result);
