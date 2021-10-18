google.charts.load('current', {packages: ['corechart']});
google.charts.setOnLoadCallback(getData);

var dataTable;
var chart;
var options = {};

function getData() {
	var year = $('#storageSettings #years').val();
	$.ajax(OC.linkTo('files_accounting', 'ajax/getUsageData.php'), {
		type: 'GET',
		data: {
			year: year
		},
		dataType:'json',
		success: function(ret){
			if(ret.status == 'success' ) {
				drawGraph(ret.data, year);
			}
		},
		error: function(ret){
			alert("Unexpected error!");
		}
	});
}

function drawGraph(data, year) {
	var usageUnit;
	var usageUnitStr;
	if (data.length>0 && data[data.length-1]['files_usage'] < 1000) {
		usageUnit = Math.pow(1024, 2);
		usageUnitStr = "MB";
	}
	else{
		usageUnit = Math.pow(1024, 3);
		usageUnitStr = "GB";
	}
	ticks = [];
	 dataTable = new google.visualization.DataTable();
	dataTable.addColumn('string', 'dates');
	dataTable.addColumn('number', t('files_accounting','files'));
	dataTable.addColumn('number', t('files_accounting','trashbin'));
	// We'll limit the number of ticks on the x-axis.
	var divisor = Math.round(data.length/10*0.9* $('#content').innerWidth()/1200);
	var formatDate = new google.visualization.DateFormat({
    pattern: 'MMM d'
  });
	for (var i=0; i<data.length; i++) {
		date = data[i]['day']+'-'+data[i]['month']+'-'+data[i]['year'];
		dateShort = data[i]['day']+'-'+data[i]['month'];
		dateFormatted = formatDate.formatValue(new Date(data[i]['year'], data[i]['month'], data[i]['day']));
		files_usage = parseInt(data[i]['files_usage'])/usageUnit;
		trash_usage = parseInt(data[i]['trash_usage'])/usageUnit;
		//console.log(i+'%'+divisor+'='+(i%divisor));
		dataTable.addRow([{v: dateShort,  f: dateFormatted}, files_usage, trash_usage]);
		if(data[i]['day']=='1'){
			ticks.push({v: dateShort,  f: dateFormatted});
		}
	}	
	chart = new google.visualization.AreaChart(document.getElementById('chart_div'));
	options = {
			title: t('files_accounting', 'Storage history'),
			hAxis: {title: year,  titleTextStyle: {color: '#333'}, textStyle: {slantedText: true, slantedTextAngle: '40', fontSize: '12'}, ticks: ticks},
			vAxis: {title: usageUnitStr+' \n\n',  titleTextStyle: {color: '#333'}, textStyle: {fontSize: '12'}},
			//width:  '100%'
			width: 0.9* $('#content').innerWidth(),
	};
	chart.draw(dataTable, options);
}
//create trigger to resizeEnd event     
$(window).resize(function() {
	if(this.resizeTO){
		clearTimeout(this.resizeTO);
	}
	this.resizeTO = setTimeout(function() {
		$(this).trigger('resizeEnd');
	}, 500);
});

//redraw graph when window resize is completed  
$(window).on('resizeEnd', function() {
	options.width = 0.9* $('#content').innerWidth();
	//alert(options.width);
	typeof chart!='undefined' && chart.draw(dataTable, options);
});

$(document).ready(function(){
	$('a[href="#userapps"]').click(function(e){
		options.width = 0.9* $('#content').innerWidth();
		//alert(options.width);
		//chart.draw(dataTable, options);
	});
});

