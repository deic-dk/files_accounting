<?php

OC_JSON::checkSubAdminUser();
OCP\JSON::callCheck();

$user_id = isset($_POST["user_id"])?$_POST["user_id"]:'';
$group = isset($_POST["group"])?$_POST["group"];
$default = isset($_POST["default"])?$_POST["default"];

$current_user = OC_User::getUser();

if($user_id==='' && !OC_User::isAdminUser($current_user) ||
	!OC_User::isAdminUser($current_user) && !OC_SubAdmin::isUserAccessible($current_user, $user_id)){
	$l = OC_L10N::get('files_accounting');
	OC_JSON::error(array('message' => $l->t('Authentication error')));
	exit();
}

// Make sure the quota is in the expected format. We expect human format.
$freeQuota = $_POST["freequota"];
if(isset($freeQuota) && $freeQuota!=='none' && $freeQuota!=='default') {
	$freeQuota = OC_Helper::computerFileSize($freeQuota);
	$freeQuota = OC_Helper::humanFileSize($freeQuota);
}
else{
	$freeQuota = null;
}

if($user_id){
	if(isset($freeQuota)){
		if($freeQuota==0){
			OC_Preferences::deleteKey($user_id, 'files_accounting', 'freequota');
		}
		else{
			OC_Preferences::setValue($user_id, 'files_accounting', 'freequota', $freeQuota);
		}
	}
}
elseif(!empty($group) && isset($freeQuota)){
	// This will only be called by admin on master, no need for ws
	if(\OCP\App::isEnabled('user_group_admin') && \OCA\FilesSharding\Lib::isMaster()){
		OC_User_Group_Admin_Util::dbSetUserFreeQuota($group, $freeQuota);
	}
}
elseif(!empty($default) && $default == 'yes' && isset($freeQuota)){
	// This will only be called by admin on master, no need for ws
	$defaultFreeQuota = OC_Appconfig::getValue('files_accounting', 'default_freequota');
	// Set default quota
	if($freeQuota === 'default') {//'default' as default quota makes no sense
		$freeQuota = '';
	}
	if($freeQuota!=$defaultFreeQuota){
		OC_Appconfig::setValue('files_accounting', 'default_freequota', $freeQuota);
	}
}


$ret = array('freequota' => $freeQuota);

OCP\JSON::encodedPrint($ret);

