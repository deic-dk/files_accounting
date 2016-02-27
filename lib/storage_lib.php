<?php

namespace OCA\Files_Accounting;

use \OC_DB;

class Storage_Lib {
	
	public static function getServerNamesForInvoice($userid){
		$homeServerUrl = 'https://'.$_SERVER['SERVER_NAME'];
		if(!\OCP\App::isEnabled('files_sharding') || \OCA\FilesSharding\Lib::isMaster()){
			$homeServerSite = \OCA\FilesSharding\Lib::dbGetSite(null);
			$backupServerId = \OCA\FilesSharding\Lib::dbLookupServerIdForUser($userid, 1);
			$backupServerUrl = \OCA\FilesSharding\Lib::dbLookupServerURL($backupServerId);
			$backupServerSite = \OCA\FilesSharding\Lib::dbGetSite($backupServerId);
		}else{
			//todo
			//maybe create a new ws script for url/site instead of loading the list
			$serversList = \OCA\FilesSharding\Lib::ws('get_servers', Array(), true, true);
			$backupServerUrl = 'https://'.\OCA\FilesSharding\Lib::getMasterHostName();
		 	foreach ($serversList as $server){	
				if( strpos($homeServerUrl, $server['url']) !== false ){
					$homeServerSite = $server['site'];
				}
				else if (strpos($backupServerUrl, $server['url']) !== false ){
					$backupServerSite = $server['url'];
				}
			}
		}
		$serverNames = Array("home"=> Array($homeServerUrl, isset($homeServerSite)?$homeServerSite:null));
		if (isset($backupServerUrl) && isset($backupServerSite)){
			$serverNames["backup"] = Array($backupServerUrl, $backupServerSite);
		}
		return $serverNames;
			
	}

	public static function getChargeForUserServers($userid){
		if(!\OCP\App::isEnabled('files_sharding') || \OCA\FilesSharding\Lib::isMaster()){
			$homeServerId = \OCA\FilesSharding\Lib::lookupServerIdForUser($userid);
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
		\OCP\Util::writeLog('Files_Accounting', 'CHARGE HOME: '.$totalCharge['home'].' BACKUP: '.$totalCharge['backup'], \OCP\Util::ERROR);
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
			foreach($charge as $row){
				return $row['charge_per_gb'];
			}
		}else {
			return null;
		}
	}

	/*
	 * Calculate storage in all servers for a user
	 */
	public static function dailyUsage($userid, $monthToSave, $year) {
		if(\OCP\App::isEnabled('files_sharding')){
                	if(\OCA\FilesSharding\Lib::isMaster()){
                 		$backupServerId = \OCA\FilesSharding\Lib::dbLookupServerIdForUser($userid, 1);
                 		if(!empty($backupServerId)){
                 			$backupServerUrl = \OCA\FilesSharding\Lib::dbLookupInternalServerURL($backupServerId);
                 			$dailyUsageBackupInfo = \OCA\FilesSharding\Lib::ws('dailyUsage', array('userid'=>$userid, 'month'=>$monthToSave, 'year'=>$year),
                 					false, true, $backupServerUrl, 'files_accounting');
                 		}
                 	}
                 	else{
                        	$dailyUsageBackupInfo = \OCA\FilesSharding\Lib::ws('dailyUsage', array('userid'=>$userid, 'month'=>$monthToSave, 'year'=>$year),
                                 	false, true, null, 'files_accounting');
			}
		}
		//calculate the daily usage on the home server from the text file
                $dailyUsageInfo = \OCA\Files_Accounting\Util::dbDailyUsage($userid, $monthToSave, $year);
		$dailyUsageTotal = array(!empty($dailyUsageInfo)?$dailyUsageInfo:null, !empty($dailyUsageBackupInfo)?$dailyUsageBackupInfo:null);
		return $dailyUsageTotal;
        }

	public static function dailyUsageSum($userid, $monthToSave, $year) {
		$dailyUsageTotal = self::dailyUsage($userid, $monthToSave, $year);
		$dailyUsage = empty($dailyUsageTotal[0])?array():array($dailyUsageTotal[0][0]);
                for ($key = 0; $key < count($dailyUsageTotal[0])-1; $key++) {
                        $dailyUsage[$key+1] = $dailyUsageTotal[0][$key+1] +
                        (!empty($dailyUsageTotal[1][$key+1])&&isset($dailyUsageTotal[1][$key+1])?$dailyUsageTotal[1][$key+1]:0);
                }
                return $dailyUsage; 
	}

	public static function dbUserStorage($userid) {
		$storageInfo = \OC_Helper::getStorageInfo('/');
                $usedStorage = $storageInfo['used'];
                $trashbinStorage = self::trashbinSize($userid);
                $totalStorage = $usedStorage + $trashbinStorage;

                return $totalStorage;
	}

	public static function userStorage($userid){
		if(\OCP\App::isEnabled('files_sharding')){
                        if(\OCA\FilesSharding\Lib::isMaster()){
				$backupServerId = \OCA\FilesSharding\Lib::dbLookupServerIdForUser($userid, 1);
                                if(!empty($backupServerId)){
                                        $backupServerUrl = \OCA\FilesSharding\Lib::dbLookupInternalServerURL($backupServerId);
					\OCP\Util::writeLog('Files_Accounting', 'backup SERVER_id: '.$backupServerUrl, \OCP\Util::ERROR);
                                        $userStorageBackup = \OCA\FilesSharding\Lib::ws('actionsPersonal', array('userid'=>$userid, 
							'action'=>'userStorage'),false, true, 
							$backupServerUrl, 'files_accounting');
                                }
			}
			else{
                                $userStorageBackup = \OCA\FilesSharding\Lib::ws('actionsPersonal',
							 array('userid'=>$userid, 'action'=>'userStorage'),
							 false, true, null, 'files_accounting');
                        }
                }
	 	$userStorageHome = self::dbUserStorage($userid);
		$userStorageTotal = $userStorageHome + (isset($userStorageBackup)?$userStorageBackup:0); 
		$userStorageTotalHuman = \OC_Helper::HumanFileSize($userStorageTotal);
		return [$userStorageTotalHuman, $userStorageTotal];
			
	}

	public static function trashbinSize($user) {
		$view = new \OC\Files\View('/' . $user);
		$fileInfo = $view->getFileInfo('/files_trashbin/files');
		return isset($fileInfo['size']) ? $fileInfo['size'] : 0; 
	}
	
	public static function dbFreeSpace($user) {
		$free_space = \OC_Preferences::getValue($user, 'files_accounting', 'freequota');
		if (isset($free_space)) {
			$free_space_real = \OC_Helper::computerFileSize($free_space);

			return [$free_space, $free_space_real];
		}else {
			return null;
		}
	}

	public static function relativeSpace($user, $total_used) {
		$storageInfo = \OC_Helper::getStorageInfo('/');
		$total_storage = $storageInfo['total'];
		$free_quota = self::freeSpace($user);
		
		if (isset($free_quota)) {
			$free_quota = $free_quota[1];
			$relative = round(($total_used / $free_quota) * 10000) / 100;		
		}else {
			$relative = round(($total_used / $total_storage) * 10000) / 100;
		}

		return $relative;
	}


}
