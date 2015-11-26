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

function download_invoice() {
	$('#billingtable').find('a.invoice-link').on('click', function () {
               var link = $(this).text();
               document.location.href = OC.linkTo('files_accounting', 'ajax/download.php') + '?link=' + link;
        });	
}

$(document).ready(function() {
//	var year = $("#list").val();
  //  update_graph(year);

	$("#billingtable #history").on ("click", function () {
		var d = new Date();
		var year = d.getFullYear();
		$.post(OC.filePath('files_accounting', 'ajax', 'actions.php'), {action : "loadhistory", year: year} ,
                function ( jsondata ){
                        if(jsondata.status == 'success' ) {
				$("billingtable").find("td.empty").remove();
				$("#billingtable").append(jsondata.data.page);
				$('.centertr').hide();
				download_invoice();
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
				download_invoice();
                        }else{
                                OC.dialogs.alert( jsondata.data.message , jsondata.data.title ) ;
                        }
                });


	});

	$('.load_history').on('click', function () {
		var action = 'downloadhistory';
		document.location.href = OC.linkTo('files_accounting', 'ajax/download.php') + '?action=' + action;
	});

 	download_invoice();
	
	$('.activitysettings tr').eq(5).css('display','none');
})

