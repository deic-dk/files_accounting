<fieldset id='storageSettings' class='section' style="border-top-style: none;">
  <h2>Storage Use</h2>
  <div id="chart_div"><?php $plot = include "storageplot.php"; ?>
</div>


<div style="padding-top:25px;"><select id="list" name="yearList" method="POST"><option name='year' value=<?php echo date("Y"); ?> ><?php echo date("Y"); ?></option>
<?php $years = \OCA\Files_Accounting\Util::billYear(OCP\USER::getUser ());
	foreach ($years as $year) {
                echo "<option name='year' value=$year>$year</option>";
        }
?>
</option> 
</select></div>
<div><?php
        $form = include "billing.php";
           ?>
</div>

</fieldset>

