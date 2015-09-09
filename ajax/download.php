<?php

	
  OCP\User::checkLoggedIn();
  OCP\App::checkAppEnabled('files_accounting');

  $username = OC_User::getUser();
  $action = isset($_GET['action']) ? $_GET['action'] : null;
  $link = isset($_GET['link']) ? $_GET['link'] : null;

  function downloadFile($filename, $username) {
	$format = str_replace(array('/', '\\'), '', $filename);
	$file = "/tank/data/owncloud/" . $username . "/" . $format;
	if(!file_exists($file)) die("I'm sorry, the file doesn't seem to exist.");

    $type = filetype($file);
    header("Content-type: $type");
    header("Content-Disposition: attachment;filename=$filename");
    readfile($file);
  }
  if  ($action == "downloadhistory") {
	$dailyStorage = 'diskUsageDaily2015.txt';
	downloadFile($dailyStorage, $username);
  //foreach ($lines as $line) {
    //$userRows = explode(" ", $line);
    //$arr = array($userRows[1], $userRows[2], $userRows[3]);
    //$rows[] = join(" ", $arr); 
  //}
  //OCP\JSON::success(implode("\n",$rows));
  }elseif (isset($link)) { 	
	downloadFile($link, $username);
   }

