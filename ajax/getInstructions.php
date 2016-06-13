<?php

OCP\JSON::checkLoggedIn();
OCP\JSON::checkAppEnabled('files_accounting');
OCP\JSON::callCheck();

$tmpl = new OCP\Template("files_accounting", "instructions");
$page = $tmpl->fetchPage();
OCP\JSON::success(array('data' => array('page'=>$page)));
