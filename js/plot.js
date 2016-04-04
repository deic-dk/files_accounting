google.load("visualization", "1", {packages:["corechart"]});
google.setOnLoadCallback(getData);
function getData() {
	var arrayFromPHP = [];
	var year = $('#storageSettings #list').val();
	$.ajax(OC.linkTo('files_accounting', 'ajax/plot.php'), {
        	type: 'GET',
        	data: {
                	year: year
        	},
        	dataType:'json',
        	success: function(jsondata){
                	if(jsondata.status == 'success' ) {
                        	arrayFromPHP = jsondata.data;
				drawGraph(arrayFromPHP, year);
                	}
        	},
        	error:function(jsondata){
                	alert("Unexpected error!");
        	}
	});
}

function drawGraph(userData, year) {
	var dates = [];
        var usage = [];
	var backup = [];
        var trash = [];
        for (var i=0; i<userData.length; i++) {
                dates.push(userData[i][0]);
                usage.push(parseInt(userData[i][1])/1024);
                trash.push(parseInt(userData[i][2])/1024);
		if (userData[i][3] != null) {
			backup.push(parseInt(userData[i][3])/1024);
		}else {
			backup.push(0);
		}
        }
        for (var i=0; i<usage.length; i++) {
          var data = new google.visualization.DataTable();
          data.addColumn('string', 'dates');
          data.addColumn('number', 'files');
          data.addColumn('number', 'trash');
	  data.addColumn('number', 'backup');
          if (usage[i] < 1000) {
                   for(i = 0; i < dates.length; i++){
                        data.addRow([dates[i], Math.round(usage[i]*100)/100, Math.round(trash[i]*100)/100, Math.round(backup[i]*100)/100]);
                   }
                   var options = {
                              title: 'Average Storage History',
                              hAxis: {title: year,  titleTextStyle: {color: '#333'}},
                              vAxis: {title: 'MB \n\n',  titleTextStyle: {color: '#333'}},
                              width: "100%"
                                                              };
          }else {
                for(i = 0; i < dates.length; i++){
                   data.addRow([dates[i], (Math.round(usage[i]*100)/100)/1024, (Math.round(trash[i]*100)/100)/1024, (Math.round(backup[i]*100)/100)/1024]);
                }
                var options = {
                              title: 'Average Storage History',
                              hAxis: {title: year,  titleTextStyle: {color: '#333'}},
                              vAxis: {title: 'GB \n\n',  titleTextStyle: {color: '#333'}},
                              width:  "100%"
                              };
          }
          new google.visualization.AreaChart(document.getElementById('chart_div')).
          draw(data, options);
          break;
        }	
}
//create trigger to resizeEnd event     
$(window).resize(function() {
    if(this.resizeTO) clearTimeout(this.resizeTO);
    this.resizeTO = setTimeout(function() {
        $(this).trigger('resizeEnd');
    }, 500);
});

//redraw graph when window resize is completed  
$(window).on('resizeEnd', function() {
    getData();
});
