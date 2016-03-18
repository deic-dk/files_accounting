<fieldset id='storageSettings' class='section'>
  <h2>Storage Use</h2>
  <div id="chart_div">
</div>

<div style="padding-top:25px;"><select id="list" name="yearList" method="POST"><option name='year' value=<?php echo date("Y"); ?> ><?php echo date("Y"); ?></option>
<?php $years = \OCA\Files_Accounting\Util::billYear(OCP\USER::getUser ());
	foreach ($years as $year) {
                echo "<option name='year' value=$year>$year</option>";
        }

?>
</option>
</select>
<label class="load_history button" href=# data-action="downloadhistory"
 >Daily History</label>
</div>
<div style="margin-top: 2%">
<form action="https://www.sandbox.paypal.com/cgi-bin/webscr" method="post" target="_top">
<input type="hidden" name="cmd" value="_s-xclick">
<input type="hidden" name="hosted_button_id" value="EPP2VQRP5XWF2">
<table>
<tr><td>Enter the maximum amount you want to pay each month</td></tr><tr><td><input type="text" name="max_amount" value="">
DKK</td></tr></table>
<table><tr><td align=center><i>Sign up for</i></td></tr><tr><td><input type="image" src="https://www.paypalobjects.com/en_US/GB/i/btn/btn_auto_billing_LG.gif" border="0" name="submit" alt="PayPal \xe2\x80\
x93 The safer, easier way to pay online."></td></tr></table>
<img alt="" border="0" src="https://www.sandbox.paypal.com/en_GB/i/scr/pixel.gif" width="1" height="1">
</form>

</div>
<div><?php 
	//\OCA\Files_Accounting\Stats::updateMonthlyAverage();
        $form = include "billing.php";
           ?>
</div>

</fieldset>
