<?php

OCP\User::checkLoggedIn();
OCP\App::checkAppEnabled('files_accounting');

$username = OC_User::getUser();
$action = isset($_GET['action']) ? $_GET['action'] : null;
$link = isset($_GET['link']) ? $_GET['link'] : null;

if($action == "downloadhistory"){
	$dailyStorage = 'diskUsageDaily'.date("Y").'.txt';
	OCA\Files_Accounting\Util::downloadInvoice($dailyStorage, $username);
}
elseif(isset($link)){
	OCA\Files_Accounting\Util::downloadInvoice($link, $username);
}

