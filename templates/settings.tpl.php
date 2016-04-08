<fieldset id="filesAccountingSettings" class="section">
	<h2><?php p($l->t("Files Accounting"));?></h2>
	<div>
		<label>Default free quota</label>
		<input type="text" id="defaultFreeQuota" value="<?php echo $_['default_freequota'];?>" placeholder="Enter quota (ex: 2 GB)">
		<input type="submit" value="Save" id="defaultFreeQuotaSubmit">
	</div>
	<div>
		<label>Free quota </label>
		<input type="text" id="groupFreeQuota" placeholder="Enter quota (ex: 2 GB)">Search
		<label>for group:</label>
		<span class="search-user">
			<input id="group" type="text" placeholder="Name of group" class="ui-autocomplete-input" autocomplete="off"/>
		</span>
		<input type="submit" value="Save" id="groupFreeQuotaSubmit">
	</div>
</fieldset>
