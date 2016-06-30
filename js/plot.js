google.load("visualization", "1", {packages:["corechart"]});
google.setOnLoadCallback(getData);

var dataTable;
var chart;
var options;

function getData() {
	var year = $('#storageSettings #list').val();
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
 options = {
			title: ' Storage History',
			hAxis: {title: year,  titleTextStyle: {color: '#333'}},
			vAxis: {title: usageUnitStr+' \n\n',  titleTextStyle: {color: '#333'}},
			//width:  '100%'
	};
	 dataTable = new google.visualization.DataTable();
	dataTable.addColumn('string', 'dates');
	dataTable.addColumn('number', 'files');
	dataTable.addColumn('number', 'trashbin');
	for (var i=0; i<data.length; i++) {
		date = data[i]['day']+'-'+data[i]['month']+'-'+data[i]['year'];
		files_usage = parseInt(data[i]['files_usage'])/usageUnit;
		trash_usage = parseInt(data[i]['trash_usage'])/usageUnit;
		dataTable.addRow([date, files_usage, trash_usage]);
	}	
	chart = new google.visualization.AreaChart(document.getElementById('chart_div'));
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
	chart.draw(dataTable, options);
});

$(document).ready(function(){
	$('a[href="#userapps"]').click(function(e){
		options.width = 0.9* $('#content').innerWidth();
		//alert(options.width);
		chart.draw(dataTable, options);
	});
});

