<?php

function getBills($status=null, $year=null){
	if(empty($year)){
		$year = date('Y');
	}
	$paypalAccount = \OCA\Files_Accounting\Storage_Lib::getPayPalAccount();
	$bills = \OCA\Files_Accounting\Storage_Lib::getBills(OC_User::getUser (), $year, $status);
	if(empty($bills)){
	  return "<tr><td class='empty'>You don't have any ".
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
			'<div style="color:#CDDC39"><strong>Pending</strong></div>':
			'<div style="color:#AAAAAA"><strong>Paid</strong></div>';
		$ret = "<tr>
		<td style='height:34px; padding-left:6px;' ><div class='row'>
		<div class='col-xs-1 text-right '></div>
		<div class='col-xs-8 filelink-wrap' style='padding-left:4px;'>
		<span class='nametext'>$statusStr</span></a></div>
		</td>
		<td class='amount' style='padding-left:2px;'>$amount</td>
		<td class='month'>$issueDate</td>
		<td class='duedate'>$dueDate</td>
		<td class='duedate'>$monthName</td>
		<td class='invoice'><a class='invoice-link'>$invoice</a></td>
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
				<img alt="" border="0" src="https://www.paypalobjects.com/en_US/i/scr/pixel.gif" width="1" height="1">
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

