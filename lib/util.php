<?php

namespace OCA\Files_Accounting;

class Util {

 	public static function dbUserBill($user,$year, $plot=null) {
		$stmt = \OCP\DB::prepare ( "SELECT  `status`, `month`, `bill`, `average`, `trashbin`, `reference_id` FROM `*PREFIX*files_accounting` WHERE `user` = ? AND YEAR(STR_TO_DATE(`month`, '%Y-%m')) = ?" );
		$result = $stmt->execute ( array ($user, $year ));
		$monthly_bill = array ();
		while ( $row = $result->fetchRow () ) {
			if (!$plot){
				if ((int)$row['status'] != 2) {
					$date = explode("-", $row['month']);
					$monthly_bill[] = array('status' => (int)$row['status'], 'month' => (int)$date[1], 'bill' => (float)$row['bill'], 'average' => (float)$row['average'], 'trashbin' => (float)$row['trashbin'], 'link' => $row['reference_id'], 'year' => $date[0]); 
				}	
			}else {
				$date = explode("-", $row['month']);
				$monthly_bill[] = array('month' => (int)$date[1], 'average' => (float)$row['average'], 'trashbin' => (float)$row['trashbin'], 'year' => $date[0]);
			}
		}
		return $monthly_bill;
	}

	public static function userBill($user, $year, $plot) {
		if(!\OCP\App::isEnabled('files_sharding') || \OCA\FilesSharding\Lib::isMaster()){
			$result = self::dbUserBill($user, $year, $plot);
		}
		else{
			$result = \OCA\FilesSharding\Lib::ws('userBill', array('userid'=>$user, 'year'=>$year, 'plot'=>$plot),
					false, true, null, 'files_accounting');
		}
		return $result;
	}

	public static function dbDailyUsage($user, $monthToSave, $year) {
		$path = \OCA\Files_Accounting\Storage_Lib::getInvoiceDir($user);
		$dailyFilePath = $path."/diskUsageDaily".$year.".txt";
		if(!file_exists($dailyFilePath)){
			touch($dailyFilePath);
		}
		$lines = file($dailyFilePath);
		$dailyUsage = array();
		$userStorage  = array();
		$averageToday = 0 ;
		$averageTodayTrash = 0;
		foreach ($lines as $line_num => $line) {
			$userRows = explode(" ", $line);
			if ($userRows[0] == $user) {
				$month = substr($userRows[1], 0, 2);
				if ($month == $monthToSave) { 
					$month = (int)$month;
					$dailyUsage[] = array('usage' => (int)$userRows[2], 'trash' => (int)$userRows[3], 'month' => $month);
				}
			}
		}
		if(!empty($dailyUsage)){
			$averageToday = array_sum(array_column($dailyUsage, 'usage')) / count(array_column($dailyUsage, 'usage'));
			$averageTodayTrash = array_sum(array_column($dailyUsage, 'trash')) / count(array_column($dailyUsage, 'trash'));
		}

		if ($averageToday != 0) {
			$userStorage = array(date('M'), $averageToday, $averageTodayTrash);
		}
		return $userStorage;
	}

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

	public static function dbBillYear($user) {
		$year = date('Y');
		$stmt = \OCP\DB::prepare ( "SELECT DISTINCT `month`  FROM `*PREFIX*files_accounting` WHERE `user` = ? AND YEAR(STR_TO_DATE(`month`, '%Y-%m')) != ?" );
	 	$result = $stmt->execute ( array (
				$user,
				$year
		) );

		$years = array ();
		while ( $row = $result->fetchRow () ) {
			$years [] = explode("-", $row['month'])[0];
		}

		return array_reverse(array_unique($years));	
	} 

	public static function billYear($user) {
		if(!\OCP\App::isEnabled('files_sharding') || \OCA\FilesSharding\Lib::isMaster()){
			$result = self::dbBillYear($user);
		}
		else{
			$result = \OCA\FilesSharding\Lib::ws('billYear', array('userid'=>$user),
					false, true, null, 'files_accounting');
			}
			return $result;
	}

	public static function dbUpdateStatus($id) {
		$query = \OCP\DB::prepare ( "UPDATE `*PREFIX*files_accounting` SET `status` = true WHERE `reference_id` = ?" );
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

	public static function dbUpdateMonth($user, $status, $month, $year, $average, $averageTrash, $bill, $reference_id){
		$stmt = \OCP\DB::prepare ( "INSERT INTO `*PREFIX*files_accounting` ( `user`, `status`, `month`, `average`, `trashbin`, `bill`, `reference_id`) VALUES(?, ?, ?, ?, ?, ?, ?)");
		$result = $stmt->execute ( array (
				$user,
				$status,
				date("$year-$month"),
				$average,
				$averageTrash,
				$bill,
				$reference_id
		) );

		return $result;
	}

	public static function updateMonth($user, $status, $month, $year, $average, $averageTrash, $bill, $reference_id){
		if(!\OCP\App::isEnabled('files_sharding') || \OCA\FilesSharding\Lib::isMaster()){
			$result = self::dbUpdateMonth($user, $status, $month, $year, $average, $averageTrash, $bill, $reference_id);
		}
		else{
			$result = \OCA\FilesSharding\Lib::ws('updateMonth', array('userid'=>$user, 'status'=>$status, 
				'month'=>$month, 'year'=>$year, 'average'=>$average, 
				'averageTrash'=>$averageTrash, 'bill'=>$bill, 'reference_id'=>$reference_id),
					false, true, null, 'files_accounting');
		}
		return $result;
	}

	public static function dbCheckTxnId($tnxid) {
		$query = \OCP\DB::prepare("SELECT * FROM `*PREFIX*files_accounting_payments` WHERE `txnid` = '$tnxid'");
		$result = $query->execute( array ($tnxid));
		return $result->fetchRow () ? false:true;
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
			$result = \OCA\FilesSharding\Lib::ws('checkPrice', array('action'=>'checkPrice', 'price'=>$price, 'id'=>urlencode($id)),
				false, true, null, 'files_accounting');
		}
		return $result;

	}
	
	public static function getTaxRate() {
		if(!\OCP\App::isEnabled('files_sharding') || \OCA\FilesSharding\Lib::isMaster()){
			$result = (float) \OCP\Config::getAppValue('files_accounting', 'tax', '');
		}
		else{
			$result = \OCA\FilesSharding\Lib::ws('checkPrice', array('action'=>'getTaxRate'),
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
				 	date("Y-m-d H:i:s")
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
		$path = \OCA\Files_Accounting\Storage_Lib::getInvoiceDir($user);
		$format = str_replace(array('/', '\\'), '', $filename);
		$file = $path . "/" . $format;
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

	public static function addNotification($user, $free_space) {
		self::updateFreeQuota($user, $free_space);
		\OCA\Files_Accounting\ActivityHooks::spaceExceed($user, $free_space);
		$charge = \OCA\Files_Accounting\Storage_Lib::getChargeForUserServers($user);
		$serverNames = \OCA\Files_Accounting\Storage_Lib::getServerNamesForInvoice($user);
		$name = \OCP\User::getDisplayName($user);
		$defaults = new \OCP\Defaults();
		$senderName = $defaults->getName();
		$senderAddress = \OCA\Files_Accounting\Storage_Lib::getIssuerAddress();
		$subject = 'DeIC Data: Storage Notice';
		$currency = \OCA\Files_Accounting\Storage_Lib::getBillingCurrency();
		$message = "Dear ".$name.",\n \nOn ".date('l jS \of F Y h:i:s A').
		" you exceeded the available free storage space of " . $free_space .
		". From now on, your average use of storage will be charged ".
		$charge['home']." ".$currency."/Gigabyte for ".$serverNames['home'][1]." (".$serverNames['home'][0].")";
		if(isset($serverNames['backup'])){
			$message .= " and ".$charge['backup']." ".$currency."/Gigabyte for ".$serverNames['backup'][1]." (".$serverNames['backup'][0].")";
		}
		$message .= ".\n\nThank you for choosing our services.\n \n";
		try {
			\OCP\Util::sendMail(
					$user, $name,
					$subject, $message,
					$senderAddress, $senderName
			);
		}
		catch(\Exception $e){
			\OCP\Util::writeLog('Files_Accounting', 'A problem occurred while sending the e-mail. Please revisit your settings.', \OCP\Util::ERROR);
		}
		\OC_Preferences::setValue($user, 'files_accounting', 'freequota', null);
	}

	public static function updateFreeQuota($user, $free_space) {
		$free_space_exceed = \OC_Preferences::getValue($user, 'files_accounting', 'freequotaexceed');
		if(isset($free_space_exceed)){
			$total_exceed = \OC_Helper::computerFileSize($free_space_exceed) + \OC_Helper::computerFileSize($free_space);
			$total_exceed = \OC_Helper::humanFileSize($total_exceed);
			$free_space_exceed = \OC_Preferences::setValue($user, 'files_accounting', 'freequotaexceed', $total_exceed);
		}
		else{
			$free_space_exceed = \OC_Preferences::setValue($user, 'files_accounting', 'freequotaexceed', $free_space);
		}
		return $free_space_exceed;
	}
}
