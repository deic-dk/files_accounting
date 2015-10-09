<?php

namespace OCA\Files_Accounting;

use \OCP\DB; 
use \OCP\Config;

class Util {

	public static function userBill($user,$year) {
		$stmt = DB::prepare ( "SELECT  `status`, `month`, `bill`, `average`, `reference_id` FROM `*PREFIX*files_accounting` WHERE `user` = ? AND `year` = ?" );
		$result = $stmt->execute ( array ($user, $year ));
		$monthly_bill = array ();
		while ( $row = $result->fetchRow () ) {
			if ((int)$row['status'] != 2) {
				$monthly_bill[] = array('status' => (int)$row['status'], 'month' => (int)$row['month'], 'bill' => (float)$row['bill'], 'average' => (float)$row['average'], 'link' => $row['reference_id']); 
			}
		}
		return $monthly_bill;
	}

	public static function userBalance($user, $average) {
		$average = $average/1048576;
		$gift_card = Util::freeSpace($user);
		if (isset($gift_card)) {
			$str_to_ar = explode(" ", $gift_card);
			$amount = (float) $str_to_ar[0];
			$size = $str_to_ar[1];
			if ($size == 'MB'){
				$amount = $amount/1024;
			} 
                	if ($amount > $average){
                        	$balance = $amount - $average;
				$balance = round($balance, 3);
                	}else {
                        	$balance = 0;
                	}
			return $balance;
		}

	}

	public static function updatePreferences($user, $gift_card) {
		$config_key = 'freequota';
		$app_id = 'files_accounting';

		$stmt = DB::prepare ( "SELECT `configkey` FROM `*PREFIX*preferences` WHERE `userid` = ? AND `configkey` = ?" );
                $value = $stmt->execute ( array (
                                $user,
                                $config_key 
                ) );
                if ($value->fetchRow ()) {
                        $stmt = DB::prepare ( "UPDATE `*PREFIX*preferences` SET `configvalue` = '$gift_card' WHERE `userid` = ? AND `appid` = ? AND `configkey` = ?" );
                        $result = $stmt->execute ( array (
                                        $user,
                                        $app_id,
                                        $config_key 
                                  ) );
                } else {
                	$stmt = DB::prepare("INSERT INTO `*PREFIX*preferences` ( `userid`, `appid`, `configkey`, `configvalue`) VALUES (?, ?, ?, ?)");
                	$result = $stmt->execute( array(
                        	$user,
                        	$app_id,
                        	$config_key,
                        	$gift_card
                	));
                }

		return $result;
				
	}
	
	public static function freeSpace($user) {
		$app_id = 'files_accounting';
		$config_key = 'freequotaexceed';
		$stmt = DB::prepare ( "SELECT `configvalue` FROM `*PREFIX*preferences` WHERE `userid` = ? AND `appid` = ? AND `configkey` = ? " );
		$result = $stmt->execute ( array (
				$user,
				$app_id,
				$config_key 
		) );
		$row = $stmt->fetch (); 
                $config_value  = $row["configvalue"];

		return $config_value;
	}

	public static function billYear($user) {
		$year = date('Y');
		$stmt = DB::prepare ( "SELECT `year`  FROM `*PREFIX*files_accounting` WHERE `user` = ? AND `year` != ?" );
	 	$result = $stmt->execute ( array (
				$user,
				$year
		) );
		
		$years = array ();
		while ( $row = $result->fetchRow () ) {
			$years [] =  $row ["year"];
		}
		
		return array_reverse(array_unique($years));	
	} 

	public static function getId($user, $month) {
		$stmt = DB::prepare ( "SELECT `reference_id`  FROM `*PREFIX*files_accounting` WHERE `user` = ? AND `month` = ?" );
		$result = $stmt->execute ( array (
				$user,
				$month
		));	
		$row = $stmt->fetch (); 
                $id  = $row["reference_id"];
		return $id;
	}

	public static function updateStatus($id) {
		$query = DB::prepare ( "UPDATE `*PREFIX*files_accounting` SET `status` = true WHERE `reference_id` = ?" );
		$result = $query->execute ( array ($id) );
		return $result;
	} 

	public static function checkTxnId($tnxid) {
		$query = DB::prepare("SELECT * FROM `*PREFIX*files_accounting_payments` WHERE `txnid` = '$tnxid'");
		$result = $query->execute( array ($tnxid));

		return $result->fetchRow () ? false:true;
	}

	public static function checkPrice($price, $id) {
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

	public static function updatePayments($data) {
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

}

