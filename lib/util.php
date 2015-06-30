<?php

namespace OCA\Files_Accounting;

use \OCP\DB; 

class Util {

	public static function userBill($user,$year) {
		$stmt = DB::prepare ( "SELECT `status`, `month`, `bill`, `invoice_link` FROM `*PREFIX*files_accounting` WHERE `user` = ? AND `year` = ?" );
			$result = $stmt->execute ( array ($user, $year ));
			$monthly_bill = array ();
			while ( $row = $result->fetchRow () ) {
				$monthly_bill[] = array('status' => (int)$row['status'], 'month' => (int)$row['month'], 'bill' => (float)$row['bill'], 'link' => $row['invoice_link']); 
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

	public static function updateStatus($user, $ref_id) {
		$query = DB::prepare ( "UPDATE `*PREFIX*files_accounting` SET `status` = true WHERE `user` = ? AND `reference_id` = ?" );
		$result = $query->execute ( array (
				$user,
				$ref_id
		) );
		return $result;
	} 

	public static function checkTnxId($tnxid) {
		$query = DB::prepare("SELECT * FROM `*PREFIX*files_accounting_payments` WHERE `txnid` = '$tnxid'");
		$result = $query->execute( array ($tnxid));

		return $result->fetchRow () ? true:false;
	}

	public static function checkPrice($price, $id) {
		$query = DB::prepare("SELECT `amount` FROM `*PREFIX*files_accounting_payments` WHERE `id` = '$id'");
		$result = $query->execute(array($id));
	 	return true;	 	
	}

	public static function updatePayments($data) {
		if(is_array($data)){
			$query = DB::prepare("INSERT INTO `*PREFIX*files_accounting_payments` ( `txnid`, `payment_amount`, `payment_status`, `createdtime`) VALUES (	?, ?, ?, ?)");
			$query->execute( array(
					$data['txn_id'],
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

