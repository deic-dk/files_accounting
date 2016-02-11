var d = new Date();
var year = d.getFullYear();
var arrayFromPHP = [];
$.ajax(OC.linkTo('files_accounting', 'ajax/plot.php'), {
	type: 'GET',
	data: {
		year: year
	},
	dataType:'json',
	success: function(jsondata){
		if(jsondata.status == 'success' ) {
			arrayFromPHP = jsondata.data;
                }else{
                        OC.dialogs.alert( jsondata.data.message , jsondata.data.title ) ;
                }
	},
	error:function(jsondata){
                alert("Unexpected error!");
        }
});
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
          data.addColumn('number', 'trash');
          if (usage[i] < 1000) {
                   for(i = 0; i < dates.length; i++){
                          data.addRow([dates[i], Math.round(usage[i]*100)/100, Math.round(trash[i]*100)/100]);
                   }
            	   var options = {
                              title: 'Average Storage History',
                              hAxis: {title: year,  titleTextStyle: {color: '#333'}},
		              vAxis: {title: 'MB \n\n',  titleTextStyle: {color: '#333'}},
            	              width: "100%"

                              };
          }else {
                for(i = 0; i < dates.length; i++){
    	            data.addRow([dates[i], (Math.round(usage[i]*100)/100)/1024, (Math.round(trash[i]*100)/100)/1024]);
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
    //      new google.visualization.AreaChart(document.getElementById('chart_div')).draw(data, options);
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
    drawChart();
});
