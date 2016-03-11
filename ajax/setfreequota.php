<?php

OC_JSON::checkSubAdminUser();
OCP\JSON::callCheck();

$username = isset($_POST["username"])?$_POST["username"]:'';

if(($username === '' && !OC_User::isAdminUser(OC_User::getUser()))
	|| (!OC_User::isAdminUser(OC_User::getUser())
		&& !OC_SubAdmin::isUserAccessible(OC_User::getUser(), $username))) {
	$l = OC_L10N::get('core');
	OC_JSON::error(array( 'data' => array( 'message' => $l->t('Authentication error') )));
	exit();
}

//make sure the quota is in the expected format
$freeQuota=$_POST["freequota"];
if($freeQuota !== 'none' and $freeQuota !== 'default') {
	$freeQuota= OC_Helper::computerFileSize($freeQuota);
	$freeQuota=OC_Helper::humanFileSize($freeQuota);
}
//todo
//check for previous free quota
// Return Success story
if($username) {
	OC_Preferences::setValue($username, 'files_accounting', 'freequota', $freeQuota);
}else{//set the default quota when no username is specified
	if($freeQuota === 'default') {//'default' as default quota makes no sense
		$freeQuota='none';
	}
}
OC_JSON::success(array("data" => array( "username" => $username , 'freequota' => $freeQuota)));

