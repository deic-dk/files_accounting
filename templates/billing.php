<?php

function getBills($status=null, $year=null){
	if(empty($year)){
		$year = date('Y');
	}
	$billingCurrency = \OCA\Files_Accounting\Storage_Lib::getBillingCurrency();
	$paypalAccount = \OCA\Files_Accounting\Storage_Lib::getPayPalAccount();
	$bills = \OCA\Files_Accounting\Storage_Lib::getBills(OC_User::getUser (), $year, $status);
	
	if(empty($bills)){
		$l = OC_L10N::get('files_accounting');
	  return 
	  ((isset($status)&&$status==\OCA\Files_Accounting\Storage_Lib::PAYMENT_STATUS_PAID)?
	  		("<tr><td class='empty' colspan=7>".$l->t("You don't have any old bills.")."</td></tr>"):
	  		("<tr><td class='empty' colspan=7>".$l->t("You don't have any bills.")."</td></tr>"));
	}
	$ret = "";
	foreach(array_reverse($bills) as $bill){
		$year = $bill['year'];
  		$month = $bill['month'];
		$monthName = date('F', strtotime("2000-$month-01"));
  		$issueDate = date("F j, Y", $bill['timestamp']);
  		$dueDate = date("F j, Y", $bill['time_due']);
		$billStatus = $bill['status'];
		$amount = (float)$bill['amount_due'];
                $user = OC_User::getUser();
                if($bill['reference_id'] != ""){
                        $invoice = $bill['reference_id'].'.pdf';
                }
                else{
                        $invoice = "";
		}
		if ($billStatus == \OCA\Files_Accounting\Storage_Lib::PAYMENT_STATUS_PAID
                && (!isset($status))){
                        $ret .= "";
                        continue;
                }
		$ret .= printTable($user, $billStatus, $paypalAccount, $monthName, $amount, $invoice, $billingCurrency, $issueDate, $dueDate);
	}
	return $ret;
}
function printTable($user, $billStatus, $paypalAccount, $monthName, $amount, $invoice, $billingCurrency, $issueDate, $dueDate) {
	$l = OC_L10N::get('files_accounting');
	$statusStr = $billStatus==\OCA\Files_Accounting\Storage_Lib::PAYMENT_STATUS_PENDING?
                '<div class="pending">'.$l->t('Pending').'</div>':
                '<div class="paid">'.$l->t('Paid').'</div>';
        $ret = "<tr>
        <td class='column-display name'>$statusStr</td>
        <td class='column-display'>$amount</td>
        <td class='column-display'>$issueDate</td>
        <td class='column-display'>$dueDate</td>
        <td class='column-display'>$monthName</td>
        <td class='column-display'><a class='invoice-link'>$invoice</a></td>
        <td class='paypal_btn'>";
        if($billStatus==\OCA\Files_Accounting\Storage_Lib::PAYMENT_STATUS_PENDING){
                $ret .= '<form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_top">
                        <input type="hidden" name="cmd" value="_xclick">
                        <input type="hidden" name="business" value="'.$paypalAccount.'">
                        <input type="hidden" name="item_name" value="Storage Use for '.$monthName.'">
                        <input type="hidden" name="amount" value="'.$amount.'">
                        <input type="hidden" name="item_number" value="'.substr($invoice, 0, -4).'">
                        <input type="hidden" name="currency_code" value="'.$billingCurrency.'">
                        <input type="hidden" name="button_subtype" value="services">
                        <input type="hidden" name="no_note" value="0">
                        <input type="hidden" name="cn" value="Add special instructions to the seller:">
                        <input type="hidden" name="no_shipping" value="2">
                        <input type="hidden" name="custom" value="'.$user.'">
                        <input type="hidden" name="bn" value="PP-BuyNowBF:btn_paynow_SM.gif:NonHosted">
                        <input type="image" src="https://www.paypalobjects.com/'.
                        ($l->getLanguageCode()=='da'?'da_DK':'en_US').
                        '/i/btn/btn_paynow_LG.gif" border="0" name="submit" alt="PayPal">
                        <img alt="" border="0" src="https://www.paypalobjects.com/en_US/i/scr/pixel.gif">
                        </form>';
        }
        $ret .= "</td></tr>";	
	return $ret; 
}	

if(isset($_['status']) && isset($_['year'])){
	echo getBills($_['status'], $_['year']);
}

if(!empty($_GET['giftcode'])){
	$code = $_GET['giftcode'];
	$user = OC_User::getUser();
	$ret = \OCA\Files_Accounting\Storage_Lib::redeemGiftCode($code, $user);
	if($ret){
		echo "<script type='text/javascript'>var url=window.location.href.replace('giftcode=','nocode='); ".
				"OC.dialogs.alert('Your code was successfully redeemed!', ".
				"'Congratulations', function(){window.location.href=url}, true);</script>";
	}
	else{
		//echo "<script type='text/javascript'>OC.dialogs.alert('$code is not a valid code.', 'Invalid code');</script>";
		echo "<script type='text/javascript'>var url=window.location.href.replace('giftcode=','nocode='); ".
				"OC.dialogs.alert('$code is not a valid code.', 'Invalid code', function(){window.location.href=url}, true);</script>";
	}
}

?>

