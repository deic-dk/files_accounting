<?php

namespace OCA\Files_Accounting;

use \OC_DB;

class Storage_Lib {
	
	public static function getChargeForUserServers($userid){
		//todo
		//call this function in stats.php instead of appconfig
		if(!\OCP\App::isEnabled('files_sharding') || \OCA\FilesSharding\Lib::isMaster()){
			$homeServerId = \OCA\FilesSharding\Lib::dbLookupServerIdForUser($userid, 0);
			$backupServerId = \OCA\FilesSharding\Lib::dbLookupServerIdForUser($userid, 1);
			$chargeHome = self::getChargeFromDb(isset($homeServerId)?$homeServerId:null);
			$chargeBackup = self::getChargeFromDb(isset($backupServerId)?$backupServerId:null);
		}else {
			$serverNameHome = $_SERVER['SERVER_NAME'];
			$chargeHome = self::getChargeForServer($serverNameHome);
			$serverNameBackup = \OCA\FilesSharding\Lib::getMasterHostName();
			$chargeBackup = self::getChargeForServer($serverNameBackup);
		}
		
		$totalCharge = Array('home' => isset($chargeHome)?$chargeHome:null,
				 'backup' => isset($chargeBackup)?$chargeBackup:null);
		return $totalCharge;
	}

	public static function getChargeForServer($hostname) {
		$serverInfo = \OCA\FilesSharding\Lib::ws('get_server_id', Array('hostname' => $hostname), false, true);
                if ($serverInfo['status'] == 'success'){
        	        $serverId = $serverInfo['id'];
               	 	$charge = \OCA\FilesSharding\Lib::ws('getCharge', Array('serverid' => $serverId),
                              false, true, null, 'files_accounting');
			return $charge;
                }else {
			return null;
		}
	}

	//todo
	//move this to files_sharding instead
	public static function getChargeFromDb($serverid) {
		if (isset($serverid)) {
			$query = \OC_DB::prepare('SELECT `charge_per_gb` FROM `*PREFIX*files_sharding_servers` WHERE `id` = ?');
			$result = $query->execute(Array($serverid));
			$charge = $result->fetchRow();
			return $charge;	
		}else {
			return null;
		}
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
		//calculate the daily usage on the home server from the text file
                $dailyUsageInfo = \OCA\Files_Accounting\Util::dbDailyUsage($userid, $year);
                $dailyUsage = empty($dailyUsageInfo)?array():array($dailyUsageInfo[0]);
                for ($key = 0; $key < count($dailyUsageInfo)-1; $key++) {
                        $dailyUsage[$key+1] = $dailyUsageInfo[$key+1] +
                        (!empty($dailyUsageBackupInfo)&&isset($dailyUsageBackupInfo[$key+1])?$dailyUsageBackupInfo[$key+1]:0);
                }
                return $dailyUsage;
        }

}
