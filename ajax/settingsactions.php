<?php
OC_Util::checkAdminUser();
OCP\JSON::callCheck();
OCP\JSON::checkAppEnabled('files_accounting');

if($_POST['action'] == "addcharge") {
	$charge= $_POST['charges'];
	$taxes = $_POST['taxes'];
	$url = $_POST['url'];
	$gift = $_POST['gift'];
        \OCP\Config::setAppValue('files_accounting', 'dkr_perGb', $charge);
	\OCP\Config::setAppValue('files_accounting', 'tax', $taxes);
	\OCP\Config::setAppValue('files_accounting', 'url', $url);
	$users = OC_User::getUsers();
        foreach ($users as $user) {
                OC_Preferences::setValue($user, 'files_accounting', 'freequota', $gift);
                OCP\JSON::success();
        }

} else if ($_POST['action'] == "adduser"){
	if ( isset($_POST['user'])) {
		$gift = $_POST['gift'];
		//\OCA\Files_Accounting\Util::updatePreferences($_POST['user'], $gift);
		//OCP\JSON::success();
	}		
}

//} else if ($_POST['action'] == "addfreequota"){
	//$users = OC\User\getUsers();
	//$gift = $_POST['gift'];
	//foreach ($users as $user) {
		//OC_Preferences::setValue($user, 'files_accounting', 'freequota', $gift);
		//OCP\JSON::success();
	//}
}

