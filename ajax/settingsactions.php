<?php
OC_Util::checkAdminUser();
OCP\JSON::callCheck();
$charge= $_POST['charges']; 
$taxes = $_POST['taxes'];
$url = $_POST['url'];	
if($_POST['action'] == "addcharge") {
        \OCP\Config::setAppValue('files_accounting', 'dkr_perGb', $charge);
	\OCP\Config::setAppValue('files_accounting', 'tax', $taxes);
	\OCP\Config::setAppValue('files_accounting', 'url', $url);
}

