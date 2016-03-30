<?php

OCP\JSON::checkLoggedIn();
OCP\JSON::checkAppEnabled('files_accounting');
OCP\JSON::callCheck();

$action = isset($_GET['action'])?$_GET['action']:null;
$server_url = isset($_GET['server_url'])?$_GET['server_url']:null;
if ($action == 'loadhistory') {
	$tmpl = new OCP\Template("files_accounting", "history");
	$page = $tmpl->fetchPage();
	OCP\JSON::success(array('data' => array('page'=>$page)));

}
else if ($action == 'checkmaster') {
	if (\OCP\App::isEnabled('files_sharding')) {
		$master = \OCA\FilesSharding\Lib::isMaster();	
	}
	else{
		$master = true;
	}
	OCP\JSON::success(array('data' => $master));
}
