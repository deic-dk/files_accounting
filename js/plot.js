google.load("visualization", "1", {packages:["corechart"]});
google.setOnLoadCallback(getData);
function getData() {
	var year = $('#storageSettings #list').val();
	$.ajax(OC.linkTo('files_accounting', 'ajax/getUsageData.php'), {
		type: 'GET',
		data: {
			year: year
		},
		dataType:'json',
		success: function(ret){
			if(jsondata.status == 'success' ) {
				drawGraph(ret.data, year);
			}
		},
		error: function(ret){
			alert("Unexpected error!");
		}
	});
}s

function drawGraph(data, year) {
	var usageUnit;
	var usageUnitStr;
	if (files_usage[files_usage.length-1]['files_usage'] < 1000) {
		usageUnit = Math.pow(1024, 2);
		usageUnitStr = "MB";
	}
	else{
		usageUnit = Math.pow(1024, 3);
		usageUnitStr = "GB";
	}
	var options = {
			title: 'Average Storage History',
			hAxis: {title: year,  titleTextStyle: {color: '#333'}},
			vAxis: {title: usageUnitStr+' \n\n',  titleTextStyle: {color: '#333'}},
			width:  "100%"
	};
	var data = new google.visualization.DataTable();
	data.addColumn('string', 'dates');
	data.addColumn('number', 'files');
	data.addColumn('number', 'trashbin');
	for (var i=0; i<data.length; i++) {
		date = data[i]['month']+'-'+data[i]['day'];
		files_usage = parseInt(data[i]['files_usage'])/usageUnit;
		trash_usage = parseInt(data[i]['trash_usage'])/usageUnit;
		data.addRowFromValues(date, files_usage, trash_usage);
	}	
	new google.visualization.AreaChart(document.getElementById('chart_div')).draw(data, options);
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
	getData();
});
