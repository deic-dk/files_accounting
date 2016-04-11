<?php

$user = \OCP\User::getUser();
$year = isset($_GET['year'])?$_GET['year']:date('Y');
$data = \OCA\Files_Accounting\Storage_Lib::localUsageData($user, $year);
OCP\JSON::success(array('data' => $data));
