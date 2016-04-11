<fieldset id='storageSettings' class='section'>
	<h2>Storage Use</h2>
	
	<div style="padding-top:25px;">
	<select id="years">
	<?php
	include "billing.php";
	$hostedButtonID = \OCA\Files_Accounting\Storage_Lib::getPayPalHostedButtonID();
	$billingCurrency = \OCA\Files_Accounting\Storage_Lib::getBillingCurrency();
	$years = \OCA\Files_Accounting\Storage_Lib::accountedYears(OCP\USER::getUser ());
	$thisYear = date("Y");
	foreach ($years as $year) {
		echo "<option value=$year".$year==$thisYear?"selected='selected'":"".">$year</option>";
	}
	?>
	</option>
	</select>
	</div>

	<div id="chart_div">
	</div>

	<div style="margin-top: 2%">
	<form action="https://www.sandbox.paypal.com/cgi-bin/webscr" method="post" target="_top">
		<input type="hidden" name="cmd" value="_s-xclick">
		<input type="hidden" name="hosted_button_id" value="<?php echo $hostedButtonID;?>">
		<span>
			Maximum amount you want to pay each month:
			<input type="text" name="max_amount" value="" /><?php echo $billingCurrency;?>
		</span>
		<span>
			Sign up for<input type="image" src="https://www.paypalobjects.com/en_US/GB/i/btn/btn_auto_billing_LG.gif" border="0" name="submit" alt="PayPal">
			<img alt="" border="0" src="https://www.sandbox.paypal.com/en_GB/i/scr/pixel.gif" width="1" height="1">
		</span>
	</form>
	
	</div>

	<table id="billingtable" class="panel" style="width:100%; margin-top:12px;">
		<thead class="panel-heading"> 
		<tr>
	  <th id="headerName" class="column-display" style="padding-left:20px;">
	        <div class="name sort columntitle" data-sort="descr">
			  <span class="text-semibold">Status</span>         
		    </div>
	  </th>

	  <th id="headerDisplay" class="column-display" style="width:14%">
	    <div class="size sort columntitle" data-sort="size">
	      <span>Amount (<?php echo $billingCurrency;?>)</span>
	    </div>
	  </th>
	
	  <th id="headerName" class= "column-name" style="padding-left:12px;">
	    <div class="row">
	      <span class="text-semibold">Date</span>
	    </div>
	  </th>
	
	  <th id="headerDisplay" class="column-display">
	        <div class="size sort columntitle" data-sort="size">
	         <span>Due</span>
	        </div>
	  </th>	

	  <th id="headerDisplay" class="column-display">
	        <div class="size sort columntitle" data-sort="size">
	         <span>Period</span>
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
			<?php echo getBills();?>
		</tbody>

		<tr><td colspan="6" class="centertr"><div id="history" class="btn btn-primary btn-flat">Load history</div></td></tr>

	</table>

</fieldset>
