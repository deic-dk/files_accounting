<?php

namespace OCA\Files_Accounting;

use \OC_DB;

class Storage_Lib {

	const PAYMENT_STATUS_PAID = 1;
	const PAYMENT_STATUS_PENDING = 2;

	public static function getBillingDayOfMonth(){
		return \OCP\Config::getSystemValue('billingdayofmonth', 1);
	}

	public static function getBillingNetDays(){
		return \OCP\Config::getSystemValue('billingnetdays', 120);
	}

	public static function getBillingVAT(){
		return \OCP\Config::getSystemValue('billingvat', 25);
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

	public static function getPayPalApiCredentials(){
		$username = \OCP\Config::getSystemValue('paypalusername', '');
		$password = \OCP\Config::getSystemValue('paypalpassword', '');
		$signature = \OCP\Config::getSystemValue('paypalsignature', '');
		return array($username, $password, $signature);
	}

	public static function getBillingURL($user, $fq=true){
		$homeServer = "";
		if(\OCP\App::isEnabled('files_sharding')){
			if($fq){
				$homeServer = \OCA\FilesSharding\Lib::getServerForUser($user, false);
			}
			$url = $homeServer . "/index.php/settings/personal#userapps";
		}
		else{
			if($fq){
				$charge = getChargeForUserServers($userid);
				$homeServer = $charge['url_home'];
			}
			$url = $homeServer . "/index.php/settings/personal";
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

	public static function getUsageFilePath($user, $year, $group=null){
		$dir = self::getAppDir($user);
		return $dir."/usage".(!empty($group)?'_'.$group:"")."-".$year.".txt";
	}

	public static function getInvoiceDir($user){
		return self::getAppDir($user)."/bills";
	}

	public static function getChargeForUserServers($userid){
		// Allow functioning without files_sharding.
		// Allow local override of charge set in DB on master.
		$localCharge = \OCP\Config::getSystemValue('chargepergb');
		if(!\OCP\App::isEnabled('files_sharding')){
			$chargeHome = array();
			$chargeHome['charge_per_gb'] = $localCharge;
			// In non-sharded setups, set chargepergb and url of the server in config.php.
			$chargeHome['url'] = \OCP\Config::getSystemValue('url', '');
			$chargeBackup = array();
		}
		else{
			$isMaster = \OCA\FilesSharding\Lib::isMaster();
			$homeServerID = \OCA\FilesSharding\Lib::lookupServerIdForUser($userid,
					\OCA\FilesSharding\Lib::$USER_SERVER_PRIORITY_PRIMARY);
			if(empty($homeServerID)){
				if($isMaster){
					$master = \OCA\FilesSharding\Lib::getMasterHostName();
					$homeServerID = \OCA\FilesSharding\Lib::lookupServerId($master);
				}
				else{
					// Called from cron job on backup server for user with home on master.
					// We only check usage for user with home here.
					return null;
				}
			}
			if($isMaster){
				\OCP\Util::writeLog('Files_Accounting', 'HOME SERVER: '.$homeServerID, \OCP\Util::WARN);
				$chargeHome = self::dbGetCharge($homeServerID);
				if(isset($localCharge)){
					$chargeHome['charge_per_gb'] = $localCharge;
				}
			}
			else{
				$chargeHome = \OCA\FilesSharding\Lib::ws('getCharge', array('server_id'=>$homeServerID),
						false, true, null, 'files_accounting');
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
				'charge_home' => !empty($chargeHome['charge_per_gb'])?$chargeHome['charge_per_gb']:0,
				'charge_backup' => !empty($chargeBackup['charge_per_gb'])?$chargeBackup['charge_per_gb']:0,
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
			while ( $row = $result->fetchRow () ) {
				\OCP\Util::writeLog('Files_Accounting', 'charge on '.$serverid.': '.$row['charge_per_gb'], \OCP\Util::WARN);
				return $row;
			}
		}
		// defaultchargepergb is the default for servers
		// with no charge def in DB on master and no local setting of chargepergb
		return array('charge_per_gb'=> \OCP\Config::getSystemValue('defaultchargepergb', 0));
	}

	public static function currentUsageAverage($userid, $year, $month) {
		$homeInternalUrl = \OCA\FilesSharding\Lib::getServerForUser($userid, true,
				\OCA\FilesSharding\Lib::$USER_SERVER_PRIORITY_PRIMARY);
		$backupServerInternalUrl = \OCA\FilesSharding\Lib::getServerForUser($userid, true,
				\OCA\FilesSharding\Lib::$USER_SERVER_PRIORITY_BACKUP_1);
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
		else{
			$backupUsageAverage = 0;
		}
		return array('home'=>$homeUsageAverage, 'backup'=>$backupUsageAverage);
	}

	public static function getLocalUsage($userid, $trash=true, $group=null) {
		//solution to work with ws based on
		//https://github.com/owncloud/core/issues/5740
		$user = \OC::$server->getUserManager()->get($userid);
		$storage = new \OC\Files\Storage\Home(array('user'=>$user));
		$ret = array();
		$ret['free_space'] = $storage->free_space('/');
		if(\OCP\App::isEnabled('user_group_admin') && !empty($group)){
			$filesInfo = $storage->getCache()->get('user_group_admin/'.$group);
		}
		else{
			$filesInfo = $storage->getCache()->get('files');
		}
		$ret['files_usage'] = empty($filesInfo['size'])||$filesInfo['size']=="-1"?0:
		trim($filesInfo['size']);
		if($trash && empty($group)){
			$trashInfo = $storage->getCache()->get('files_trashbin/files');
			$ret['trash_usage'] = isset($trashInfo)&&!empty($trashInfo['size'])&&$trashInfo['size']!="-1"?
			trim($trashInfo['size']):0;
		}
		\OCP\Util::writeLog('Files_Accounting', 'Usage for '.$userid.': '.$group.': '.$trash. ': '.serialize($ret), \OCP\Util::WARN);
		return $ret;
	}

	public static function getDefaultQuotas(){
		if(!\OCP\App::isEnabled('files_sharding') || \OCA\FilesSharding\Lib::isMaster()){
			$defaultQuota = \OC_Appconfig::getValue('files', 'default_quota', INF);
			$defaultFreeQuota = \OC_Appconfig::getValue('files_accounting', 'default_freequota', 0);
		}
		else{
			$quotas = \OCA\FilesSharding\Lib::ws('personalStorage', array('key'=>'defaultQuotas'),
					false, true, null, 'files_accounting');
			$defaultQuota = $quotas['default_quota'];
			$defaultFreeQuota = $quotas['default_freequota'];
		}
		return array('default_quota'=>$defaultQuota, 'default_freequota'=>$defaultFreeQuota);
	}

	public static function personalStorage($userid, $trashbin=true, $group=null) {
		$ret = self::getQuotas($userid);
		$usage = self::getLocalUsage($userid, $trashbin, $group);
		$valid_quota = empty($ret['quota']) || $ret['quota']==="default"?
		$ret['default_quota']:$ret['quota'];
		$valid_freequota = empty($ret['freequota']) || $ret['freequota']==="default"?
			$ret['default_freequota']:$ret['freequota'];
		// Bump up quota if smaller than freequota
		$quota = !empty($valid_quota)?\OCP\Util::computerFileSize($valid_quota):INF;
		$freequota = !empty($valid_freequota)?\OCP\Util::computerFileSize($valid_freequota):0;
		if(!empty($freequota) && (!empty($quota) || $quota===0) && $quota<$freequota){
			$ret['quota'] = $valid_freequota;
			$quota = $freequota;
		}
		if(!empty($quota) || $quota===0 || $quota==='0'){
			$ret['total_space'] = $quota;
			$ret['free_space'] = $quota - (int)$usage['files_usage'] -
				(empty($usage['trash_usage'])?0:(int)$usage['trash_usage']);
		}
		else{
			$loggedin_user = \OCP\USER::getUser();
			if(empty($loggedin_user) || $userid!=$loggedin_user){
				$old_user = $loggedin_user;
				\OC_Util::teardownFS();
				\OC_User::setUserId($userid);
				\OC_Util::setupFS($userid);
			}
			$storageInfo = \OC_Helper::getStorageInfo("/");
			$ret['free_space'] = (int)$storageInfo['free'];
			//$ret['files_usage'] = (int)$storageInfo['used'];
			$ret['total_space'] = (int)$storageInfo['total'];
			if(!empty($old_user)){
				\OC_Util::teardownFS();
				\OC_User::setUserId($old_user);
				\OC_Util::setupFS($old_user);
				
			}
		}
		\OCP\Util::writeLog('Files_Accounting', 'Total space: '.$ret['total_space'].':'.
				$ret['quota'].':'.$ret['freequota'].':'.$ret['default_quota'], \OCP\Util::WARN);
		$ret['files_usage'] = $usage['files_usage'];
		$ret['trash_usage'] = $trashbin?$usage['trash_usage']:0;
		if(\OCP\App::isEnabled('files_sharding')){
			$backupServerInternalUrl = \OCA\FilesSharding\Lib::getServerForUser($userid, true,
					\OCA\FilesSharding\Lib::$USER_SERVER_PRIORITY_BACKUP_1);
			if(!empty($backupServerInternalUrl)){
				$personalStorageBackup = \OCA\FilesSharding\Lib::ws('personalStorage',
						array('userid'=>$userid, 'key'=>'usage', 'trashbin'=>false),
						false, true, $backupServerInternalUrl, 'files_accounting');
				$ret['backup_usage'] = $personalStorageBackup['files_usage'];
			}
		}
		return $ret;
	}

	public static function getQuotas($userid){
		$defaultQuotas = self::getDefaultQuotas();
		$defaultQuota = $defaultQuotas['default_quota'];
		$defaultFreeQuota = $defaultQuotas['default_freequota'];
		$ret['default_quota'] = $defaultQuotas['default_quota'];
		$ret['default_freequota'] = $defaultQuotas['default_freequota'];
		// Prefs have been set on login (by user_saml+files_sharding)
		$ret['freequota'] =
			\OC_Preferences::getValue($userid, 'files_accounting', 'freequota', $defaultFreeQuota);
		$ret['quota'] =
			\OC_Preferences::getValue($userid, 'files', 'quota', $defaultQuota);
		\OCP\Util::writeLog('files_accounting', 'Quotas: '.$userid.':'.$ret['quota'].'<' .$ret['freequota'], \OC_Log::INFO);
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
				$arr['year'] = $year;
			}
			if(!empty($status)){
				$arr['status'] = $status;
			}
			$result = \OCA\FilesSharding\Lib::ws('getBills', $arr, false, true, null, 'files_accounting');
		}
		return $result;
	}

	/**
	 * Columns of usage-201*.txt:
	 * 0/user_id 1/year 2/month 3/day 4/time 5/files_usage 6/trash_usage
	 */
	public static function logDailyUsage($user, $overwrite=false, $group=null) {
		$timestamp = time();
		$year = date('Y', $timestamp);
		$month = date('n', $timestamp);
		$day = date('j', $timestamp);
		$time = date('H:i:s', $timestamp);
		$usageFilePath = self::getUsageFilePath($user, $year, $group);
		if(!file_exists($usageFilePath)){
			touch($usageFilePath);
		}
		$lines = file($usageFilePath);
		if(!empty($lines)){
			while(empty($lastLine)){
				$lastLine = array_pop($lines);
				$lastLineArr = explode(" ", $lastLine);
			}
		}
		if(!empty($lastLine) && $lastLineArr[0]==$user &&
				$lastLineArr[1]==$year && $lastLineArr[2]==$month && $lastLineArr[3]==$day){
			if($overwrite){
				$i = 0;
				foreach ($lines as $line) {
				 file_put_contents($usageFilePath, $line, $i==0?LOCK_EX:FILE_APPEND | LOCK_EX);
					++$i;
				}
			}
			else{
				\OCP\Util::writeLog('Files_Accounting', 'Already logged user: '.$user. '. Skipping.', \OCP\Util::WARN);
				return;
			}
		}
		$usage = self::getLocalUsage($user, true, $group);
		$line = $user." ".$year." ".$month." ".$day." ".$time." ".$usage['files_usage']." ".
				(empty($usage['trash_usage'])?"0":$usage['trash_usage'])."\n";
		file_put_contents($usageFilePath, $line, FILE_APPEND | LOCK_EX);
		// For group usage, update DB on server to allow billing owner
		if(!empty($group) && \OCP\App::isEnabled('user_group_admin')){
			\OC_User_Group_Admin_Util::updateGroupUsage($user, $group, $usage['files_usage']);
		}
	}

	public static function localUsageData($user, $year, $month=null){
		$dailyUsage = array();
		$usageFilePath = self::getUsageFilePath($user, $year);
		if(!file_exists($usageFilePath)){
			return $dailyUsage;
		}
		$lines = file($usageFilePath);
		foreach ($lines as $line_num => $line) {
			$row = explode(" ", $line);
			if (!empty($row) && $row[0] == $user) {
				if ($row[1] == $year && (empty($month) || $row[2] == $month)) {
					$dailyUsage[] = array('year'=>(int)$row[1], 'month'=>(int)$row[2], 'day'=>(int)$row[3],
							'files_usage' => (int)$row[5], 'trash_usage' => (int)$row[6]);
				}
			}
		}
		return $dailyUsage;
	}

	public static function localCurrentUsageAverage($user, $year, $month=null, $timestamp=null) {

		if(empty($timestamp)){
			$todayDay = (int)date("j");
		}
		else{
			$todayDay = (int)date("j", $timestamp);
		}

		$usageFilePath = self::getUsageFilePath($user, $year);
		if(!file_exists($usageFilePath)){
			return array('files_usage'=>0, 'trash_usage'=>0);
		}
		$lines = file($usageFilePath);
		$dailyUsage = array();
		$averageToday = 0 ;
		$averageTodayTrash = 0;
		$i = 0;
		$firstDay = 0;
		$firstMonth = 0;
		foreach ($lines as $line_num => $line) {
			$row = explode(" ", trim($line));
			if (!empty($row) && $row[0] == $user) {
				if(empty($firstDay)){
					$firstDay = (int)$row[3];
					$firstMonth = (int)$row[2];
				}
				// We will calculate the average from this day last month to today.
				if(((int)$row[1])==$year && (empty($month) || ((int)$row[2])==$month && ((int)$row[3])<$todayDay ||
						(((int)$row[2])==$month-1 || ((int)$row[2])==12 && $month==1) && ((int)$row[3])>=$todayDay)){
					$dailyUsage[] = array('day'=>(int)$row[3], 'files_usage' => (int)$row[5],
							'trash_usage' => (int)$row[6]);
					\OC_Log::write('files_accounting', $row[2]."==".$month."--> files: ".$row[5].", trash: ".$row[6], \OC_Log::WARN);
					++$i;
				}
			}
		}
		if(!empty($dailyUsage)){
			$averageToday = array_sum(array_column($dailyUsage, 'files_usage')) / $i;
			$averageTodayTrash = array_sum(array_column($dailyUsage, 'trash_usage')) / $i;
		}

		return array('days'=>$i, 'first_day'=>$firstDay, 'first_month'=>$firstMonth,
				'files_usage'=>$averageToday, 'trash_usage'=>$averageTodayTrash);
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

	public static function dbUpdateMonth($user, $status, $year, $month, $timestamp, $timedue,
			$average, $averageTrash, $averageBackup,
			$homeId, $backupId, $homeUrl, $backupUrl, $homeSite, $backupSite,
			$amountDue, $referenceId){
		$stmt = \OCP\DB::prepare ( "INSERT INTO `*PREFIX*files_accounting` ( `user`, `status`, `year`, `month`, `timestamp`, `time_due`, `home_files_usage`, `home_trash_usage`, `backup_files_usage`, `home_id`, `backup_id`, `home_url`, `backup_url`, `home_site`, `backup_site`, `amount_due`, `reference_id`) VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
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
				$backupId,
				$homeUrl,
				$backupUrl,
				$homeSite,
				$backupSite,
				$amountDue,
				$referenceId
		) );

		return $result;
	}

	public static function updateMonth($user, $status, $year, $month, $timestamp, $timedue,
			$average, $averageTrash, $averageBackup,
			$homeId, $backupId, $homeUrl, $backupUrl, $homeSite, $backupSite,
			$amountDue, $referenceId){
		if(!\OCP\App::isEnabled('files_sharding') || \OCA\FilesSharding\Lib::isMaster()){
			$result = self::dbUpdateMonth($user, $status, $year, $month, $timestamp, $timedue,
					$average, $averageTrash, $averageBackup,
					$homeId, $backupId, $homeUrl, $backupUrl, $homeSite, $backupSite,
					$amountDue, $referenceId);
		}
		else{
			$result = \OCA\FilesSharding\Lib::ws('updateMonth', array('user_id'=>$user, 'status'=>$status,
					'year'=>$year, 'month'=>$month, 'timestamp'=>$timestamp, 'time_due'=>$timedue,
					'home_files_usage'=>$average, 'home_trash_usage'=>$averageTrash,
					'backup_files_usage'=>$averageTrash,
					'home_id'=>$homeId, 'backup_id'=>$backupId,
					'home_url'=>$homeUrl, 'backup_url'=>$backupUrl,
					'home_site'=>$homeSite, 'backup_site'=>$backupSite,
					'amount_due'=>$amountDue, 'reference_id'=>$referenceId),
					true, true, null, 'files_accounting');
		}
		return $result;
	}

	public static function dbCheckTxnId($tnxid) {
		$query = \OCP\DB::prepare("SELECT * FROM `*PREFIX*files_accounting_payments` WHERE `txnid` = '$tnxid'");
		$result = $query->execute( array ($tnxid));
		$row = $result->fetchRow ();
		return empty($row);
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
		$query = \OCP\DB::prepare("SELECT `amount_due` FROM `*PREFIX*files_accounting` WHERE `reference_id` = '$id'");
		$result = $query->execute(array($id));
		while ( $row = $result->fetchRow () ) {
			$bill = (float)$row["amount_due"];
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
					ucfirst(strtolower($data['payment_status'])),
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

	public static function getInvoice($filename, $user) {
		$dir = self::getInvoiceDir($user);
		$file = $dir . "/" . $filename;
		if(!file_exists($file)){
			\OCP\Util::writeLog('Files_Accounting', 'File not found: '.$filename, \OCP\Util::ERROR);
			return;
		}
		$type = filetype($file);
		header("Content-type: $type");
		header("Content-Disposition: attachment;filename=$filename");
		readfile($file);
	}

	public static function addQuotaExceededNotification($user, $freequota) {
		$charge = self::getChargeForUserServers($user);
		$name = \OCP\User::getDisplayName($user);
		$subject = 'Free quota exceeded';
		$currency = self::getBillingCurrency();

		$message = "Dear ".$name.",\n \nOn ".date('l jS \of F Y h:i:s A').
		" you have exceeded your free space " . $freequota .
		". From now on, you will be charged ".$charge['charge_home']." ".$currency."/GB on ".
		$charge['site_home'];
		if(isset($serverNames['backup'])){
			$message .= " and ".$charge['charge_backup']." ".$currency."/GB for your backup on ".
					$charge['site_backup'];
		}
		$message .= ".\n\nThanks for using our services.\n\n";

		\OCA\Files_Accounting\ActivityHooks::spaceExceed($user, $freequota);

		// Send long email regardless of the user's notification settings.
		$userEmail = \OCP\Config::getUserValue($user, 'settings', 'email');
		$userRealName = \OCP\User::getDisplayName($user);
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

	public static function getCurrentMonthBill($user, $month, $year) {
		$stmt = \OC_DB::prepare ( "SELECT `amount_due`, `reference_id` FROM `*PREFIX*files_accounting` WHERE `user` = ? AND `month` = ? AND `year` = ?" );
		$result = $stmt->execute ( array ($user, $month, $year) );
		$row = $result->fetchRow();
		return $row;
	}

	public static function dbSetPreapprovalKey($user, $preapprovalKey, $expiration) {
		$stmt = \OC_DB::prepare ( "SELECT `user` FROM `*PREFIX*files_accounting_adaptive_payments` WHERE `user` = ?" );
		$result = $stmt->execute ( array ($user) );
		if ($result->fetchRow ()) {
			return false;
		}
		$query = \OC_DB::prepare ("INSERT INTO `*PREFIX*files_accounting_adaptive_payments` ( `user` , `preapproval_key`, `expiration` ) VALUES( ? , ?, ? )" );
		$result = $query->execute( array ($user, $preapprovalKey, $expiration));
		return $result ? true : false;
	}

	public static function setPreapprovalKey($user, $preapprovalKey, $expiration) {
		if(!\OCP\App::isEnabled('files_sharding') || \OCA\FilesSharding\Lib::isMaster()){
			$result = self::dbSetPreapprovalKey($user, $preapprovalKey, $expiration);
		}
		else{
			$result = \OCA\FilesSharding\Lib::ws('preapprovalKey', array('userid'=>$user, 'action'=>'setPreapprovalKey',
					'preapproval_key'=>$preapprovalKey, 'expiration'=>$expiration),
					false, true, null, 'files_accounting');
		}
		return $result;
	}

	public static function deletePreapprovalKey($user, $preapprovalKey) {
		$stmt = \OC_DB::prepare ("DELETE FROM `*PREFIX*files_accounting_adaptive_payments` WHERE `user` = ? AND `preapproval_key` = ?");
		$result = $stmt->execute(array($user, $preapprovalKey));
	}

	public static function setAutomaticCharge($user, $amount, $preapprovalKey, $reference_id, $month, $year) {
		$keyErrors = array(569013, 569017, 569018, 579024);
		$paypalCredentials = self::getPayPalApiCredentials();
		$receiverEmail = self::getPayPalAccount();
		$currencyCode = self::getBillingCurrency();
		$ipnNotificationUrl = \OC::$WEBROOT.'/index.php/apps/files_accounting/ajax/paypal.php?reference_id='.
				urlencode($reference_id).'&month='.$month.'&user='.$user;
		if(\OCP\App::isEnabled('files_sharding') && !\OCA\FilesSharding\Lib::isMaster()){
			$ipnNotificationUrl = \OCA\FilesSharding\Lib::getMasterURL() . $ipnNotificationUrl;
		}
		PayPalAP::setAuth($paypalCredentials[0], $paypalCredentials[1], $paypalCredentials[2]);

		$options = array(
				'currencyCode' => $currencyCode,
				'receiverEmailArray' => array($receiverEmail),
				'receiverAmountArray' => array($amount),
				'actionType' => 'PAY',
				'preapprovalKey' => $preapprovalKey,
				'ipnNotificationUrl' => $ipnNotificationUrl
		);

		$response = PayPalAP::doPayment($options);

		if($response['success'] == true){
			return true;
		}
		else{
			$errors = $response['errors'];
			foreach ($errors as $error) {
				if (in_array((int)$error['errorId'], $keyErrors) ) {
					self::deletePreapprovalKey($user, $preapprovalKey);
					break;
				}
			}
			return false;
				
		}
	}

	/**
	 *
	 * @param $user
	 * @return true if preapproval was set up ok, false otherwise
	 */
	public static function dbGetPreapprovalKey($user, $month, $year){
		$stmt = \OC_DB::prepare ( "SELECT `preapproval_key`, `expiration` FROM `*PREFIX*files_accounting_adaptive_payments` WHERE `user` = ?" );
		$result = $stmt->execute(array($user));
		$row = $result->fetchRow ();
		if(!$row){
			// user has not signed up for preapproved payments
			return false;
		}
		$expiration = strtotime($row["expiration"]);
		$preapprovalKey = $row["preapproval_key"];
		if(time()  > $expiration){
			// key has expired. Delete user from the table.
			self::deletePreapprovalKey($user, $preapprovalKey);
			return false;
		}

		// get current amount due
		$currentBill = self::getCurrentMonthBill($user, $month, $year);
		if(empty($currentBill)){
			return false;
		}

		// check if the payment has already been executed
		$stmt = \OC_DB::prepare ( "SELECT `payment_status` FROM `*PREFIX*files_accounting_payments` WHERE `itemid` = ?" );
		$result = $stmt->execute(array($currentBill['reference_id']));
		$row = $result->fetchRow();
		if($row){
			return false;
		}

		// charge user
		$result = self::setAutomaticCharge($user, $currentBill['amount_due'],
				$preapprovalKey, $currentBill['reference_id'], $month, $year);
		if($result){
			// update months status
			self::updateStatus($currentBill['reference_id']);
		}
		return $result;
	}

	public static function getPreapprovalKey($user, $month, $year) {
		if(!\OCP\App::isEnabled('files_sharding') || \OCA\FilesSharding\Lib::isMaster()){
			$result = self::dbGetPreapprovalKey($user, $month, $year);
		}
		else{
			$result = \OCA\FilesSharding\Lib::ws('preapprovalKey', array('userid'=>$user,
					'action'=>'getPreapprovalKey', 'month'=>$month, 'year'=>$year),
					false, true, null, 'files_accounting');
		}
		return $result;
	}

}

