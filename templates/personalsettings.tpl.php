<fieldset id='storageSettings' class='section'>
	<h2>Storage Use</h2>
	<select id="years">
	<?php
	include "billing.php";
	$hostedButtonID = \OCA\Files_Accounting\Storage_Lib::getPayPalHostedButtonID();
	$billingCurrency = \OCA\Files_Accounting\Storage_Lib::getBillingCurrency();
	$user = OCP\USER::getUser();
	$years = \OCA\Files_Accounting\Storage_Lib::accountedYears($user);
	$memberGroups = array();
	$ownerGroups = array();
	if(\OCP\App::isEnabled('files_sharding') && OCP\App::isEnabled('user_group_admin')){
		$memberGroups = \OC_User_Group_Admin_Util::getUserGroups($user, true, true, true);
		$ownerGroups = \OC_User_Group_Admin_Util::getOwnerGroups($user, true);
	}
	$thisYear = date("Y");
	foreach ($years as $year) {
		echo "<option value=".$year.($year==$thisYear?"selected='selected'":"").">".$year."</option>";
	}
	?>
	</select>

	<div id="chart_div">
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
	
	<div id="adaptive-payments" data-paypal-button="true" class="inlineblock button">
		Preapprove Future Payments
	</div> 
	<span class="paypal-text">powered by <img src="https://www.paypalobjects.com/webstatic/en_US/i/buttons/pp-acceptance-small.png"/>
	</span>
	<a id="pay-info">What's this?</a>
	
	<?php if(!empty($memberGroups)):?>
	<hr>
	<h3><b>Group usage</b></h3>
	<?php foreach($memberGroups as $group){
		if(!empty($group['user_freequota'])){
			$usageStats = \OCA\FilesSharding\Lib::buildFileStorageStatistics('/', $user, null, $group['gid']);
			$usedSpace = \OCP\Util::humanFileSize($usageStats['usedSpace']);
			echo "<div class='quotarow'>".$group['gid'].":<b> ".$usedSpace."</b> of ".$group['user_freequota']."</div>";
		}
	}?>	<?php endif;?>
	<?php if(!empty($ownerGroups)):?>
	<hr>
	<h3><b>Total usage of owned groups</b></h3>
	<?php foreach($ownerGroups as $group){
		if(!empty($group['user_freequota'])){
			echo "<div class='quotarow'>".$group['gid'].":<b> ".
			\OC_User_Group_Admin_Util::getGroupUsage($group['gid'])."</b></div>";
		}
	}?>
	<?php endif;?>

	
	

</fieldset>
