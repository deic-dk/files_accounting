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
                if(!\OCP\App::isEnabled('files_sharding') || \OCA\FilesSharding\Lib::isMaster()){
                        $backupServerId = \OCA\FilesSharding\Lib::dbLookupServerIdForUser($userid, 1);
                        if(!empty($backupServerId)){
                                $backupServerUrl = self::dbLookupInternalServerURL($backupServerId);
                                $dailyUsageBackupInfo = \OCA\FilesSharding\Lib::ws('dailyUsage', array('userid'=>$userid, 'year'=>$year),
                                                        false, true, $backupServerUrl, 'files_accounting');
                        }
                }
                else{
                        $dailyUsageBackupInfo = \OCA\FilesSharding\Lib::ws('dailyUsage', array('userid'=>$userid, 'year'=>$year),
                                 false, true, null, 'files_accounting');
                }
                $dailyUsageInfo = \OCA\Files_Accounting\Util::dbDailyUsage($userid, $year);
                $dailyUsage = array($dailyUsageInfo[0]);
                for ($key = 0; $key < count($dailyUsageInfo)-1; $key++) {
                        $dailyUsage[$key+1] = $dailyUsageInfo[$key+1] + $dailyUsageBackupInfo[$key+1];
                }
                return $dailyUsage;

        }

	//This is a copy from lib_files_sharding
	//todo
	private static function dbLookupInternalServerURL($id){
		$query = \OC_DB::prepare('SELECT `internal_url` FROM `*PREFIX*files_sharding_servers` WHERE `id` = ?');
		$result = $query->execute(Array($id));
		if(\OCP\DB::isError($result)){
			\OCP\Util::writeLog('files_sharding', \OC_DB::getErrorMessage($result), \OC_Log::ERROR);
		}
		$results = $result->fetchAll();
		if(count($results)>1){
			\OCP\Util::writeLog('files_sharding', 'ERROR: Duplicate entries found for server '.$id, \OCP\Util::ERROR);
		}
		foreach($results as $row){
			return($row['internal_url']);
		}
		\OCP\Util::writeLog('files_sharding', 'ERROR: ID not found: '.$id, \OC_Log::ERROR);
		return null;
	}

}
