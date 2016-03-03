<?php

namespace OCA\Files_Accounting;

use \OCP\User;

class Free_Quota extends \OC\BackgroundJob\QueuedJob{

	protected function run($argument) {
		$quota_update = $this->getFreeQuota();
	}

	public function getFreeQuota() {
		if(!\OCP\App::isEnabled('files_sharding') || \OCA\FilesSharding\Lib::isMaster()){
			if(User::isAdminUser(User::getUser())){
				$users = User::getUsers();
			}
			else{
				$users = array(User::getUser());
			}
			foreach ($users as $user) {
				list($total_usage,$total_usage_real) = \OCA\Files_Accounting\Storage_Lib::userStorage($user);
				list($free_space, $free_space_real) = \PersonalUtil::freeSpace($user);
				if (isset($free_space)){
					if ($free_space_real < $total_usage_real) {
						\OCA\Files_Accounting\Util::addNotification($user, $free_space);
					}
				}
			}
		}
	}
}
