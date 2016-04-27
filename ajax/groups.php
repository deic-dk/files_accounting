<?php

OCP\JSON::checkLoggedIn();
OCP\JSON::checkAppEnabled('files_accounting');
OCP\JSON::callCheck();

$data = array();
if(isset($_GET['search'])){
	$groups = array();
	$count = 0;
	$limit = 0;
	$offset = 0;
	while ($count < 4 && count($groups) == $limit) {
		$limit = 4 - $count;
		$groups = OC_Group::getGroups($_GET['search'].'%', $limit, $offset);
		$offset += $limit;
		foreach($groups as $group){
			$data[] = $group;
			$count++;
		}
	}
	/*if(OCP\App::isEnabled('user_group_admin')){
		$count = 0;
		$limit = 0;
		$offset = 0;
		$groups = OC_User_Group_Admin_Util::getGroups($_GET['search'].'%', $limit, $offset);
		while ($count < 4 && count($groups) == $limit) {
			$limit = 4 - $count;
			$groups = OC_Group::getGroups($_GET['search'].'%', $limit, $offset);
			$offset += $limit;
			foreach($groups as $group){
				$data[] = $group;
				$count++;
			}
		}
	}*/
	
	OC_JSON::success(array('data' => $data));
}
else{
	OC_JSON::error(array('data' => $data));
}
