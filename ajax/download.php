<?php


  OCP\User::checkLoggedIn();
  OCP\App::checkAppEnabled('files_accounting');

  $username = OC_User::getUser();
  $action = isset($_GET['action']) ? $_GET['action'] : null;
  $link = isset($_GET['link']) ? $_GET['link'] : null;

  if  ($action == "downloadhistory") {
        $dailyStorage = 'diskUsageDaily2015.txt';
        $file = OCA\Files_Accounting\Util::downloadInvoice($dailyStorage, $username);
        OCA\Files_Accounting\Util::readFile($file, $dailyStorage);
  }elseif (isset($link)) {
        $file = OCA\Files_Accounting\Util::downloadInvoice($link, $username);
        OCA\Files_Accounting\Util::readfile($file, $link);
   }

