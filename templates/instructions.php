<div class='pay-popup'>
	<?php 
		$user =  \OCP\User::getUser();
		$charge = \OCA\Files_Accounting\Storage_Lib::getChargeForUserServers($user);
		$currency = \OCA\Files_Accounting\Storage_Lib::getBillingCurrency();
		$backupServerID = \OCA\FilesSharding\Lib::lookupServerIdForUser($user,
				\OCA\FilesSharding\Lib::$USER_SERVER_PRIORITY_BACKUP_1);
	?>
	<p>When exceeding your free quota, you will be charged <?php echo $charge['charge_home']." ".$currency; ?>
	per GB used on this site<?php if(empty($backupServerID)):?>.<?php else:?>
	and <?php echo $charge['charge_backup']." ".$currency; ?> per GB used on your backup server.
	<?php endif;?>
	
	The billing is done on day <?php echo \OCA\Files_Accounting\Storage_Lib::getBillingDayOfMonth(); ?>
	of each month. Your payment is due after <?php echo \OCA\Files_Accounting\Storage_Lib::getBillingNetDays(); ?>
	days.</p>

	<p>PayPal is a safe, convenient way to pay your invoices. You can either choose to pay with PayPal directly
	by clicking the <strong>Pay Now</strong> button in the table with your invoices, or set up automatic 
	payments to pay your invoices automatically.</p> 

	<h1>How automatic PayPal payments work</h1>
		<ul>
			<li>You can register for automatic payments with PayPal by clicking on the
			<strong>Preapprove Future Payments</strong> button.</li>

			<li>After approving the service, you will receive a preapproval key that verifies the agreement,
			which you can retrieve from Paypal on the page <strong>My preapproved payments</strong> along with other
			information on the agreement.</li>

			<li>The respective amount is deducted from your PayPal account no later than
			<?php echo \OCA\Files_Accounting\Storage_Lib::getBillingNetDays(); ?> days after you
			receive the invoice.</li>

			<li>You can track the payments in your PayPal account.</li>

			<li>The agreement is valid for 1 year. After that period, you can create a new agreement. </li>

			<li>You can cancel the agreement any time on the PayPal page <strong>My preapproved payments</strong>.</li>

		</ul>

	<div>If you need more information about paying with PayPal, contact
	<a href="https://www.paypal.com/us/webapps/helpcenter/helphub/home/" target="_blank" >PayPal Customer Support</a>.</div>

	<h1>General Info</h1>
		<ul>
			<li>You must have a PayPal account.</li>
			<li>If you have funds directly available in your PayPal account, a notification regarding the completion
			of the payment will be posted to your account within minutes.</li>
			<li>You can check the status of your payment on at PayPal.</li>
		</ul>

	<h1>More Questions</h1>
	If you need more help, please contact
	<a target="_blank" href="mailto:<?php $issuerEmail = \OCA\Files_Accounting\Storage_Lib::getIssuerEmail(); echo $issuerEmail; ?>">
	<?php echo $issuerEmail; ?></a>. 
</div>
