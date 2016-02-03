<div id="chart"><script type="text/javascript" src="https://www.google.com/jsapi"></script>
    <script type="text/javascript">
	var d = new Date();
	var year = d.getFullYear();
	var arrayFromPHP = <?php
			$user = \OCP\User::getUser();
			$userStorage  = array();
			$year = isset($_GET['year'])?$_GET['year']:date('Y');
			$average_lines = \OCA\Files_Accounting\Util::userBill($user, $year);
                        foreach ($average_lines as $line) {
                                //$userRows = explode(" ", $line);
                                //if ($userRows[0] == $user) {
                                $month =  $line['month'];
                                if ($month != date('m')) {
                                        $averageMonth = (int)$line['average'];
                                        $averageMonthTrash = (int)$line['trashbin'];
				 	$fullmonth = date('F', strtotime("2000-$month-01"));	
					$fullmonth = substr($fullmonth, 0, 3);
					$userStorage[] = array($fullmonth, $averageMonth, $averageMonthTrash); 

                                }

                        }

			$userStorage[] = OCA\Files_Accounting\Storage_Lib::dailyUsageSum($user, $year);
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
        	usage.push(parseInt(arrayFromPHP[i][1])/1024);
        	trash.push(parseInt(arrayFromPHP[i][2])/1024);
  	}
	for (var i=0; i<usage.length; i++) {
		var data = new google.visualization.DataTable();
                data.addColumn('string', 'dates');
                data.addColumn('number', 'files');
                data.addColumn('number', 'trashbin');
		if (usage[i] < 1000) {
       			 for(i = 0; i < dates.length; i++){
                		data.addRow([dates[i], Math.round(usage[i]*100)/100, Math.round(trash[i]*100)/100]);
        		}
			var options = {
                        title: 'Average Storage History',
                        hAxis: {title: year,  titleTextStyle: {color: '#333'}},
			vAxis: {title: 'MB \n\n',  titleTextStyle: {color: '#333'}},
			width: 900
                        }; 
		}else {
                         for(i = 0; i < dates.length; i++){
                                data.addRow([dates[i], (Math.round(usage[i]*100)/100)/1024, (Math.round(trash[i]*100)/100)/1024]);
                        }
			var options = {
                        title: 'Average Storage History',
                        hAxis: {title: year,  titleTextStyle: {color: '#333'}},
			vAxis: {title: 'GB \n\n',  titleTextStyle: {color: '#333'}},
			width: 900
                        };
		}
			//var container = document.getElementById('chart_div');
			//container.style.display = 'block';
			//var chart = new google.visualization.AreaChart(container);
			//google.visualization.events.addListener(chart, 'ready', function () {
    				//container.style.display = 'none';
			//});
			//chart.draw(data, options);	
                        new google.visualization.AreaChart(document.getElementById('chart_div')).
                        draw(data, options);
                        break;

	}
//        	new google.visualization.AreaChart(document.getElementById('chart_div')).draw(data, options);
//	$(window).resize(function(){
  //		drawChart();
	//});
}
//drawChart();
    </script>
</div>
