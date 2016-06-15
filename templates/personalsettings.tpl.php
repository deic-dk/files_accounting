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
	
	<div id="adaptive-payments">	
		<div data-paypal-button="true" class="inlineblock button">
			 Preapprove Future Payments
		</div> 
	</div>
	<span class="paypal-text">powered by <img src="https://www.paypalobjects.com/webstatic/en_US/i/buttons/pp-acceptance-small.png"/>
	</span>
	<a id="pay-info">What's this?</a>
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
