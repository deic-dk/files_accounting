<?php

namespace OCA\Files_Accounting;

use \OCP\DB; 

class Util {

	public static function userBill($user) {
		$stmt = DB::prepare ( "SELECT `status`, `month`, `bill`, `invoice_link` FROM `*PREFIX*files_accounting` WHERE `user` = ? " );
			$result = $stmt->execute ( array ($user));
			$monthly_bill = array ();
			while ( $row = $result->fetchRow () ) {
				$monthly_bill[] = array('status' => (int)$row['status'], 'month' => (int)$row['month'], 'bill' => (float)$row['bill'], 'link' => $row['invoice_link']); 
			}
	return $monthly_bill;
	}
}

