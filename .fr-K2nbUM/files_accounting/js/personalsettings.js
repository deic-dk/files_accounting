//function update_graph(year){
//	$.post(OC.filePath('files_accounting', 'ajax', 'actions.php'), {year : year, action : "loadgraph" },
  //              function ( jsondata ){
    //                    if(jsondata.status == 'success' ) {

      //                          $('#chart_div').append(jsondata.data.page);
        //                }else{
          //                      OC.dialogs.alert( jsondata.data.message , jsondata.data.title ) ;
            //            }
         //});	
//}
$(document).ready(function() {
//	var year = $("#list").val();
  //  update_graph(year);

	$("#history").on ("click", function () {
		var d = new Date();
		var year = d.getFullYear();
		$.post(OC.filePath('files_accounting', 'ajax', 'actions.php'), {action : "loadhistory", year: year} ,
                function ( jsondata ){
                        if(jsondata.status == 'success' ) {
				$("#billingtable").find("td.empty").remove();
				$('#billingtable').append(jsondata.data.page);
				$('.centertr').hide();

                        }else{
                                OC.dialogs.alert( jsondata.data.message , jsondata.data.title ) ;
                        }
		});
    	});

	$('#list').change(function() {
		var year = $(this).val();
	 	 $.post(OC.filePath('files_accounting', 'ajax', 'actions.php'), {year : year, action : "loadhistory" } ,
                function ( jsondata ){
                        if(jsondata.status == 'success' ) {
				$("#billingtable").find("tr:gt(0)").remove();
                                $('#billingtable').append(jsondata.data.page);
				$('#billingtable').find('tr').removeClass('unpaid');
				//$('#chart_div').slideToggle();
                        }else{
                                OC.dialogs.alert( jsondata.data.message , jsondata.data.title ) ;
                        }
                });


	});

	$('.load_history').on('click', function () {
		var action = 'downloadhistory';
		document.location.href = OC.linkTo('files_accounting', 'ajax/download.php') + '?action=' + action;
	});

	$('#billingtable').find('a.invoice-link').on('click', function () {
		var link = $(this).text();
		document.location.href = OC.linkTo('files_accounting', 'ajax/download.php') + '?link=' + link;
	});
	
	$('.activitysettings tr').eq(5).css('display','none');
})

