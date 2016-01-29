<?php

namespace OCA\Files_Accounting;

use \OC_DB;

class Storage_Lib {
	
	public static function getChargeForUserServers($userid){
		//todo
		//extract server id	
		$userServer = \OCA\FilesSharding\Lib::ws('get_user_server', Array('user_id' => $user), false, true);
		$charge = self::getChargeForServer($userServerId);
		
	}
	//todo
	//move this to files_sharding instead
	//should be integrated with ws
	public static function getChargeForServer($serverid) {
		$query = \OC_DB::prepare('SELECT `charge_per_gb` FROM `*PREFIX*files_sharding_servers` WHERE `id` = ?');
		$result = $query->execute(Array($serverid));
		$charge = $result->fetchRow();
		return $charge;	
	}

	/*
	 * Calculate storage in all servers for a user
	 */
	public static function dailyUsage($userid, $year) {
                if(\OCP\App::isEnabled('files_sharding')){
                	if(\OCA\FilesSharding\Lib::isMaster()){
                		$backupServerId = \OCA\FilesSharding\Lib::dbLookupServerIdForUser($userid, 1);
                		if(!empty($backupServerId)){
                			$backupServerUrl = \OCA\FilesSharding\Lib::dbLookupInternalServerURL($backupServerId);
                			$dailyUsageBackupInfo = \OCA\FilesSharding\Lib::ws('dailyUsage', array('userid'=>$userid, 'year'=>$year),
                					false, true, $backupServerUrl, 'files_accounting');
                		}
                	}
                  else{
                        $dailyUsageBackupInfo = \OCA\FilesSharding\Lib::ws('dailyUsage', array('userid'=>$userid, 'year'=>$year),
                                 false, true, null, 'files_accounting');
                	}
                }
                $dailyUsageInfo = \OCA\Files_Accounting\Util::dbDailyUsage($userid, $year);
                $dailyUsage = empty($dailyUsageInfo)?array():array($dailyUsageInfo[0]);
                for ($key = 0; $key < count($dailyUsageInfo)-1; $key++) {
                        $dailyUsage[$key+1] = $dailyUsageInfo[$key+1] +
                        (!empty($dailyUsageBackupInfo)&&isset($dailyUsageBackupInfo[$key+1])?$dailyUsageBackupInfo[$key+1]:0);
                }
                return $dailyUsage;

        }
}
