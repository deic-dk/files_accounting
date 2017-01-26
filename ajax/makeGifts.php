<?php

OCP\JSON::checkAdminUser();
OCP\JSON::callCheck();

$codes = isset($_POST["codes"])?$_POST["codes"]:0;
$amount = isset($_POST["amount"])?$_POST["amount"]:"";
$size = isset($_POST["size"])?$_POST["size"]:"";
$site = isset($_POST["site"])?$_POST["site"]:"";
$days = isset($_POST["days"])?$_POST["days"]:"";
$claimExpiresDays = isset($_POST["expires"])?$_POST["expires"]:"";
$suffix = isset($_POST["suffix"])?$_POST["suffix"]:"";

if(!empty($claimExpiresDays)){
	$nowDate = new DateTime();
	$now = $nowDate->getTimestamp();
	$claimExpires = $now + 60*60*24*((int)$claimExpiresDays);
}
else{
	$claimExpires = "";
}

$ret = true;

for($i=0; $i<$codes; ++$i){
	if(!empty($amount)){
		$ret = $ret && \OCA\Files_Accounting\Storage_Lib::makeCreditGift($amount, $claimExpires, $suffix);
	}
	elseif(!empty($size)){
		$ret = $ret && \OCA\Files_Accounting\Storage_Lib::makeStorageGift($size, $site, $days, $claimExpires, $suffix);
	}
}

//OCP\JSON::encodedPrint($ret);

if($ret){
	OCP\JSON::success();
}
else{
	OCP\JSON::error();
}