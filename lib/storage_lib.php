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
		$dailyUsageTotal = array(!empty($dailyUsageInfo)?$dailyUsageInfo:null, !empty($dailyUsageBackupInfo)?$dailyUsageBackupInfo:null);
		return $dailyUsageTotal;
        }

	public static function dailyUsageSum($userid, $year) {
		$dailyUsageTotal = self::dailyUsage($userid, $year);
		$dailyUsage = empty($dailyUsageTotal[0])?array():array($dailyUsageTotal[0][0]);
                for ($key = 0; $key < count($dailyUsageTotal[0])-1; $key++) {
                        $dailyUsage[$key+1] = $dailyUsageTotal[0][$key+1] +
                        (!empty($dailyUsageTotal[1][$key+1])&&isset($dailyUsageTotal[1][$key+1])?$dailyUsageTotal[1][$key+1]:0);
                }
                return $dailyUsage; 
	}

}
