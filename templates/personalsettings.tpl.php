<fieldset id='storageSettings' class='section'>

  <h2>Storage Use</h2>
  <div id="chart_div"><script type="text/javascript" src="https://www.google.com/jsapi"></script>
    <script type="text/javascript">
	var arrayFromPHP = <?php
			$user=OCP\USER::getUser ();
			$userStorage  = array();
			$stmt = OC_DB::prepare ( "SELECT `month`, `average`, `trashbin` FROM `*PREFIX*files_accounting` WHERE `user` = ? " );
			$result = $stmt->execute ( array ($user));
			$average_lines = array ();
			while ( $row = $result->fetchRow () ) {
				$average_lines [] = array('average' => (int)$row['average'], 'trashbin' => (int)$row['trashbin'], 'month' => (int)$row['month']); 
			}
                                  foreach ($average_lines as $line) {
                                        //$userRows = explode(" ", $line);
                                        //if ($userRows[0] == $user) {
                                          $month =  $line['month'];
                                          if ($month != date('m')) {
                                                $month = (int)$month;
                                                $averageMonth = (int)$line['average'];
                                                $averageMonthTrash = (int)$line['trashbin'];
                                                switch ($month) {
                                                        case 01:
                                                                $userStorage[] = array('Jan', $averageMonth, $averageMonthTrash);
                                                                break;
                                                        case 02:
                                                                $userStorage[] = array('Feb', $averageMonth, $averageMonthTrash);
                                                                break;
                                                        case 03:
                                                                $userStorage[] = array('Mar', $averageMonth, $averageMonthTrash);
                                                                break;
                                                        case 04:
                                                                $userStorage[] = array('Apr', $averageMonth, $averageMonthTrash);
                                                                break;
                                                        case 05:
                                                                $userStorage[] = array('May', $averageMonth, $averageMonthTrash);
                                                                break;
                                                        case 06:
                                                                $userStorage[] = array('Jun', $averageMonth, $averageMonthTrash);
                                                                break;
                                                        case 07:
                                                                $userStorage[] = array('Jul', $averageMonth, $averageMonthTrash);
                                                                break;
                                                        case 08:
                                                                $userStorage[] = array('Aug', $averageMonth, $averageMonthTrash);
                                                                break;
                                                        case 09:
                                                                $userStorage[] = array('Sep', $averageMonth, $averageMonthTrash);
                                                                break;
                                                        case 10:
                                                                $userStorage[] = array('Oct', $averageMonth, $averageMonthTrash);
                                                                break;
                                                        case 11:
                                                                $userStorage[] = array('Nov', $averageMonth, $averageMonthTrash);
                                                                break;
                                                        case 12:
                                                                $userStorage[] = array('Dec', $averageMonth, $averageMonthTrash);
                                                                break;
                                                }

                                          }

                                  }

            $lines = file('/tank/data/owncloud/'.$user.'/diskUsageDaily.txt');
			$dailyUsage = array();
			$averageToday = 0 ;
			$averageTodayTrash = 0;
            foreach ($lines as $line_num => $line) {
                    $userRows = explode(" ", $line);
                    if ($userRows[0] == $user) {
						$month =  substr($userRows[1], 0, 2);
						if ($month == date('m')) { 
				           $month = (int)$month;
						   $dailyUsage[] = array('usage' => (int)$userRows[2], 'trash' => (int)$userRows[3], 'month' => $month);
						   $averageToday = array_sum(array_column($dailyUsage, 'usage')) / count(array_column($dailyUsage, 'usage'));
						   $averageTodayTrash = array_sum(array_column($dailyUsage, 'trash')) / count(array_column($dailyUsage, 'trash'));	
						}
					}
           }
			if ($averageToday != 0 ) {
			  $userStorage[] = array(date('M'), $averageToday, $averageTodayTrash);
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

