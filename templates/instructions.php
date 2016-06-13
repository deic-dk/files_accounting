<div class='pay-popup'>
	<?php 
	    // TODO 	
		$user =  \OCP\User::getUser();
		$charge = \OCA\Files_Accounting\Storage_Lib::getChargeForUserServers($user);
	?>
	<p> When you exceed your free quota, you will start paying <?php echo $charge['charge_home']; ?> per GB for the storage use in your home server. An invoice will be sent to you on day <?php echo \OCA\Files_Accounting\Storage_Lib::getBillingDayOfMonth(); ?> of the month. Your payment is due after <?php echo \OCA\Files_Accounting\Storage_Lib::getBillingNetDays(); ?> days.</p>

	<p>PayPal is a safe, convenient way to pay your invoices. You can either choose to pay with PayPal directly by clicking the <strong>Pay Now</strong> button in the table with your invoices or set up automatic PayPal payments to pay your invoice amount automatically.</p> 

	<h1>How automatic PayPal payments work</h1>
		<ul>
			<li>You can register for automatic payments with PayPal by clicking on the <strong>Preapprove Future Payments</strong> button.</li>

			<li>After approving the service, you will receive a preapproval key that verifies the agreement, which you can retrieve from the Paypal <strong>My preapproved payments</strong> page along with other information on the agreement.</li>

			<li>The respective amount is deducted from your PayPal account no sooner than DUE DAY days after you receive the invoice.</li>

			<li>You can track the payments in your PayPal account.</li>

			<li>The agreement is valid for 1 year. After that period, you can create a new agreement or directly pay the invoice. </li>

			<li>You can cancel the agreement any time on <strong>My preapproved payments</strong> page on PayPal. You should directly pay the invoices after that.</li>

		</ul>

	<div>If you need more information about paying with PayPal, contact <a href="https://www.paypal.com/us/webapps/helpcenter/helphub/home/" target="_blank" >PayPal Customer Support</a>.</div>

	<h1>General Info</h1>
		<ul>
			<li>You must have a PayPal account.</li>
			<li>You must be billed in <?php echo \OCA\Files_Accounting\Storage_Lib::getBillingCurrency(); ?>.</li>
			<li>If you have funds directly available in your PayPal account, a notification regarding the completion of the payment will be posted to your COMPANY NAME account within minutes.</li>
			<li>You can check the status of your payment in your PayPal account.</li>
		</ul>

	<h1>More Questions</h1>
	If you need more help or have a question to ask, please contact SOME MAIL. 
</div>
