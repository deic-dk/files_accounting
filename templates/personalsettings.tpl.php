<fieldset id='storageSettings' class='section'>
	<h2>Storage Use</h2>
	
	<select id="years">
	<?php
	include "billing.php";
	$hostedButtonID = \OCA\Files_Accounting\Storage_Lib::getPayPalHostedButtonID();
	$billingCurrency = \OCA\Files_Accounting\Storage_Lib::getBillingCurrency();
	$years = \OCA\Files_Accounting\Storage_Lib::accountedYears(OCP\USER::getUser ());
	$thisYear = date("Y");
	foreach ($years as $year) {
		echo "<option value=".$year.($year==$thisYear?"selected='selected'":"").">".$year."</option>";
	}
	?>
	</select>

	<div id="chart_div">
	</div>

	<div>
	<form action="https://www.sandbox.paypal.com/cgi-bin/webscr" method="post" target="_top">
		<input type="hidden" name="cmd" value="_s-xclick">
		<input type="hidden" name="hosted_button_id" value="<?php echo $hostedButtonID;?>">
		<span>
			Maximum amount you want to pay each month:
			<input type="text" name="max_amount" value="" /><?php echo $billingCurrency;?>
		</span>
		<span class="paypal_img">
			Sign up for<input id="paypal_billing_button" type="image" src="https://www.paypalobjects.com/en_US/GB/i/btn/btn_auto_billing_LG.gif" border="0" name="submit" alt="PayPal">
			<img alt="Paypal button" border="0" src="https://www.sandbox.paypal.com/en_GB/i/scr/pixel.gif">
		</span>
	</form>
	
	</div>

	<table id="billingtable" class="panel">
		<thead class="panel-heading"> 
		<tr>
	  <th class="column-display">
	      <div class="name sort columntitle" data-sort="descr">
					<span>Status</span>
		    </div>
	  </th>

	  <th class="column-display">
	    <div class="size sort columntitle" data-sort="size">
	      <span>Amount (<?php echo $billingCurrency;?>)</span>
	    </div>
	  </th>
	
	  <th class="column-display">
	    <div class="size sort columntitle" data-sort="size">
	      <span>Date</span>
	    </div>
	  </th>
	
	  <th class="column-display">
	      <div class="size sort columntitle" data-sort="size">
	         <span>Due</span>
	      </div>
	  </th>

	  <th class="column-display">
	      <div class="name sort columntitle" data-sort="descr">
	         <span>Period</span>
	      </div>
	  </th>	

	  <th class="column-display">
	    <div class="name sort columntitle" data-sort="descr">
	      <span>Invoice</span>
	    </div>
	  </th>

	  <th class="column-display">
	    <div class="name sort columntitle" data-sort="descr">
	      <span>Payment</span>
	    </div>
	  </th>

		</tr>
		</thead>
		<tbody id="fileList">
			<?php echo getBills();?>
		</tbody>

		<tbody>
			<tr><td colspan="7" class="centertr"><div id="history" class="btn btn-primary btn-flat">Load history</div></td></tr>
		</tbody>
		
	</table>

</fieldset>
