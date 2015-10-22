<?php

OCP\JSON::checkLoggedIn();
OCP\JSON::checkAppEnabled('files_accounting');
OCP\JSON::callCheck();

if (isset($_GET['search'])) {
    $shareWith = array();
    $count = 0;
    $groups = array();
    $limit = 0;
    $offset = 0;
    while ($count < 4 && count($groups) == $limit) {
        $limit = 4 - $count;
        $groups = \OCA\Files_Accounting\Util::getDefaultGroups($_GET['search'], $limit, $offset);
        $offset += $limit;
        foreach ($groups as $group => $name) {
            if ((!isset($_GET['itemShares']) || !is_array($_GET['itemShares'][OCP\Share::SHARE_TYPE_USER]) || !in_array($group, $_GET['itemShares'][OCP\Share::SHARE_TYPE_USER]))) {
                $shareWith[] = array('label' => $name, 'value' => array('shareType' => OCP\Share::SHARE_TYPE_USER, 'shareWith' => $name));
                $count++;
            }
        }
    }
    OC_JSON::success(array('data' => $shareWith));
}

