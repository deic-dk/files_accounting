<?php

OCP\User::checkLoggedIn();
OCP\App::checkAppEnabled('files_accounting');

$user = OC_User::getUser();
$file = isset($_GET['file']) ? $_GET['file'] : null;

if(!empty($file) && !empty($user)){
	OCA\Files_Accounting\Storage_Lib::getInvoice($file, $user);
}

