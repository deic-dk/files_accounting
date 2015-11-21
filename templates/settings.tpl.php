<fieldset id="filesAccountingSettings" class="section">

  <h2><?php p($l->t('Files Accounting'));?></h2>
  <?php  
		$charge = OCP\Config::getAppValue('files_accounting', 'dkr_perGb', '');
		$taxes = OCP\Config::getAppValue('files_accounting', 'tax', '');
		$url = OCP\Config::getAppValue('files_accounting', 'url', '');
		$gift = OCP\Config::getAppValue('files_accounting', 'gift', '');
		echo "
			<table style='width:50%'>
  				<tr>
    					<td>
						<label for='charges'>Currency per GB </label>
						<br>
						<label for='taxes'>VAT </label>
						<br>
						<label for='url'>URL </label>
						<br>
						<label for='gift'>Gift Card</label>
						<br>
					</td>
    					<td>
						<input type='text' name='charges' id = 'charges' value=\"".$charge."\" >
                        			<br>
						<input type='text' name='taxes' id = 'taxes'  value=\"".$taxes."\"  >
                        			<br>
						<input type='text' name='url' id = 'url'  value=\"".$url."\"  >
                        			<br>
						<input type='text' name='gift' id='gift' value=\"".$gift."\" placeholder='Enter quota (ex: 2 GB)'>
						<br>
					</td> 
  				</tr>
			</table>
			<input type='submit' value='Save' name='billsubmit' id = 'billsubmit' original-title=''>
			<br>
			<label>Gift card for groups: &nbsp;</label>&nbsp<input type='text' name='group-gift' id='group-gift' placeholder='Enter quota (ex: 2 GB)'>&nbsp Search group :&nbsp
			<span class='search-user'><input id='user-gift' type='text' placeholder='Type name of group' class='ui-autocomplete-input' autocomplete='off'/></span>";
		?>
	
	
</fieldset>
