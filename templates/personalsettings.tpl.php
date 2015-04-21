<?php
//OCP\Util::addScript('user_storage','personalsettings');
?>
<fieldset id='storageSettings' class='section'>

  <h2>Storage Use</h2>
  <div id="chart_div" style="width: 100%; height: 100%;"><script type="text/javascript" src="https://www.google.com/jsapi"></script>
    <script type="text/javascript">
	var arrayFromPHP = <?php
				$user=OCP\USER::getUser ();
                        $lines = file('/tank/data/owncloud/s141277@student.dtu.dk/data/diskUsage.txt');
			$userStorage  = array();
			$janUsage  = array();
			$febUsage = array();
			$marUsage = array();
			$aprUsage = array();
			$mayUsage = array();
			$junUsage = array();
			$julUsage = array();
			$augUsage = array();
			$septUsage = array();
			$octUsage = array();
			$novUsage = array();
			$decUsage = array();	
                        foreach ($lines as $line_num => $line) {
                                $userRows = explode(" ", $line);
                                if ($userRows[0] == $user) {
					$month =  substr($userRows[1], 0, 2);
				        $month = (int)$month;
					switch ($month) {
						case 01:
							$janUsage[] = array('usage' => (int)$userRows[2], 'trash' => (int)$userRows[3]);
 
							$averageJan = array_sum(array_column($janUsage, 'usage')) / count(array_column($janUsage, 'usage'));
             				                $averageJanTrash = array_sum(array_column($janUsage, 'trash')) / count(array_column($janUsage, 'trash'));
							break;
						case 02:
							$febUsage[] = array('usage' => (int)$userRows[2], 'trash' => (int)$userRows[3]);
							$averageFeb = array_sum(array_column($febUsage, 'usage')) / count(array_column($febUsage, 'usage'));
                                			$averageFebTrash = array_sum(array_column($febUsage, 'trash')) / count(array_column($febUsage, 'trash'));
                                                        break;
						case 03:
							$marUsage[] = array('usage' => (int)$userRows[2], 'trash' => (int)$userRows[3]);
							$averageMar = array_sum(array_column($marUsage, 'usage')) / count(array_column($marUsage, 'usage'));
                           			        $averageMarTrash = array_sum(array_column($marUsage, 'trash')) / count(array_column($marUsage, 'trash'));
                                                        break;
						case 04:
							$aprUsage[] = array('usage' => (int)$userRows[2], 'trash' => (int)$userRows[3]);
 
							$averageApr = array_sum(array_column($aprUsage, 'usage')) / count(array_column($aprUsage, 'usage'));
            			                        $averageAprTrash = array_sum(array_column($aprUsage, 'trash')) / count(array_column($aprUsage, 'trash'));
                                                        break;
						case 05:
							$mayUsage[] = array('usage' => (int)$userRows[2], 'trash' => (int)$userRows[3]);
							$averageMay = array_sum(array_column($mayUsage, 'usage')) / count(array_column($mayUsage, 'usage'));
                           			        $averageMayTrash = array_sum(array_column($mayUsage, 'trash')) / count(array_column($mayUsage, 'trash'));
                                                        break;
						case 06:
							$junUsage[] = array('usage' => (int)$userRows[2], 'trash' => (int)$userRows[3]);
							$averageJun = array_sum(array_column($junUsage, 'usage')) / count(array_column($junUsage, 'usage'));
                             			       $averageJunTrash = array_sum(array_column($junUsage, 'trash')) / count(array_column($junUsage, 'trash'));
                                                        break;
						case 07:
							$julUsage[] = array('usage' => (int)$userRows[2], 'trash' => (int)$userRows[3]);
							$averageJul = array_sum(array_column($julUsage, 'usage')) / count(array_column($julUsage, 'usage'));
                       				         $averageJulTrash = array_sum(array_column($julUsage, 'trash')) / count(array_column($julUsage, 'trash'));
                                                        break;
						case 08:
							$augUsage[] =array('usage' => (int)$userRows[2], 'trash' => (int)$userRows[3]);
							$averageAug = array_sum(array_column($augUsage, 'usage')) / count(array_column($augUsage, 'usage'));
            			                        $averageAugTrash = array_sum(array_column($augUsage, 'trash')) / count(array_column($augUsage, 'trash'));
                                                        break;
						case 09:
							$septUsage[] = array('usage' => (int)$userRows[2], 'trash' => (int)$userRows[3]);
							$averageSept = array_sum(array_column($septUsage, 'usage')) / count(array_column($septUsage, 'usage'));
             			                        $averageSeptTrash = array_sum(array_column($septUsage, 'trash')) / count(array_column($septUsage, 'trash'));
                                                        break;
						case 10:
							$octUsage[] = array('usage' => (int)$userRows[2], 'trash' => (int)$userRows[3]);
							$averageOct = array_sum(array_column($octUsage, 'usage')) / count(array_column($octUsage, 'usage'));
            				                $averageOctTrash = array_sum(array_column($octUsage, 'trash')) / count(array_column($octUsage, 'trash'));
                                                        break;
						case 11:
							$novUsage[] =array('usage' => (int)$userRows[2], 'trash' => (int)$userRows[3]);
							$averageNov = array_sum(array_column($novUsage, 'usage')) / count(array_column($novUsage, 'usage'));
            		      	 	                $averageNovTrash = array_sum(array_column($novUsage, 'trash')) / count(array_column($novUsage, 'trash'));
                                                        break;
						case 12:
							$decUsage[] = array('usage' => (int)$userRows[2], 'trash' => (int)$userRows[3]);
							$averageDec = array_sum(array_column($decUsage, 'usage')) / count(array_column($decUsage, 'usage'));
            				                $averageDecTrash = array_sum(array_column($decUsage, 'trash')) / count(array_column($decUsage, 'trash'));
                                                        break;
				}
                                }
                        }
if ($averageJan != 0 ) {
	$userStorage[] = array("Jan", $averageJan, $averageJanTrash);
}
if ($averageFeb != 0) {
	$userStorage[] = array("Feb", $averageFeb, $averageFebTrash);
} 
if ($averageMar != 0 ) {
	$userStorage[] = array("Mar", $averageMar, $averageMarTrash);
}
if ($averageApr != 0 ) {
	$userStorage[] = array("Apr", $averageApr, $averageAprTrash);
}
if ($averageMay != 0) {
	$userStorage[] = array("May", $averageMay, $averageMayTrash);
}
if ($averageJun != 0) {
	$userStorage[] = array("Jun", $averageJun, $averageJunTrash);
}
if ($averageJul != 0 ) {
	$userStorage[] = array("Jul", $averageJul, $averageJulTrash);
}
if ($averageAug != 0 ) {
	$userStorage[] = array("Aug", $averageAug, $averageAugTrash);
}
if ($averageSept != 0 ) {
	$userStorage[] = array("Sep", $averageSept, $averageSeptTrash);
} 
if ($averageOct != 0 ) {
	$userStorage[] = array("Oct", $averageOct, $averageOctTrash);
}
if ($averageNov != 0 ) {
	$userStorage[] = array("Nov", $averageNov, $averageNovTrash);
}
if ($averageDec != 0 ) {
	$userStorage[] = array("Dec", $averageDec, $averageDecTrash);
}
			
                        echo json_encode($userStorage);
                        ?>;
      google.load("visualization", "1", {packages:["corechart"]});
      google.setOnLoadCallback(drawChart);
      function drawChart() {
  	var dates = [];
  	var usage = [];
  	var trash = [];
  	for (var i=0; i<arrayFromPHP.length; i++) {
        	dates.push(arrayFromPHP[i][0]);
        	usage.push(parseInt(arrayFromPHP[i][1])/1000);
        	trash.push(parseInt(arrayFromPHP[i][2])/1000);
  	}
	for (var i=0; i<usage.length; i++) {
		if (usage[i] < 1000) {
			 var data = new google.visualization.DataTable();
		        data.addColumn('string', 'dates');
       			data.addColumn('number', 'usage');
       			data.addColumn('number', 'trash');
       			 for(i = 0; i < dates.length; i++){
                		data.addRow([dates[i], usage[i], trash[i]]);
        		}
 
        		var options = {
          		title: 'Average Storage History',
          		hAxis: {title: 'Months',  titleTextStyle: {color: '#333'}},
          		vAxis: {title: 'MB \n\n',  titleTextStyle: {color: '#333'}},
          		width: 900 
        		};
  			new google.visualization.AreaChart(document.getElementById('chart_div')).
    			draw(data, options);
			break;
		}else {
			var data = new google.visualization.DataTable();
                        data.addColumn('string', 'dates');
                        data.addColumn('number', 'usage');
                        data.addColumn('number', 'trash');
                         for(i = 0; i < dates.length; i++){
                                data.addRow([dates[i], usage[i]/1000, trash[i]/1000]);
                        }

                        var options = {
                        title: 'Average Storage History',
                        hAxis: {title: 'Months',  titleTextStyle: {color: '#333'}},
                        vAxis: {title: 'GB \n\n',  titleTextStyle: {color: '#333'}},
                        width: 900 
                        };
                        new google.visualization.AreaChart(document.getElementById('chart_div')).
                        draw(data, options);
                        break;
		}
	}
	function resizeHandler () {
        	new google.visualization.AreaChart(document.getElementById('chart_div')).draw(data, options);
       }
       if (window.addEventListener) {
           window.addEventListener('resize', resizeHandler);
       }
       else if (window.attachEvent) {
           window.attachEvent('onresize', resizeHandler);
       }
}
    </script>
</div>
</fieldset>
