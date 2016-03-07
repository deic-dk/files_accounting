<table id="billingtable" class="panel" style="width:100%; margin-top:12px;">
<thead class="panel-heading"> 
<tr>
  <th id="headerName" class="column-display" style="padding-left:20px; width:14%">
        <div class="name sort columntitle" data-sort="descr">
		  <span class="text-semibold">Status</span>         
	    </div>
  </th>

  <th id="headerName" class= "column-name" style="width:20%; padding-left:12px; ">
    <div class="row">
      <span class="text-semibold">Date</span>
    </div>
  </th>

  <th id="headerDisplay" class="column-display" style="width:14%">
    <div class="size sort columntitle" data-sort="size">
      <span>Amount (DKK)</span>
    </div>
  </th>

  <th id="headerDisplay" class="column-display" style="width:17%">
        <div class="size sort columntitle" data-sort="size">
         <span>Due</span>
        </div>
  </th>	

  <th id="headerDisplay" class="column-display" style="width:20%">
    <div class="size sort columntitle" data-sort="size">
      <span>Invoice</span>
    </div>
  </th>


  <th id="headerDisplay" class="">
    <div class="size sort columntitle" data-sort="size">
      <span>Payment</span>
    </div>
  </th>
  
</tr>
</thead>
<tbody id="fileList">
	<?php
	$bills = \OCA\Files_Accounting\Util::userBill(OC_User::getUser (), date('Y') ) ;	
	if (count($bills) == 0) {
	  echo '<tr><td class="empty">You don\'t have any invoices yet</td>';
	}else {
		$count = 0;
		$i = 0;
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
			if ($bill['link'] != "") {
                        	$invoice = $bill['link'].'.pdf';
			}else {
				$invoice = "";
			}
			$average = $bill['average'];
			$duemonthname = date('F', strtotime("2000-$duemonth-01"));
			$due_date =  $duemonthname." 1, 23:59 PM";
			$vat = \OCA\Files_Accounting\Util::getTaxRate();
	  		if ($status == '0') {
      				$i ++;
	      			$status = '<div style="color:#CDDC39"><strong>Pending</strong></div>';
				echo "<tr><td style='height:34px; padding-left:6px;' ><div class='row'><div class='col-xs-1 text-right '></div>
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
					<input type="hidden" name="tax_rate" value="'.$vat.'">
					<input type="hidden" name="shipping" value="0.00">
					<input type="hidden" name="custom" value="'.OC_User::getUser().'">
					<input type="hidden" name="bn" value="PP-BuyNowBF:btn_paynow_SM.gif:NonHosted">
					<input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_paynow_SM.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">
					<img alt="" border="0" src="https://www.paypalobjects.com/en_US/i/scr/pixel.gif" width="1" height="1">
					</form>';


                                echo "</td></tr>";
      			}else {
				$count ++; 	
			}
		}
		if ($i <1) {
                        echo '<tr><td class="empty">You don\'t have new invoices</td></tr>';
                }

		if ($count > 0) {
			echo "<tr><td colspan='5' class='centertr'><div id='history' class='btn btn-primary btn-flat'>Load history</div></td></tr>";
		} 
	}
?>
</tbody>

</table>

