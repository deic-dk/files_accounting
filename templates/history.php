<?php
	$year = isset($_GET['year'])?$_GET['year']:null;
	$bills = \OCA\Files_Accounting\Util::userBill(OC_User::getUser (), $year, false ) ;	
	foreach (array_reverse($bills) as $bill) {
  		$status = $bill['status'];
		$month = $bill['month'];
		if ($month != 12) {
                        $datemonth = $bill['month'] + 01;
                        $fullmonth = date('F', strtotime("2000-$datemonth-01"));
                        $date = $fullmonth." 1, ".$bill['year'];
			$duemonth = (int)$month + 02;
                }else {
                        $datemonth = 01;
                        $fullmonth = date('F', strtotime("2000-$datemonth-01"));
                        $date = $fullmonth." 1, ".($bill['year']+1);
			$duemonth = 02;
                }

		$monthbill = (float)$bill['bill'];
                $duemonthname = date('F', strtotime("2000-$duemonth-01"));
                $due_date =  $duemonthname." 1, 23:59 PM";
		if ($bill['link'] != "") {
                       	$invoice = $bill['link'].'.pdf';
		}else {
			$invoice = "";
		}

  		if ($status == '1') {
   	   		$status = '<div style="color:#9E9E9E"><strong>Paid</strong><div>';
	   		$button = '<div><i class="icon-ok"></i></div>';
			echo "<tr><td style='height:34px; padding-left:6px;' ><div class='row'><div class='col-xs-1 text-right '></div>
                <div class='col-xs-8 filelink-wrap' style='padding-left:4px;'>
                       <span class='nametext'>$status</span></a></div>
                           </td><td class='month'>$date</td><td style='padding-left:2px;'>$monthbill</td>
                           <td class='duedate'>$due_date</td><td class='invoice'><a class='invoice-link'>$invoice</a></td><td>$button</td></tr>";
      		}else {
			$status = '<div style="color:#CDDC39"><strong>Pending</strong></div>';
			echo "<tr class='unpaid'><td style='height:34px; padding-left:6px;' ><div class='row'><div class='col-xs-1 text-right '></div>
                        <div class='col-xs-8 filelink-wrap' style='padding-left:4px;'>
                       <span class='nametext'>$status</span></a></div>
                           </td><td class='month'>$date</td><td class='amount' style='padding-left:2px;'>$monthbill</td>
                           <td class='duedate'>$due_date</td><td class='invoice'><a class='invoice-link'>$invoice</a></td><td class='paypal_btn'>";

		echo '<form action="https://www.sandbox.paypal.com/cgi-bin/webscr" method="post" target="_top">
					 <input type="hidden" name="cmd" value="_xclick">
                                        <input type="hidden" name="business" value="ioanna.psylla-facilitator@gmail.com">
                                        <input type="hidden" name="lc" value="DK">
                                        <input type="hidden" name="item_name" value="Storage Use for '.$fullmonth.'">
                                        <input type="hidden" name="amount" value="'.$monthbill.'">
                                       <input type="hidden" name="item_number" value="'.substr($invoice, 0, -4).'">
                                        <input type="hidden" name="currency_code" value="DKK">
                                        <input type="hidden" name="button_subtype" value="services">
                                        <input type="hidden" name="no_note" value="0">
                                        <input type="hidden" name="cn" value="Add special instructions to the seller:">
                                        <input type="hidden" name="no_shipping" value="2">
                                        <input type="hidden" name="bn" value="PP-BuyNowBF:btn_paynow_SM.gif:NonHosted">
                                        <input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_paynow_SM.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">
                                        <img alt="" border="0" src="https://www.paypalobjects.com/en_US/i/scr/pixel.gif" width="1" height="1">
                                        </form>';

			echo "</td></tr>";
      		}
	}
//	echo "<tr><td colspan='5'><div>Hide history</div></td></tr>";
?>
