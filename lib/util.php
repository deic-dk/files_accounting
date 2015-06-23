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
}

