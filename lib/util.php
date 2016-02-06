<?php

namespace OCA\Files_Accounting;

use \OCP\DB; 
use \OCP\Config;

class Util {

 	public static function dbUserBill($user,$year) {
		$stmt = DB::prepare ( "SELECT  `status`, `month`, `bill`, `average`, `trashbin`, `reference_id` FROM `*PREFIX*files_accounting` WHERE `user` = ? AND YEAR(STR_TO_DATE(`month`, '%Y-%m')) = ?" );
		$result = $stmt->execute ( array ($user, $year ));
		$monthly_bill = array ();
		while ( $row = $result->fetchRow () ) {
			if ((int)$row['status'] != 2) {
				$date = explode("-", $row['month']);
				$monthly_bill[] = array('status' => (int)$row['status'], 'month' => (int)$date[1], 'bill' => (float)$row['bill'], 'average' => (float)$row['average'], 'trashbin' => (float)$row['trashbin'], 'link' => $row['reference_id'], 'year' => $date[0]); 
			}
		}
		return $monthly_bill;
	}

	public static function userBill($user, $year) {
		if(!\OCP\App::isEnabled('files_sharding') || \OCA\FilesSharding\Lib::isMaster()){
                        $result = self::dbUserBill($user, $year);
                }
                else{
                        $result = \OCA\FilesSharding\Lib::ws('userBill', array('userid'=>$user, 'year'=>$year),
                                 false, true, null, 'files_accounting');
                }
                return $result;
	}

	public static function dbDailyUsage($user, $year) {
		$dailyFilePath = "/tank/data/owncloud/".$user."/diskUsageDaily".$year.".txt";
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
				$month =  substr($userRows[1], 0, 2);
				if ($month == date('m')) { 
					$month = (int)$month;
					$dailyUsage[] = array('usage' => (int)$userRows[2], 'trash' => (int)$userRows[3], 'month' => $month);
				}
			}
		} 
		if (!empty($dailyUsage)) {
			$averageToday = array_sum(array_column($dailyUsage, 'usage')) / count(array_column($dailyUsage, 'usage'));
                        $averageTodayTrash = array_sum(array_column($dailyUsage, 'trash')) / count(array_column($dailyUsage, 'trash'));
		}

		if ($averageToday != 0) {
			$userStorage = array(date('M'), $averageToday, $averageTodayTrash);
		}	
		return $userStorage;
	}

	public static function dbGetFileContent($user, $year) {
		$dailyFilePath = "/tank/data/owncloud/".$user."/diskUsageDaily".$year.".txt";
		if(!file_exists($dailyFilePath)){
			touch($dailyFilePath);
		}
		$fileContents = file_get_contents($dailyFilePath);
		return $fileContents;
	}
	public static function getFileContent($user, $year) {
		if(!\OCP\App::isEnabled('files_sharding') || \OCA\FilesSharding\Lib::isMaster()){
			$result = self::dbGetFileContent($user, $year);
		}else {
			$result = \OCA\FilesSharding\Lib::ws('getFileContent', array('userid'=>$user, 'year'=>$year),
				false, true, null, 'files_accounting');
		}	 
		return $result;
	}
	
	public static function usersInGroup($gid, $search = '', $limit = null, $offset = null) {
	  		$stmt = DB::prepare ( 'SELECT `uid` FROM `*PREFIX*user_group_admin_group_user` WHERE `gid` = ? AND `uid` LIKE ?', $limit, $offset );
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
		$stmt = DB::prepare ( "SELECT DISTINCT `month`  FROM `*PREFIX*files_accounting` WHERE `user` = ? AND YEAR(STR_TO_DATE(`month`, '%Y-%m')) != ?" );
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
		$query = DB::prepare ( "UPDATE `*PREFIX*files_accounting` SET `status` = true WHERE `reference_id` = ?" );
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
		$stmt = DB::prepare ( "INSERT INTO `*PREFIX*files_accounting` ( `user`, `status`, `month`, `average`, `trashbin`, `bill`, `refe
, ? , ?, ?, ? )" );
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
		$query = DB::prepare("SELECT * FROM `*PREFIX*files_accounting_payments` WHERE `txnid` = '$tnxid'");
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
		$query = DB::prepare("SELECT `bill` FROM `*PREFIX*files_accounting` WHERE `reference_id` = '$id'");
		$result = $query->execute(array($id));
		while ( $row = $result->fetchRow () ) {
			$bill = (float)$row["bill"]*1.25;
			$bill = round($bill, 2);
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
                          $result = \OCA\FilesSharding\Lib::ws('checkPrice', array('price'=>$price, 'id'=>$id),
                                 false, true, null, 'files_accounting');
                }
                return $result;

	}
	
	
	public static function dbUpdatePayments($data) {
		if(is_array($data)){
			$query = DB::prepare("INSERT INTO `*PREFIX*files_accounting_payments` ( `txnid`, `itemid`, `payment_amount`, `payment_status`, `created_time`) VALUES (?, ?, ?, ?, ?)");
			$query->execute( array(
					$data['txn_id'],
					$data['item_number'],
					$data['payment_amount'],
					$data['payment_status'],
				 	date("Y-m-d H:i:s")
			));	
			return true;
		}else {
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
		$query = DB::prepare('SELECT `gid` FROM `*PREFIX*groups` WHERE `gid` LIKE ?', $limit, $offset );
		$result = $query->execute ( array ($search . '%',
		) );
		$groups = array ();
		while ( $row = $result->fetchRow () ) {
			$groups [] = $row ['gid'];
		}
		return $groups;
		
	}

	public static function inGroup($user) {
		$stmt = DB::prepare ( "SELECT `uid` FROM `*PREFIX*user_group_admin_group_user` WHERE `gid` = ? AND `uid` = ? " );
		$result = $stmt->execute ( array (
				'dtu.dk',
				$user 
		) );
		
		return $result->fetchRow () ? true : false;	
	}

	public static function dbDownloadInvoice($filename, $user) {
		$format = str_replace(array('/', '\\'), '', $filename);
		$file = "/tank/data/owncloud/" . $user . "/" . $format;
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
}
