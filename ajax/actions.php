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

}else if ($action == 'getserver') {
	$server = OCA\Files_Accounting\Util::getServerUrl($server_url);	
	OCP\JSON::success(array('data' => array('url'=>$server)));
}
//else if ($action == 'loadgraph') {
	//$graph = new OCP\Template("files_accounting", "storageplot");
	//$graph_page = $graph->fetchPage();
//	OCP\JSON::success(array('data' => array('page'=>$graph_page)));
//}

