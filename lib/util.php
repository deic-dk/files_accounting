<?php

namespace OCA\Files_Accounting;

use \OCP\DB; 
use \OCP\Config;

class Util {

	public static function userBill($user,$year) {
		$stmt = DB::prepare ( "SELECT  `status`, `month`, `bill`, `average`, `reference_id` FROM `*PREFIX*files_accounting` WHERE `user` = ? AND YEAR(STR_TO_DATE(`month`, '%Y-%m')) = ?" );
		$result = $stmt->execute ( array ($user, $year ));
		$monthly_bill = array ();
		while ( $row = $result->fetchRow () ) {
			if ((int)$row['status'] != 2) {
				$date = explode("-", $row['month']);
				$monthly_bill[] = array('status' => (int)$row['status'], 'month' => (int)$date[1], 'bill' => (float)$row['bill'], 'average' => (float)$row['average'], 'link' => $row['reference_id'], 'year' => $date[0]); 
			}
		}
		return $monthly_bill;
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
	
	public static function billYear($user) {
		$year = date('Y');
		$stmt = DB::prepare ( "SELECT `month`  FROM `*PREFIX*files_accounting` WHERE `user` = ? AND YEAR(STR_TO_DATE(`month`, '%Y-%m')) != ?" );
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

	public static function inGroup($user) {
		$stmt = DB::prepare ( "SELECT `uid` FROM `*PREFIX*user_group_admin_group_user` WHERE `gid` = ? AND `uid` = ? " );
		$result = $stmt->execute ( array (
				'dtu.dk',
				$user 
		) );
		
		return $result->fetchRow () ? true : false;	
	}

}

