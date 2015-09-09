<?php

namespace OCA\Files_Accounting;

use \OCP\DB; 

class Util {

	public static function userBill($user,$year) {
		$stmt = DB::prepare ( "SELECT `fa_id`, `status`, `month`, `bill`, `reference_id` FROM `*PREFIX*files_accounting` WHERE `user` = ? AND `year` = ?" );
			$result = $stmt->execute ( array ($user, $year ));
			$monthly_bill = array ();
			while ( $row = $result->fetchRow () ) {
				$monthly_bill[] = array('id' => $row['fa_id'], 'status' => (int)$row['status'], 'month' => (int)$row['month'], 'bill' => (float)$row['bill'], 'link' => $row['reference_id'].'.pdf'); 
			}
	return $monthly_bill;
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
		$stmt = DB::prepare ( "SELECT `fa_id`  FROM `*PREFIX*files_accounting` WHERE `user` = ? AND `month` = ?" );
		$result = $stmt->execute ( array (
				$user,
				$month
		));	
		$row = $stmt->fetch (); 
                $id  = $row["fa_id"];
		return $id;
	}

	public static function updateStatus($id) {
		$query = DB::prepare ( "UPDATE `*PREFIX*files_accounting` SET `status` = true WHERE `fa_id` = ?" );
		$result = $query->execute ( array (
				(int)$id
		) );
		return $result;
	} 

	public static function checkTxnId($tnxid) {
		$query = DB::prepare("SELECT * FROM `*PREFIX*files_accounting_payments` WHERE `txnid` = '$tnxid'");
		$result = $query->execute( array ($tnxid));

		return $result->fetchRow () ? false:true;
	}

	public static function checkPrice($price, $id) {
		$valid_price = false;
		$query = DB::prepare("SELECT `bill` FROM `*PREFIX*files_accounting` WHERE `fa_id` = '$id'");
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
}

