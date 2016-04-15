<?php

function getBills($status=null, $year=null){
	if(empty($year)){
		$year = date('Y');
	}
	$paypalAccount = \OCA\Files_Accounting\Storage_Lib::getPayPalAccount();
	$bills = \OCA\Files_Accounting\Storage_Lib::getBills(OC_User::getUser (), $year, $status);
	if(empty($bills)){
	  return "<tr><td class='empty' colspan=7>You don't have any ".
	  (isset($status)&&$status==\OCA\Files_Accounting\Storage_Lib::PAYMENT_STATUS_PAID?"old ":"").
	  "bills.</td></tr>";
	}

	foreach(array_reverse($bills) as $bill){
		$year = $bill['year'];
  	$month = $bill['month'];
		$monthName = date('F', strtotime("2000-$month-01"));
  	$issueDate = date("F j, Y", $bill['timestamp']);
  	$dueDate = date("F j, Y", $bill['time_due']);
		$amount = (float)$bill['amount_due'];
		$user = OC_User::getUser();
		if($bill['reference_id'] != ""){
			$invoice = $bill['reference_id'].'.pdf';
		}
		else{
			$invoice = "";
		}
		$i ++;
		$statusStr = $status==\OCA\Files_Accounting\Storage_Lib::PAYMENT_STATUS_PAID?
			'<div class="pending">Pending</div>':
			'<div class="paid">Paid</div>';
		$ret = "<tr>
		<td class='column-display name'>$statusStr</td>
		<td class='column-display'>$amount</td>
		<td class='column-display'>$issueDate</td>
		<td class='column-display'>$dueDate</td>
		<td class='column-display'>$monthName</td>
		<td class='column-display'><a class='invoice-link'>$invoice</a></td>
		<td class='paypal_btn'>";
		if(!isset($status) || $status==\OCA\Files_Accounting\Storage_Lib::PAYMENT_STATUS_PENDING){
			$ret .= '<form action="https://www.sandbox.paypal.com/cgi-bin/webscr" method="post" target="_top">
				<input type="hidden" name="cmd" value="_xclick">
				<input type="hidden" name="business" value="'.$paypalAccount.'">
				<input type="hidden" name="item_name" value="Storage Use for '.$fullmonth.'">
				<input type="hidden" name="amount" value="'.$amount.'">
				<input type="hidden" name="item_number" value="'.substr($invoice, 0, -4).'">
				<input type="hidden" name="currency_code" value="'.$billingCurrency.'">
				<input type="hidden" name="button_subtype" value="services">
				<input type="hidden" name="no_note" value="0">
				<input type="hidden" name="cn" value="Add special instructions to the seller:">
				<input type="hidden" name="no_shipping" value="2">
				<input type="hidden" name="custom" value="'.$user.'">
				<input type="hidden" name="bn" value="PP-BuyNowBF:btn_paynow_SM.gif:NonHosted">
				<input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_paynow_SM.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">
				<img alt="" border="0" src="https://www.paypalobjects.com/en_US/i/scr/pixel.gif">
				</form>';
		}
		$ret .= "</td></tr>";
	}
	return $ret;
}
if(isset($_['status']) && isset($_['year'])){
	echo getBills($_['status'], $_['year']);
}
?>

