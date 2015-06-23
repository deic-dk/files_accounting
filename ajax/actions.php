<?php

OCP\JSON::checkLoggedIn();
OCP\JSON::checkAppEnabled('files_accounting');
OCP\JSON::callCheck();

if ($_POST['action'] == 'loadhistory') {
	$tmpl = new OCP\Template("files_accounting", "history");
        $page = $tmpl->fetchPage();
        OCP\JSON::success(array('data' => array('page'=>$page)));
}

