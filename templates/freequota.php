<?php
	$freequotaIsUserDefined = !empty($_['user_freequota']) &&
		array_search($_['user_freequota'], $_['quota_preset'])===false;
?>
<select name="size" data-inputtitle="<?php p($l->t('Please enter free storage quota (ex: "512 MB" or "12 GB")')) ?>" data-tipsy-gravity="s">
	<option value='none'>
		<?php p($l->t('None'));?>
	</option>
	<?php foreach($_['gift_quota_preset'] as $preset):?>
		<?php if($preset !== 'default'):?>
			<option value='<?php p($preset);?>'>
				<?php p($preset);?>
			</option>
		<?php endif;?>
	<?php endforeach;?>
	<?php if($freequotaIsUserDefined):?>
		<option selected="selected" value='<?php p($_['user_freequota']);?>'>
			<?php p($_['user_freequota']);?>
		</option>
	<?php endif;?>
	<option data-new value='other'>
		<?php p($l->t('Other'));?>
		...
	</option>
</select>
