<fieldset id="filesAccountingSettings" class="section">

  <h2><?php p($l->t('Files Accounting'));?></h2>
  <?php  
		$charge = OCP\Config::getAppValue('files_accounting', 'dkr_perGb', '');
		$taxes = OCP\Config::getAppValue('files_accounting', 'tax', '');
		echo "
  			<label for='charges'>Kroner per GB </label>
  			<input type='text' name='charges' id = 'charges' value=\"".$charge."\" >
  			<br>
  			<label for='taxes'>VAT </label>
  			<input type='text' name='taxes' id = 'taxes'  value=\"".$taxes."\"  >
  			<br>
  			<input type='submit' value='Save' name='billsubmit' id = 'billsubmit' original-title=''>";
		?>
	
	
</fieldset>