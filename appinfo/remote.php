<?php

OCP\App::checkAppEnabled('files_accounting');
OCP\App::checkAppEnabled('chooser');

$billsDir = '/'.$_SERVER['PHP_AUTH_USER']."/files_accounting";
$_SERVER['BASE_DIR'] = $billsDir;
$_SERVER['BASE_URI'] = "/remote.php/bills";

include('chooser/appinfo/remote.php');

