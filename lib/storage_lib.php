<?php

namespace OCA\Files_Accounting;

use \OC_DB;

class Storage_Lib {
	
	const PAYMENT_STATUS_PAID = 1;
	const PAYMENT_STATUS_PENDING = 2;
	
	public static function getBillingDayOfMonth(){
		return \OCP\Config::getSystemValue('billingdayofmonth', 16);
	}

	public static function getBillingNetDays(){
		return \OCP\Config::getSystemValue('billingnetdays', 30);
	}

	public static function getBillingVAT(){
		return \OCP\Config::getSystemValue('billingvat', 10);
	}

	public static function getBillingCurrency(){
		return \OCP\Config::getSystemValue('billingcurrency', 'EUR');
	}
	
	public static function getIssuerAddress(){
		return \OCP\Config::getSystemValue('fromaddress', '');
	}
	
	public static function getIssuerEmail(){
		return \OCP\Config::getSystemValue('fromemail', '');
	}
	
	public static function getPayPalHostedButtonID(){
		return \OCP\Config::getSystemValue('paypalhostedbuttonid', '');
	}
	
	public static function getPayPalAccount(){
		return \OCP\Config::getSystemValue('paypalaccount', '');
	}
	
	public static function getBillingURL($user){
		if(\OCP\App::isEnabled('files_sharding')){
			$homeServer = \OCA\FilesSharding\Lib::getServerForUser($user, false);
			$url = $homeServer . \OC::$WEBROOT . "/index.php/settings/personal#userapps";
		}
		else{
			$url = \OCP\Config::getSystemValue('billingurl', '');
		}
		return $url;
	}
	
	private static function getAppDir($user){
		\OC_User::setUserId($user);
		\OC_Util::setupFS($user);
		$fs = \OCP\Files::getStorage('files_accounting');
		if(!$fs){
			\OC_Log::write('files_accounting', "ERROR, could not access files of user ".$user, \OC_Log::ERROR);
			return null;
		}
		return $fs->getLocalFile('/');
	}
	
	public static function getUsageFilePath($user, $year){
		$dir = self::getAppDir($user);
		return $dir."/usage-".$year.".txt";
	}
	
	public static function getInvoiceDir($user){
		return self::getAppDir($user);
	}
	
	public static function getChargeForUserServers($userid){
		// Allow functioning without files_sharding.
		// Allow local override of charge set in DB on master.
		$localCharge = \OCP\Config::getSystemValue('chargepergb', null);
		if(!\OCP\App::isEnabled('files_sharding')){
			$chargeHome = $localCharge;
			$chargeBackup = 0;
			$homeServerID = '';
			$backupServerID = '';
		}
		else{
			$homeServerID = \OCA\FilesSharding\Lib::lookupServerIdForUser($userid,
					\OCA\FilesSharding\Lib::$USER_SERVER_PRIORITY_PRIMARY);
			$isMaster = \OCA\FilesSharding\Lib::isMaster();
			if($isMaster){
				$chargeHome = isset($localCharge)?$localCharge:self::dbGetCharge($homeServerId);
			}
			else{
				$chargeHome = \OCA\FilesSharding\Lib::ws('getCharge', array('server_id'=>$homeServerID),
					false, true, null, 'files_accounting');
			}
			if(!empty($backupServerID)){
				
			}
			$backupServerID = \OCA\FilesSharding\Lib::lookupServerIdForUser($userid,
					\OCA\FilesSharding\Lib::$USER_SERVER_PRIORITY_BACKUP_1);
			if(!empty($backupServerID)){
				if($isMaster){
					$chargeBackup =self::dbGetCharge($backupServerID);
				}
				else{
					$chargeBackup = \OCA\FilesSharding\Lib::ws('getCharge', array('server_id'=>$backupServerID),
							false, true, null, 'files_accounting');
				}
			}
		}
		$ret = Array(
				'charge_home' => $chargeHome['charge_per_gb'], 'charge_backup' => isset($chargeBackup['charge_per_gb'])?$chargeBackup['charge_per_gb']:0,
				'id_home' => $homeServerID, 'id_backup' => !empty($backupServerID)?$backupServerID:'',
				'url_home'=>$chargeHome['url'], 'url_backup'=>isset($chargeBackup['url'])?$chargeBackup['url']:'',
				'site_home'=>$chargeHome['site'], 'site_backup'=>isset($chargeBackup['site'])?$chargeBackup['site']:'');
		\OCP\Util::writeLog('Files_Accounting', 'CHARGE HOME: '.$ret['charge_home'].' BACKUP: '.$ret['charge_backup'], \OCP\Util::WARN);
		return $ret;
	}

	public static function dbGetCharge($serverid) {
		if(isset($serverid)){
			$query = \OC_DB::prepare('SELECT * FROM `*PREFIX*files_sharding_servers` WHERE `id` = ?');
			$result = $query->execute(Array($serverid));
			$charge = $result->fetchRow();
			foreach($charge as $row){
				return $row;
			}
		}
		// defaultchargepergb is the default for servers
		// with no charge def in DB on master and no local setting of chargepergb
		return array('charge_per_gb'=> \OCP\Config::getSystemValue('defaultchargepergb', 0));
	}

	public static function monthlyUsageAverage($userid, $month, $year) {
		$backupServerInternalUrl = \OCA\FilesSharding\Lib::getServerForUser($userid, true, \OCA\FilesSharding\Lib::$USER_SERVER_PRIORITY_BACKUP_1);
		if(\OCA\FilesSharding\Lib::onServerForUser($userid)) {
			$homeUsageAverage = self::localCurrentUsageAverage($userid, $year, $month);
		}
		else{
			$homeUsageAverage = \OCA\FilesSharding\Lib::ws('currentUsageAverage', array('userid'=>$userid, 'month'=>$month, 'year'=>$year),
				false, true, $homeInternalUrl, 'files_accounting');
		}
		if(!empty($backupServerInternalUrl)){
			$backupUsageAverage = \OCA\FilesSharding\Lib::ws('currentUsageAverage', array('userid'=>$userid, 'month'=>$month, 'year'=>$year),
				false, true, $backupServerInternalUrl, 'files_accounting');
		}
		return array('home'=>$homeUsageAverage, 'backup'=>$backupUsageAverage);
	}

	/*
	 * Calculate storage usage on both home and backup server for a user
	 */
	private static function dailyUsage($userid, $year, $month) {
		if(\OCP\App::isEnabled('files_sharding')){
			if(\OCA\FilesSharding\Lib::isMaster()){
				$backupServerId = \OCA\FilesSharding\Lib::dbLookupServerIdForUser($userid, 1);
				if(!empty($backupServerId)){
					$backupServerUrl = \OCA\FilesSharding\Lib::dbLookupInternalServerURL($backupServerId);
					$dailyUsageBackupInfo = \OCA\FilesSharding\Lib::ws('dailyUsage', array('userid'=>$userid, 'month'=>$month, 'year'=>$year),
							false, true, $backupServerUrl, 'files_accounting');
				}
			}
			else{
				$dailyUsageBackupInfo = \OCA\FilesSharding\Lib::ws('dailyUsage', array('userid'=>$userid, 'month'=>$month, 'year'=>$year),
						false, true, null, 'files_accounting');
			}
		}
		//calculate the daily usage on the home server from the text file
		$dailyUsageInfo = self::localCurrentUsageAverage($userid, $year, $month);
		$dailyUsageTotal = array(!empty($dailyUsageInfo)?$dailyUsageInfo:null, !empty($dailyUsageBackupInfo)?$dailyUsageBackupInfo:null);
		return $dailyUsageTotal;
	}

	public static function getLocalUsage($userid, $trashbin=true) {
		//solution to work with ws based on
		//https://github.com/owncloud/core/issues/5740
		$user = \OC::$server->getUserManager()->get($userid);
		$storage = new \OC\Files\Storage\Home(array('user'=>$user));
		$ret['free_space'] = $storage->free_space('/');
		$filesInfo = $storage->getCache()->get('files');
		$ret = array();
		$ret['files_usage'] = $filesInfo['size'];
		if($trashbin){
			$trashInfo = $storage->getCache()->get('files_trashbin/files');
			$ret['trash_usage'] = $trashInfo['size'];
		}
		return $ret;
	}
	
	public static function getDefaultQuotas(){
		if(!\OCP\App::isEnabled('files_sharding') || \OCA\FilesSharding\Lib::isMaster()){
			$defaultQuota = OC_Appconfig::getValue('files', 'default_quota', INF);
			$defaultFreeQuota = OC_Appconfig::getValue('files_accounting', 'default_freequota', 0);
		}
		else{
			$quotas = \OCA\FilesSharding\Lib::ws('personalStorage', array('key'=>'defaultQuotas'),
					false, true, null, 'files_accounting');
			$defaultQuota = $quotas['default_quota'];
			$defaultFreeQuota = $quotas['default_freequota'];
		}
		return array('default_quota'=>$defaultQuota, 'default_freequota'=>$defaultFreeQuota);
	}
	
	public static function personalStorage($userid, $trashbin=true) {
		$defaultQuotas = getDefaultQuotas();
		$defaultQuota = $defaultQuotas['default_quota'];
		$defaultFreeQuota = $defaultQuotas['default_freequota'];
		
		$usage = self::getLocalUsage($userid, $trashbin);
		$ret['files_usage'] = $usage['files_usage'];
		$ret['trash_usage'] = $usage['trash_usage'];
		
		$userStorageBackup = 0;
		if(\OCP\App::isEnabled('files_sharding')){
						$backupServerInternalUrl = \OCA\FilesSharding\Lib::getServerForUser($user_id, true, 1);
				if(!empty($backupServerInternalUrl)){
					$personalStorageBackup = \OCA\FilesSharding\Lib::ws('personalStorage',
							array('userid'=>$userid, 'key'=>'userStorage', 'trashbin'=>false),
							false, true, $backupServerInternalUrl, 'files_accounting');
					$ret['backup_usage'] = $personalStorageBackup['files_usage'];
				}
		}

		// Prefs have been set on login (by user_saml+files_sharding)
		$ret['freequota'] = 
			\OC_Preferences::getValue($userid, 'files_accounting', 'freequota', $defaultFreeQuota);
		$ret['quota'] =
			\OC_Preferences::getValue($userid, 'files', 'quota', $defaultQuota);

		return $ret;
	}

	public static function dbGetBills($user=null, $year=null, $status=null) {
		$sql = "SELECT  * FROM `*PREFIX*files_accounting` WHERE TRUE";
		$arr = Array();
		if(!empty($user)){
			$sql .= " AND `user` = ?";
			$arr[] = $user;
		}
		if(!empty($year)){
			$sql .= " AND `year` = ?";
			$arr[] = $year;
		}
		if(!empty($status)){
			$sql .= " AND `status` = ?";
			$arr[] = $status;
		}
		$stmt = \OCP\DB::prepare($sql);
		$result = $stmt->execute($arr);
		return $result->fetchAll();
	}
	
	public static function getBills($user=null, $year=null, $status=null) {
		if(!\OCP\App::isEnabled('files_sharding') || \OCA\FilesSharding\Lib::isMaster()){
			$result = self::dbGetBills($user, $year, $status);
		}
		else{
			$arr = Array();
			if(!empty($user)){
				$arr['userid'] = $user;
			}
			if(!empty($year)){
				$arr['year'] = $user;
			}
			if(!empty($status)){
				$arr['status'] = $user;
			}
			$result = \OCA\FilesSharding\Lib::ws('getBills', $arr, false, true, null, 'files_accounting');
		}
		return $result;
	}
	
	/**
	 * Columns of usage-201*.txt:
	 * 0/user_id 1/year 2/month 3/day 4/time 5/files_usage 6/trash_usage
	 */
	public static function logDailyUsage($user, $overwrite=false) {
		$timestamp = time();
		$year = date('Y', $timestamp);
		$month = date('n', $timestamp);
		$day = date('j', $timestamp);
		$time = date('H:i:s', $timestamp);
		$usageFilePath = self::getUsageFilePath($user, $year);
		if(!file_exists($usageFilePath)){
			touch($usageFilePath);
		}
		$lines = file($usageFilePath);
		while(empty($lastLine)){
			$lastLine = array_pop($lines);
		}
		if($lastLine[0]==$user && $lastLine[1]==$year && $lastLine[2]==$month && $lastLine[3]==$day){
			if($overwrite){
				foreach ($lines as $line) {
					file_put_contents($usageFilePath, $line, LOCK_EX);
				}
			}
			else{
				return;
			}
		}
		$row = explode(" ", $lastLine);
		$usage = self::getLocalUsage($userid, $trashbin);
		$line = $user.' '.$year.' '.$month.' '.$day.' '.$time.' '.$usage['files_usage'].' '.$usage['trash_usage'].'\n';
		file_put_contents($usageFilePath, $line, FILE_APPEND | LOCK_EX);
	}
	
	public static function localCurrentUsageAverage($user, $year, $month) {
		$usageFilePath = self::getUsageFilePath($user, $year);
		if(!file_exists($usageFilePath)){
			touch($usageFilePath);
		}
		$lines = file($usageFilePath);
		$dailyUsage = array();
		$userStorage  = array();
		$averageToday = 0 ;
		$averageTodayTrash = 0;
		foreach ($lines as $line_num => $line) {
			$row = explode(" ", $line);
			if (!empty($row) && $row[0] == $user) {
				if ($row[1] == $year && $row[2] == $month) {
					$dailyUsage[] = array('day'=>(int)$row[3], 'files_usage' => (int)$row[5],
							'trash_usage' => (int)$row[6]);
				}
			}
		}
		if(!empty($dailyUsage)){
			$averageToday = array_sum(array_column($dailyUsage, 'files_usage')) / count(array_column($dailyUsage, 'files_usage'));
			$averageTodayTrash = array_sum(array_column($dailyUsage, 'trash_usage')) / count(array_column($dailyUsage, 'trash_usage'));
		}
	
		return array('files_usage'=>$averageToday, 'trash_usage'=>$averageTodayTrash);
	}
	
	// TODO
	public static function usersInGroup($gid, $search = '', $limit = null, $offset = null) {
		$stmt = \OCP\DB::prepare ( 'SELECT `uid` FROM `*PREFIX*user_group_admin_group_user` WHERE `gid` = ? AND `uid` LIKE ?', $limit, $offset );
		$result = $stmt->execute ( array (
				$gid,
				$search . '%',
		) );
		$users = array ();
		while ( $row = $result->fetchRow () ) {
			$users [] = $row ['uid'];
		}
	
		return $users;
	}
	
	public static function dbAccountedYears($user) {
		$year = date('Y');
		$stmt = \OCP\DB::prepare ( "SELECT DISTINCT `year`  FROM `*PREFIX*files_accounting` WHERE `user` = ?" );
		$result = $stmt->execute(array ($user));
		$years = array ();
		while($row = $result->fetchRow()){
			$years[]= $row['year'];
		}
	
		return array_reverse(array_unique($years));
	}
	
	public static function accountedYears($user) {
		if(!\OCP\App::isEnabled('files_sharding') || \OCA\FilesSharding\Lib::isMaster()){
			$result = self::dbAccountedYears($user);
		}
		else{
			$result = \OCA\FilesSharding\Lib::ws('accountedYears', array('userid'=>$user),
					false, true, null, 'files_accounting');
		}
		return $result;
	}
	
	public static function dbUpdateStatus($id) {
		$query = \OCP\DB::prepare ( "UPDATE `*PREFIX*files_accounting` SET `status` = ".self::PAYMENT_STATUS_PAID." WHERE `reference_id` = ?" );
		$result = $query->execute ( array ($id) );
		return $result;
	}
	
	public static function updateStatus($id) {
		if(!\OCP\App::isEnabled('files_sharding') || \OCA\FilesSharding\Lib::isMaster()){
			$result = self::dbUpdateStatus($id);
		}
		else{
			$result = \OCA\FilesSharding\Lib::ws('updateStatus', array('id'=>$id),
					false, true, null, 'files_accounting');
		}
		return $result;
	}
	
	public static function dbUpdateMonth($user, $status, $year, $month, $timestamp, $timedue, $average, $averageTrash, $averageBackup,
			$homeId, $backupId, $homeUrl, $backupUrl, $homeSite, $backupSite, $bill, $referenceId){
		$stmt = \OCP\DB::prepare ( "INSERT INTO `*PREFIX*files_accounting` ( `user`, `status`, `year`, `month`, `timestamp`, `time_due` home_files_usage`, `home_trash_usage`, `backup_files_usage`, `home_id`, `backup_id`, `home_url`, `backup_url`, `home_site`, `backup_site`, `amount_due`, `reference_id`) VALUES(?, ?, ?, ?, ?, ?, ?)");
		$result = $stmt->execute ( array (
				$user,
				$status,
				$year,
				$month,
				$timestamp,
				$timedue,
				$average,
				$averageTrash,
				$averageBackup,
				$homeId,
				$backupSite,
				$homeUrl,
				$backupUrl,
				$homeSite,
				$backupSite,
				$bill,
				$referenceId
		) );
	
		return $result;
	}
	
	public static function updateMonth($user, $status, $year, $month, $timestamp, $timedue, $average, $averageTrash, $averageBackup,
			$homeId, $backupId, $homeUrl, $backupUrl, $homeSite, $backupSite, $bill, $referenceId){
		if(!\OCP\App::isEnabled('files_sharding') || \OCA\FilesSharding\Lib::isMaster()){
			$result = self::dbUpdateMonth($user, $status, $year, $month, $timestamp, $timedue, $average, $averageTrash, $averageBackup,
					$homeId, $backupId, $homeUrl, $backupUrl, $homeSite, $backupSite, $bill, $referenceId);
		}
		else{
			$result = \OCA\FilesSharding\Lib::ws('updateMonth', array('user_id'=>$user, 'status'=>$status,
					'year'=>$year, 'month'=>$month, 'timestamp'=>$timestamp, 'time_due'=>$timedue, 'home_files_usage'=>$average,
					'home_trash_usage'=>$averageTrash, 'backup_files_usage'=>$averageTrash, 'bill'=>$bill,
					'reference_id'=>$referenceId, 'home_id'=>$homeSite, 'backup_id'=>$backupSite,
					'home_url'=>$homeSite, 'backup_url'=>$backupSite, 'home_site'=>$homeSite, 'backup_site'=>$backupSite),
					false, true, null, 'files_accounting');
		}
		return $result;
	}
	
	public static function dbCheckTxnId($tnxid) {
		$query = \OCP\DB::prepare("SELECT * FROM `*PREFIX*files_accounting_payments` WHERE `txnid` = '$tnxid'");
		$result = $query->execute( array ($tnxid));
		$row = $result->fetchRow ();
		return empty($row)?false:true;
	}
	
	public static function checkTxnId($txnid) {
		if(!\OCP\App::isEnabled('files_sharding') || \OCA\FilesSharding\Lib::isMaster()){
			$result = self::dbCheckTxnId($txnid);
		}
		else{
			$result = \OCA\FilesSharding\Lib::ws('checkTxnId', array('txnid'=>$txnid),
					false, true, null, 'files_accounting');
		}
		return $result;
	}
	
	public static function dbCheckPrice($price, $id) {
		$valid_price = false;
		$query = \OCP\DB::prepare("SELECT `bill` FROM `*PREFIX*files_accounting` WHERE `reference_id` = '$id'");
		$result = $query->execute(array($id));
		while ( $row = $result->fetchRow () ) {
			$bill = (float)$row["bill"];
			if ($bill == $price) {
				$valid_price = true;
			}
		}
		return $valid_price;
	}
	
	public static function checkPrice($price, $id) {
		if(!\OCP\App::isEnabled('files_sharding') || \OCA\FilesSharding\Lib::isMaster()){
			$result = self::dbCheckPrice($price, $id);
		}
		else{
			$result = \OCA\FilesSharding\Lib::ws('checkPrice', array('price'=>$price, 'id'=>urlencode($id)),
					false, true, null, 'files_accounting');
		}
		return $result;
	
	}
	
	public static function dbUpdatePayments($data) {
		if(is_array($data)){
			$query = \OCP\DB::prepare("INSERT INTO `*PREFIX*files_accounting_payments` ( `txnid`, `itemid`, `payment_amount`, `payment_status`, `created_time`) VALUES (?, ?, ?, ?, ?)");
			$query->execute( array(
					$data['txn_id'],
					$data['item_number'],
					$data['payment_amount'],
					$data['payment_status'],
					date("c")
			));
			return true;
		}
		else {
			return false;
		}
	}
	
	public static function updatePayments($data) {
		if(!\OCP\App::isEnabled('files_sharding') || \OCA\FilesSharding\Lib::isMaster()){
			$result = self::dbUpdatePayments($data);
		}
		else{
			$result = \OCA\FilesSharding\Lib::ws('updatePayments', array('txn_id'=>$data['txn_id'],
					'item_number'=>$data['item_number'], 'payment_amount'=>$data['payment_amount'],
					'payment_status'=>$data['payment_status']),
					false, true, null, 'files_accounting');
		}
		return $result;
	}
	
	// TODO
	public static function getDefaultGroups($search,$limit = null, $offset = null ) {
		$query = \OCP\DB::prepare('SELECT `gid` FROM `*PREFIX*groups` WHERE `gid` LIKE ?', $limit, $offset );
		$result = $query->execute ( array ($search . '%') );
		$groups = array ();
		while ( $row = $result->fetchRow () ) {
			$groups [] = $row ['gid'];
		}
		return $groups;
	}
	
	public static function dbDownloadInvoice($filename, $user) {
		$dir = self::getInvoiceDir($user);
		$file = $dir . "/" . $filename;
		if(!file_exists($file)) die("I'm sorry, the file doesn't seem to exist.");
		$type = filetype($file);
		header("Content-type: $type");
		header("Content-Disposition: attachment;filename=$filename");
		readfile($file);
	}
	
	public static function downloadInvoice($filename, $user) {
		if(!\OCP\App::isEnabled('files_sharding') || \OCA\FilesSharding\Lib::isMaster()){
			self::dbDownloadInvoice($filename, $user);
		}
		else{
			\OCA\FilesSharding\Lib::ws('getInvoice', array('filename'=>urlencode($filename), 'user'=>$user),
					false, true, null, 'files_accounting');
		}
	}
	
	public static function addQuotaExceededNotification($user, $free_quota) {
		$charge = self::getChargeForUserServers($user);
		$name = \OCP\User::getDisplayName($user);
		$subject = 'Free quota exceeded';
		$currency = self::getBillingCurrency();
		
		$message = "Dear ".$name.",\n \nOn ".date('l jS \of F Y h:i:s A').
		" you have exceeded your free space " . $free_space .
		". From now on, you will be charged ".$charge['charge_home']." ".$currency."/GB on ".
		$charge['site_home'];
		if(isset($serverNames['backup'])){
			$message .= " and ".$charge['charge_backup']." ".$currency."/GB for your backup on ".
					$charge['site_backup'];
		}
		$message .= ".\n\nThanks for using our services.\n\n";
		
		\OCA\Files_Accounting\ActivityHooks::spaceExceed($user, $free_quota);
		
		// Send long email regardless of the user's notification settings.
		$userEmail = \OCP\Config::getUserValue($user, 'settings', 'email');
		$userRealName = \User::getDisplayName($user);
		$fromEmail = \OCA\Files_Accounting\Storage_Lib::getIssuerEmail();
		$fromRealName = $charge['site_home'];
		try {
			\OCP\Util::sendMail(
					$userEmail, $name,
					$subject, $message,
					$fromEmail, $fromRealName
			);
		}
		catch(\Exception $e){
			\OCP\Util::writeLog('Files_Accounting', 'A problem occurred while sending e-mail. '.$e, \OCP\Util::ERROR);
		}
	}
	
}

