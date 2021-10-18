<fieldset id='storageSettings' class='section'>
	<h2><?php p($l->t('Storage Use'));?></h2>
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
	$prePaid = \OCA\Files_Accounting\Storage_Lib::getPrePaid($user);
	$thisYear = date("Y");
	foreach ($years as $year) {
		echo "<option value='".$year."' ".($year==$thisYear?"selected='selected'":"").">".$year."</option>";
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
					<span><?php p($l->t('Status'));?></span>
		    </div>
	  </th>

	  <th class="column-display">
	    <div class="size sort columntitle" data-sort="size">
	      <span><?php p($l->t('Amount'));?> (<?php p($billingCurrency);?>)</span>
	    </div>
	  </th>
	
	  <th class="column-display">
	    <div class="size sort columntitle" data-sort="size">
	      <span><?php p($l->t('Date'));?></span>
	    </div>
	  </th>
	
	  <th class="column-display">
	      <div class="size sort columntitle" data-sort="size">
	         <span><?php p($l->t('Due'));?></span>
	      </div>
	  </th>

	  <th class="column-display">
	      <div class="name sort columntitle" data-sort="descr">
	         <span><?php p($l->t('Period'));?></span>
	      </div>
	  </th>	

	  <th class="column-display">
	    <div class="name sort columntitle" data-sort="descr">
	      <span><?php p($l->t('Invoice'));?></span>
	    </div>
	  </th>

	  <th class="column-display">
	    <div class="name sort columntitle" data-sort="descr">
	      <span><?php p($l->t('Payment'));?></span>
	    </div>
	  </th>

		</tr>
		</thead>
		<tbody id="fileList">
			<?php echo getBills();?>
		</tbody>

		<tbody>
			<tr><td colspan="7" class="centertr"><div id="history" class="btn btn-primary btn-flat"><?php p($l->t('Show all'));?></div></td></tr>
		</tbody>
		
	</table>
	
	<div id="adaptive-payments" data-paypal-button="true" class="inlineblock button">
		<?php p($l->t('Preapprove future payments'));?>
	</div> 
	<span class="paypal-text"><?php p($l->t("powered by"));?>&nbsp;
	<img src="https://www.paypalobjects.com/webstatic/en_US/i/buttons/pp-acceptance-small.png"/>
	</span>
	<a id="pay-info"><?php p($l->t("What's this?"))?></a>
	
	<?php if(!empty($memberGroups)):
	$i = 0;
	foreach($memberGroups as $group){
		if(!empty($group['user_freequota'])){
			if($i==0){
				echo "<hr><h3><b>".$l->t("Group usage")."</b></h3>";
			}
			$usageStats = \OCA\FilesSharding\Lib::buildFileStorageStatistics('/', $user, null, $group['gid']);
			$usedSpace = \OCP\Util::humanFileSize($usageStats['usedSpace']);
			echo "<div class='quotarow'>".$group['gid'].":<b> ".$usedSpace."</b> of ".$group['user_freequota']."</div>";
			++$i;
		}
	}?>	<?php endif;?>
	<?php if(!empty($ownerGroups)):?>
	<?php
	$i = 0;
	foreach($ownerGroups as $group){
		if(!empty($group['user_freequota'])){
			if($i==0){
				echo "<hr><h3><b>".$l->t("Total usage of owned groups")."</b></h3>";
			}
			echo "<div class='quotarow'>".$group['gid'].":<b> ".
			\OCP\Util::humanFileSize(\OC_User_Group_Admin_Util::getGroupUsage($group['gid']))."</b></div>";
			++$i;
		}
	}
	?>
	<?php endif;?>
	<?php if($prePaid>0):?>
	<hr><div><label><?php p($l->t('Prepaid'));?>:</label><label><?php echo($prePaid." ".$billingCurrency);?></label></div>
	<?php endif;?>
	<hr><div>
	<label><?php p($l->t('Gift code'));?>:</label><input type="text" id="giftCode" value="" placeholder="<?php p($l->t('Enter gift code'));?>" />
		<div id="giftCodeRedeem" class="inlineblock button">
			<?php p($l->t('Redeem'));?>
		</div> 
	</div>
	
</fieldset>
