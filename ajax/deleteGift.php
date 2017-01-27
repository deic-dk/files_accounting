<?php

OCP\JSON::checkAdminUser();
OCP\JSON::callCheck();

$code = isset($_POST["code"])?$_POST["code"]:"";

$ret = false;

if(!empty($code)){
	$ret = \OCA\Files_Accounting\Storage_Lib::dbDeleteGift($code);
}

//OCP\JSON::encodedPrint($ret);

if($ret){
	OCP\JSON::success();
}
else{
	OCP\JSON::error();
}