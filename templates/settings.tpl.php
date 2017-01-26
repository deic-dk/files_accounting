<fieldset id="filesAccountingSettings" class="section">
	<h2><?php p($l->t("Files Accounting"));?></h2>
	<div>
		<label>Default free quota</label>
		<input type="text" id="defaultFreeQuota" value="<?php echo $_['default_freequota'];?>" placeholder="Enter quota (ex: 2 GB)">
		<input type="submit" value="Save" id="defaultFreeQuotaSubmit">
	</div>
	<div>
		<label>Group:</label>
		<span class="search-user">
			<input id="group" type="text" placeholder="Name of group" class="ui-autocomplete-input" autocomplete="off"/>
		</span>
		<label>Free quota: </label>
		<input type="text" id="groupFreeQuota" placeholder="Enter quota (ex: 2 GB)">
		<input type="submit" value="Save" id="groupFreeQuotaSubmit">
	</div>
	
	<?php
	OC_Util::addScript( 'core', 'multiselect' );
	OC_Util::addScript( 'core', 'singleselect' );
	OC_Util::addScript('core', 'jquery.inview');
	?>
	
	<?php
	function print_gift($code, $amount, $size, $site, $status, $creation_time,
			$claim_expiration_time, $redemption_time, $days, $user, $currency){
		print('<label name="code">'.$code.'</label> /
		<label name="amount">'.$amount.'</label>
		<label>'.$currency.'</label> / '.
		'<label name="size">'.$size.'</label> /
		<label name="site">'.$site.'</label> /
		<label name="status">'.$status.'</label> /
		<label name="creation_time">'.$creation_time.'</label> /
		<label name="claim_expiration_time">'.$claim_expiration_time.'</label> /
		<label name="redemption_time">'.$redemption_time.'</label> /
		<label name="days">'.$days.'</label> /
		<label name="user">'.$user.'</label>');
	}
	?>
	
	<br />
	
	<div>
	<form>
	<label>Add
	<input name="codes" type="text" value="1" width="3" placeholder="" />
	storage gifts:</label>
	<div class="gift" id="new_storage_gifts">
		<?php if(\OCP\App::isEnabled('files_sharding')){
			$tmpl = new OCP\Template("files_accounting", "freequota");
			$quotaPreset = OC_Appconfig::getValue('files_accounting', 'gift_quota_preset', '10 GB, 100 GB, 1 TB');
			$quotaPresetArr = explode(',', $quotaPreset);
			$tmpl->assign( 'gift_quota_preset' , $quotaPresetArr );
			print $tmpl->fetchPage();
			print '<select name="site">';
			foreach($_['sites'] as $site){
				print '<option value="'.$site['site'].'">'.$site['site'].'</option>';
			}
			print '</select>';
		}
		?>
		<input name="days" type="text" value="" placeholder="Days of validity" /> /
		<input name="suffix" type="text" value="" placeholder="Suffix" /> /
		<input name="expires" type="text" value="" placeholder="Days until claim expires" />
		<label class="add_gifts btn btn-flat">Add</label>
	</div>
	</form>
	</div>
	
	
	<br />

	<div>
	<form>
	<label>Add
	<input name="codes" type="text" value="1" width="3" placeholder="" />
	credit gifts:</label>
	<div class="gift" id="new_credit_gifts">
		<input name="amount" type="text" value="" placeholder="Amount" />
		<?php print($_['currency']);?> /
		<input name="suffix" type="text" value="" placeholder="Suffix" /> /
		<input name="expires" type="text" value="" placeholder="Days until claim expires" />
		<label class="add_gifts btn btn-flat">Add</label>
	</div>
	</form>
	</div>
	
	<br />
		
	<div><label>Gift codes:</label></div>
	<label class='code'>Code</label> /
	<label class='amount'>Amount</label> /
	<label class='size'>Size</label> /
	<label class='site'>Site</label> /
	<label class='status'>Status</label> /
	<label class='creation_time'>Creation time</label> /
	<label class='claim_expiration_time'>Claim expiration time</label> /
	<label class='redemption_time'>Redemption time</label> /
	<label class='days'>Days</label> /
	<label class='user'>User</label>
	<div id="gifts">
	<?php foreach ($_['gifts'] as $gift){
		print('<div class="gift" id="'.$gift['code'].'">');
		print_gift($gift['code'], $gift['amount'], $gift['size'], $gift['site'],
		$gift['status'], $gift['creation_time'], $gift['claim_expiration_time'],
		$gift['redemption_time'], $gift['days'], $gift['user'], $_['currency']);
		print('<label class="delete_gift btn btn-flat">Delete</label><div class="dialog" display="none"></div>');
		print('</div>');
	}
	?>
	</div>
	
</fieldset>
