<?php

namespace OCA\Files_Accounting;

use \OC_DB;

class Storage_Lib {
	
	public static function getBillingCurrency(){
		return \OCP\Config::getSystemValue('billingcurrency', 'EUR');
	}
	
	public static function getIssuerAddress(){
		return \OCP\Config::getSystemValue('fromaddress', '');
	}
	
	public static function getPayPalHostedButtonID(){
		return \OCP\Config::getSystemValue('paypalhostedbuttonid', '');
	}
	
	public static function getPayPalAccount(){
		return \OCP\Config::getSystemValue('paypalaccount', '');
	}
	
	public static function getServerNamesForInvoice($userid){
		$homeServerUrl = php_uname("n");
		$homeServerSite = $homeServerUrl;
		if(\OCP\App::isEnabled('files_sharding')){
			$homeServerUrl = \OCA\FilesSharding\Lib::dbLookupServerUrlForUser($userid);
			$homeServerId = \OCA\FilesSharding\Lib::dbLookupServerIdForUser($userid, 0);
			$homeServerSite = \OCA\FilesSharding\Lib::dbGetSite($homeServerId);
			$backupServerId = \OCA\FilesSharding\Lib::dbLookupServerIdForUser($userid, 1);
			$backupServerUrl = \OCA\FilesSharding\Lib::dbLookupServerURL($backupServerId);
			$backupServerSite = \OCA\FilesSharding\Lib::dbGetSite($backupServerId);
		}

		$serverNames = Array("home"=> Array($homeServerUrl, isset($homeServerSite)?$homeServerSite:null));
		if(isset($backupServerUrl) && isset($backupServerSite)){
			$serverNames["backup"] = Array($backupServerUrl, $backupServerSite);
		}
		return $serverNames;
			
	}
	
	public static function getInvoiceDir($user){
		\OC_User::setUserId($user);
		\OC_Util::setupFS($user);
		$fs = \OCP\Files::getStorage('files_accounting');
		if(!$fs){
			OC_Log::write('files_accounting', "ERROR, could not access files of user ".$user, OC_Log::ERROR);
			return null;
		}
		return $fs->getPath();
	}

	public static function getChargeForUserServers($userid){
		// Allow functioning without files_sharding.
		// Allow override charge set in central DB.
		$totalCharge = \OCP\Config::getSystemValue('chargepergb', 0);
		if(\OCP\App::isEnabled('files_sharding')){
			$homeServerId = \OCA\FilesSharding\Lib::dbLookupServerIdForUser($userid, 0);
			$backupServerId = \OCA\FilesSharding\Lib::dbLookupServerIdForUser($userid, 1);
			$chargeHome = empty($totalCharge)?(self::getChargeFromDb(isset($homeServerId)?$homeServerId:null)):
				$totalCharge;
			$chargeBackup = self::getChargeFromDb(isset($backupServerId)?$backupServerId:null);
			$totalCharge = Array('home' => isset($chargeHome)?$chargeHome:null,
					'backup' => isset($chargeBackup)?$chargeBackup:null);
		}
		\OCP\Util::writeLog('Files_Accounting', 'CHARGE HOME: '.$totalCharge['home'].' BACKUP: '.$totalCharge['backup'], \OCP\Util::ERROR);
		return $totalCharge;
	}

	private static function getChargeFromDb($serverid) {
		if(isset($serverid)){
			$query = \OC_DB::prepare('SELECT `charge_per_gb` FROM `*PREFIX*files_sharding_servers` WHERE `id` = ?');
			$result = $query->execute(Array($serverid));
			$charge = $result->fetchRow();
			foreach($charge as $row){
				return $row['charge_per_gb'];
			}
		}
		else{
			return \OCP\Config::getSystemValue('chargepergb', 0);
		}
	}

	public static function monthlyUsage($userid, $monthToSave, $year) {
		if(\OCP\App::isEnabled('files_sharding')){
			$masterInternalUrl = \OCA\FilesSharding\Lib::getMasterInternalURL();
			$homeInternalUrl = \OCA\FilesSharding\Lib::dbLookupInternalServerUrlForUser($userid);
			$backupServerId = \OCA\FilesSharding\Lib::dbLookupServerIdForUser($userid, 1);
		}
		if(!empty($backupServerId)){
			$backupServerInternalUrl = \OCA\FilesSharding\Lib::dbLookupInternalServerURL($backupServerId);		
		}
		if($homeInternalUrl == $masterInternalUrl) {
			$dailyUsageInfo = \OCA\Files_Accounting\Util::dbDailyUsage($userid, $monthToSave, $year);
		}
		else{
			$dailyUsageInfo = \OCA\FilesSharding\Lib::ws('dailyUsage', array('userid'=>$userid, 'month'=>$monthToSave, 'year'=>$year),
				false, true, $homeInternalUrl, 'files_accounting');
		}
		if(isset($backupServerInternalUrl)){
			$dailyUsageBackupInfo = \OCA\FilesSharding\Lib::ws('dailyUsage', array('userid'=>$userid, 'month'=>$monthToSave, 'year'=>$year),
				false, true, $backupServerInternalUrl, 'files_accounting');
		}
		$dailyUsageTotal = array(!empty($dailyUsageInfo)?$dailyUsageInfo:null,
															!empty($dailyUsageBackupInfo)?$dailyUsageBackupInfo:null);
		return $dailyUsageTotal;
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
		for($key = 0; $key < count($dailyUsageTotal[0])-1; $key++) {
			$dailyUsage[$key+1] = $dailyUsageTotal[0][$key+1] +
				(!empty($dailyUsageTotal[1][$key+1])&&isset($dailyUsageTotal[1][$key+1])?$dailyUsageTotal[1][$key+1]:0);
		}
		return $dailyUsage; 
	}

	//solution to work with ws based on
	//https://github.com/owncloud/core/issues/5740
	public static function dbUserStorage($userid, $trashbin) {
		$user = \OC::$server->getUserManager()->get($userid);
		$storage = new \OC\Files\Storage\Home(array('user'=>$user));
		$rootInfo = $storage->getCache()->get('files');
		$usedStorage =  $rootInfo['size'];
		if($trashbin){
			$trashbinStorage = self::trashbinSize($userid);
			$totalStorage = array($usedStorage, $trashbinStorage);
		}
		else{
			$totalStorage = $usedStorage;
		}
		return $totalStorage;
	}

	//todo
	//maybe delete this function
	public static function userStorage($userid){
		if(\OCP\App::isEnabled('files_sharding')){
			if(\OCA\FilesSharding\Lib::isMaster()){
				$backupServerId = \OCA\FilesSharding\Lib::dbLookupServerIdForUser($userid, 1);
				if(!empty($backupServerId)){
					$backupServerUrl = \OCA\FilesSharding\Lib::dbLookupInternalServerURL($backupServerId);
					$userStorageBackup = \OCA\FilesSharding\Lib::ws('actionsPersonal', array('userid'=>$userid, 
							'action'=>'userStorage'),false, true, $backupServerUrl, 'files_accounting');
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

	public static function personalStorage($userid) {
		if(\OCP\App::isEnabled('files_sharding')){
			if(\OCA\FilesSharding\Lib::isMaster()){
				$backupServerId = \OCA\FilesSharding\Lib::dbLookupServerIdForUser($userid, 1);
				if(!empty($backupServerId)){
					$backupServerUrl = \OCA\FilesSharding\Lib::dbLookupInternalServerURL($backupServerId);
					$userStorageBackup = \OCA\FilesSharding\Lib::ws('actionsPersonal', array('userid'=>$userid,
							'action'=>'userStorage', 'trashbin'=>false),false, true, $backupServerUrl, 'files_accounting');
					$userStorageBackupHuman = \OC_Helper::HumanFileSize($userStorageBackup);
				}
			}
			else{
				$backupServerInternalUrl = \OCA\FilesSharding\Lib::ws('actionsPersonal',
						array('userid'=>$userid, 'action'=>'backupInternalUrl'),
						false, true, null, 'files_accounting');
				if(isset($backupServerInternalUrl)){
					$userStorageBackup = \OCA\FilesSharding\Lib::ws('actionsPersonal',
							array('userid'=>$userid, 'action'=>'userStorage', 'trashbin'=>false),
							false, true, $backupServerInternalUrl, 'files_accounting');
					$userStorageBackupHuman = \OC_Helper::HumanFileSize($userStorageBackup);
				}
			}
		}
		$userStorageHome = self::dbUserStorage($userid, true);
		$userStorageHomeHuman = array(\OC_Helper::HumanFileSize($userStorageHome[0]), \OC_Helper::HumanFileSize($userStorageHome[0]));
		if(isset($userStorageBackup)){
			return [$userStorageHome, $userStorageHomeHuman, $userStorageBackup, $userStorageBackupHuman];
		}
		else{
			return [$userStorageHome, $userStorageHomeHuman, 0, 0];
		}
	}

	public static function trashbinSize($userid) {
		$user = \OC::$server->getUserManager()->get($userid);
		$storage = new \OC\Files\Storage\Home(array('user'=>$user));
		$rootInfo = $storage->getCache()->get('files_trashbin/files');
		$trashSpace =  $rootInfo['size'];
		return $trashSpace;
	}
	
	public static function freeSpace($user) {
		$free_space = \OC_Preferences::getValue($user, 'files_accounting', 'freequota');
		if(isset($free_space)){
			$free_space_real = \OC_Helper::computerFileSize($free_space);
			return [$free_space, $free_space_real];
		}
		else{
			return null;
		}
	}

	public static function dbRelativeSpace($userid, $totalUsed) {
		$quota = \OC_Util::getUserQuota($userid);
		$freeQuota = self::freeSpace($userid);

		if(isset($freeQuota)){
			$freeQuota = $freeQuota[1];
			$relative = round(($totalUsed / $freeQuota) * 10000) / 100;		
		}
		else{
			$relative = round(($totalUsed / $quota) * 10000) / 100;
		}
		return $relative;
	}

	public static function relativeSpace($userid, $totalUsed) {
		if(!\OCP\App::isEnabled('files_sharding') || \OCA\FilesSharding\Lib::isMaster()){
			$result = self::dbRelativeSpace($userid, $totalUsed);
		}
		else{
			$result = \OCA\FilesSharding\Lib::ws('actionsPersonal', array('userid'=>$userid,
				'totalUsed'=>$totalUsed, 'action'=>'relativeSpace'),
				false, true, null, 'files_accounting');
		}
		return $result;
	}

	public static function freeQuotaConfig($userid){
		if(!\OCP\App::isEnabled('files_sharding') || \OCA\FilesSharding\Lib::isMaster()){
			$result = \OC_Preferences::getValue($userid, 'files_accounting', 'freequota');
		}
		else{
			$result = \OCA\FilesSharding\Lib::ws('actionsPersonal', array('userid'=>$userid,
				'action'=>'freeQuotaConfig'), false, true, null, 'files_accounting');
		}
		return $result;
	}
}

