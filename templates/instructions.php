<div class='pay-popup'>
	<?php 
		$user =  \OCP\User::getUser();
		$charge = \OCA\Files_Accounting\Storage_Lib::getChargeForUserServers($user);
		$currency = \OCA\Files_Accounting\Storage_Lib::getBillingCurrency();
		$backupServerID = \OCA\FilesSharding\Lib::lookupServerIdForUser($user,
				\OCA\FilesSharding\Lib::$USER_SERVER_PRIORITY_BACKUP_1);
	?>
	<?php if($l->getLanguageCode()=='da'){?>
	<p>Når dit pladsforbrug overstiger din gratis kvote, vil du blive faktureret <?php echo $charge['charge_home']." ".$currency; ?>
	per GB brugt her<?php if(empty($backupServerID)):?>.<?php else:?>
	og <?php echo $charge['charge_backup']." ".$currency; ?> per GB brugt på din backup server.
	<?php endif;?>
	Hvis du har et aktivt forhåndsgodkendt beløb, f.eks. fra en gave-kode, vil denne blive modregnet på din næste regning.
	
	Afregningen foretages den <?php echo \OCA\Files_Accounting\Storage_Lib::getBillingDayOfMonth(); ?>.
	hver måned. Betaling er forfalden indenfor <?php echo \OCA\Files_Accounting\Storage_Lib::getBillingNetDays(); ?>
	dage.</p>

	<p>PayPal er en sikker, nem måde at betale på. Du kan vælge enten at betale direkte med PayPal, ved at klikke på
	<strong>Betal nu</strong>-knappen i oversigtstabellen, eller forhåndsgodkende betaling af fremtidige regninger -
	som så vil blive betalt automatisk hver måned.</p> 

	<h1>Hvordan forhåndsgodkendt betaling virker</h1>
		<ul>
			<li>Du kan tilmelde dig automatisk betaling med PayPal ved at klikke på
			<strong>Forhåndsgodkend fremtidige betalinger</strong>.</li>

			<li>Efter din godkendelse, vil du modtage en kode der verificerer aftalen,
			som du kan hente fra PayPal på siden <strong>Faste betalinger</strong> sammen med yderligere
			oplysninger om aftalen.</li>

			<li>Det pågældende beløb vil blive hævet på din PayPal account ikke senere end
			<?php echo \OCA\Files_Accounting\Storage_Lib::getBillingNetDays(); ?> dage efter modtagelse af
			regningen.</li>

			<li>Du kan følge betalingerne på din PayPal-konto.</li>

			<li>Aftalen er gyldig et år. Herefter kan du lave en ny. </li>

			<li>Du kan til enhver tid ophæve aftalen på din PayPal-side <strong>Faste betalinger</strong>.</li>

		</ul>

	<div>Hvis du har brug for yderligere oplysninger om betaling med PayPal, så kontakt
	<a href="https://www.paypal.com/us/webapps/helpcenter/helphub/home/" target="_blank" >PayPal Customer Support</a>.</div>

	<h1>Generel Information</h1>
		<ul>
			<li>Du skal have en PayPal-konto.</li>
			<li>Hvis der er midler på din PayPal-konto, vil du modtage advisering om betaling indenfor få minutter.</li>
			<li>Du kan checke status af dine betalinger hos PayPal.</li>
		</ul>

	<h1>Flere spørgsmål?</h1>
	Hvis du har brug for yderligere hjælp, så kontakt
	<a target="_blank" href="mailto:<?php $issuerEmail = \OCA\Files_Accounting\Storage_Lib::getIssuerEmail(); echo $issuerEmail; ?>">
	<?php echo $issuerEmail; ?></a>.
	<?php }
	else{?>
	<p>When your storage use exceeds your free quota, you will be charged <?php echo $charge['charge_home']." ".$currency; ?>
	per GB used on this site<?php if(empty($backupServerID)):?>.<?php else:?>
	and <?php echo $charge['charge_backup']." ".$currency; ?> per GB used on your backup server.
	<?php endif;?>
	If you have a prepaid amount active, e.g. from a gift code, this will be discounted when issuing
	the next bill.
	
	The billing is done on day <?php echo \OCA\Files_Accounting\Storage_Lib::getBillingDayOfMonth(); ?>
	of each month. Your payment is due after <?php echo \OCA\Files_Accounting\Storage_Lib::getBillingNetDays(); ?>
	days.</p>

	<p>PayPal is a safe, convenient way to pay your invoices. You can either choose to pay with PayPal directly
	by clicking the <strong>Pay now</strong> button in the table with your invoices, or set up automatic 
	payments to pay your invoices automatically.</p> 

	<h1>How automatic PayPal payments work</h1>
		<ul>
			<li>You can register for automatic payments with PayPal by clicking on the
			<strong>Preapprove Future Payments</strong> button.</li>

			<li>After approving the service, you will receive a preapproval key that verifies the agreement,
			which you can retrieve from Paypal on the page <strong>Recurring payments</strong> along with other
			information on the agreement.</li>

			<li>The respective amount is deducted from your PayPal account no later than
			<?php echo \OCA\Files_Accounting\Storage_Lib::getBillingNetDays(); ?> days after you
			receive the invoice.</li>

			<li>You can track the payments in your PayPal account.</li>

			<li>The agreement is valid for 1 year. After that period, you can create a new agreement. </li>

			<li>You can cancel the agreement any time on the PayPal page <strong>Recurring payments</strong>.</li>

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

	<h1>More Questions?</h1>
	If you need more help, please contact
	<a target="_blank" href="mailto:<?php $issuerEmail = \OCA\Files_Accounting\Storage_Lib::getIssuerEmail(); echo $issuerEmail; ?>">
	<?php echo $issuerEmail; ?></a>.
	<?php }?>
</div>
