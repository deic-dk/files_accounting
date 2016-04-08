<?php

OCP\JSON::checkLoggedIn();
OCP\JSON::checkAppEnabled('files_accounting');
OCP\JSON::callCheck();

$status = isset($_GET['status']) ? $_GET['status'] : null;
$year = isset($_GET['year']) ? $_GET['year'] : null;

$tmpl = new OCP\Template("files_accounting", "billing");
$tmpl->assign('status', $status);
$tmpl->assign('year', $year);
$page = $tmpl->fetchPage();

OCP\JSON::encodedPrint($page);

