<?php

OCP\User::checkLoggedIn();
OCP\App::checkAppEnabled('files_accounting');

$username = OC_User::getUser();
$link = isset($_GET['link']) ? $_GET['link'] : null;

if(isset($link)){
	OCA\Files_Accounting\Storage_Lib::downloadInvoice($link, $username);
}

