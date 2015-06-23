<?php
	$year = $_POST['year'];
	$bills = \OCA\Files_Accounting\Util::userBill(OC_User::getUser (), $year ) ;	
	foreach (array_reverse($bills) as $bill) {
  		$status = $bill['status'];
		$month = $bill['month'];
                $fullmonth = date('F', strtotime("2000-$month-01"));
                $monthbill = $bill['bill'];
                $invoice = $bill['link'];
  		if ($status == '1') {
   	   		$status = '<div style="color:#9E9E9E"><strong>Paid</strong><div>';
	   		$button = '<div><i class="icon-ok"></i></div>';
			echo "<tr><td style='height:34px; padding-left:6px;' ><div class='row'><div class='col-xs-1 text-right '></div>
                <div class='col-xs-8 filelink-wrap' style='padding-left:4px;'>
                       <span class='nametext'>$status</span></a></div>
                           </td><td>$fullmonth</td><td style='padding-left:2px;'>$monthbill</td>
                           <td>$invoice</td><td>$button</td></tr>";
      		}else {
			$status = '<div style="color:#CDDC39"><strong>Pending</strong></div>';
			$button = '<div class="inlineblock button">Pay now</div>';
			echo "<tr class='unpaid'><td style='height:34px; padding-left:6px;' ><div class='row'><div class='col-xs-1 text-right '></div>
                        <div class='col-xs-8 filelink-wrap' style='padding-left:4px;'>
                       <span class='nametext'>$status</span></a></div>
                           </td><td>$fullmonth</td><td style='padding-left:2px;'>$monthbill</td>
                           <td>$invoice</td><td>$button</td></tr>";
      		}
	}
//	echo "<tr><td colspan='5'><div>Hide history</div></td></tr>";
?>

