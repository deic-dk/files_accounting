<table id="billingtable" class="panel" style="width:100%; margin-top:12px;">
<thead class="panel-heading"> 
<tr>

  <th id="headerName" class="column-display" style="padding-left:20px; width:17%">
        <div class="name sort columntitle" data-sort="descr">
		  <span class="text-semibold">Status</span>         
	    </div>
  </th>
  <th id="headerName" class= "column-name" style="width:20%; padding-left:12px; ">
    <div class="row">
      <span class="text-semibold">Month</span>
    </div>
  </th>
  <th id="headerDisplay" class="column-display" style="width:14%">
    <div class="size sort columntitle" data-sort="size">
      <span>Bill</span>
    </div>
  </th>

  <th id="headerDisplay" class="column-display" style="width:30%">
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
	$bills = \OCA\Files_Accounting\Util::userBill(OC_User::getUser () ) ;	
	if (count($bills) == 0) {
	  echo '<tr><td class="empty">You don\'t have any invoices yet</td>';
	}else {
		$count = 1;
		foreach (array_reverse($bills) as $bill) {
	  		$count ++;
	  		$status = $bill['status'];
	  		if ($status == '1') {
	   	   		$status = '<div style="color:#9E9E9E"><strong>Paid</strong><div>';
		   		$button = '<div><i class="icon-ok"></i></div>';
      		}else {
	      		$status = '<div style="color:#CDDC39"><strong>Pending</strong></div>';
		  		$button = '<div class="inlineblock button">Pay now</div>';
      		}
	  		$month = $bill['month'];
	  		$fullmonth = date('F', strtotime("2000-$month-01"));
	  		$monthbill = $bill['bill'];
      		$invoice = $bill['invoice_link'];

			echo "<tr><td style='height:34px; padding-left:6px;' ><div class='row'><div class='col-xs-1 text-right '></div>
		<div class='col-xs-8 filelink-wrap' style='padding-left:4px;'>
		       <span class='nametext'>$status</span></a></div>
			   </td><td>$fullmonth</td><td style='padding-left:2px;'>$monthbill</td>
			   <td>$invoice</td><td>$button</td></tr>";
	  		if ($count > 3) {
				break;
	  		} 
		}
	  echo "<tr><td colspan='5' class='centertr'><div class='btn btn-primary btn-flat'>Load more</div></td></tr>";
	}
?>
</tbody>

</table>

