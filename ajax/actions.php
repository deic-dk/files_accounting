<?php

OCP\JSON::checkLoggedIn();
OCP\JSON::checkAppEnabled('files_accounting');
OCP\JSON::callCheck();

if ($_POST['action'] == 'loadhistory') {
	$tmpl = new OCP\Template("files_accounting", "history");
        $page = $tmpl->fetchPage();
        OCP\JSON::success(array('data' => array('page'=>$page)));

}
//if ($_POST['action'] == 'loadgraph') {
//	$graph = new OCP\Template("files_accounting", "storageplot");
//	$graph_page = $graph->fetchPage();
//	OCP\JSON::success(array('data' => array('page'=>$graph_page)));
//}

if ($_POST['action'] == 'downloadhistory') {
  $username = OC_User::getUser();
  $format = str_replace(array('/', '\\'), '', 'diskUsageDaily2015');
  $lines = file ("/tank/data/owncloud/" . $username . "/" . $format . ".txt");
  foreach ($lines as $line) {
    $userRows = explode(" ", $line);
    $arr = array($userRows[1], $userRows[2], $userRows[3]);
    $rows[] = join(" ", $arr); 
  }

  OCP\JSON::success(implode("\n",$rows));
}

