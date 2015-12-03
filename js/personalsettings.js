function update_graph(year){
	$.ajax(OC.linkTo('files_accounting','ajax/actions.php'), {
		type:'GET',
		data:{
			action: 'loadgraph',
			year:year
		},
		dataType: 'json',
		success: function(jsondata) {
			if(jsondata.status == 'success' ) {
				$('#chart_div').append(jsondata.data.page);
			}else {
				OC.dialogs.alert( jsondata.data.message , jsondata.data.title ) ;
			}
		}
	});
}

function download_invoice() {
	$('#billingtable').find('a.invoice-link').on('click', function () {
		var link = $(this).text();
		var owner = $("head").attr("data-user");
		getServerUrl('https://test.data.deic.dk', function(serverUrl){
		url = encodeURIComponent(serverUrl+'/apps/files_accounting/ws/getInvoice.php?filename='+link+'&user='+owner);
	 	proxy_url = OC.webroot+'/apps/files_sharding/download_proxy.php?url='+url+'&mode=native';
                OC.redirect(proxy_url)});
              // document.location.href = OC.linkTo('files_accounting', 'ajax/download.php') + '?link=' + link;
        });	
}

function getServerUrl(url, callback){
			$.ajax(OC.linkTo('files_accounting','ajax/actions.php'), {
				 type:'GET',
				  data:{
				          action: 'getserver', 	
					  server_url: url
				 },
				 dataType:'json',
				 success: function(s){
					 if(s.error){
						 alert(s.error);
					 }
					// if(s.same){
						 // If we're already on the same server as the home server of the owner of the file,
						 // just fall through.
						 //return true;
					// }
					callback(s.data.url);
				 },
				error:function(s){
					alert("Unexpected error!");
				}
			});
}

$(document).ready(function() {
//	var year = $("#list").val();
  //  update_graph(year);

	$("#billingtable #history").on ("click", function () {
		var d = new Date();
		var year = d.getFullYear();
		$.ajax(OC.linkTo('files_accounting', 'ajax/actions.php'), {
			type: 'GET',
			data: {
				action: 'loadhistory',
				year: year
			},
			dataType:'json',
			success: function(jsondata){
				if(jsondata.status == 'success' ) {
                                	$("billingtable").find("td.empty").remove();
                                	$("#billingtable").append(jsondata.data.page);
                                	$('.centertr').hide();
	                              	download_invoice();
                        	}else{
                                	OC.dialogs.alert( jsondata.data.message , jsondata.data.title ) ;
                        	}
			},
			error:function(jsondata){
                                alert("Unexpected error!");
                        }
		});

    	});

	$('#list').change(function() {
		var year = $(this).val();
		$.ajax(OC.linkTo('files_accounting', 'ajax/actions.php'), {
			type: 'GET',
			data: {
				action: 'loadhistory',
				year: year
			},
			dataType:'json',
			success: function(jsondata){
				if(jsondata.status == 'success' ) {
                                	$("#billingtable").find("tr:gt(0)").remove();
                                	$('#billingtable').append(jsondata.data.page);
                                	$('#billingtable').find('tr').removeClass('unpaid');
                //                	$('#chart_div').slideToggle();
                              		download_invoice();
                        	}else{
                                	OC.dialogs.alert( jsondata.data.message , jsondata.data.title ) ;
                        	}
			},
			error: function(jsondata) {
				alert("Unexpected error!");
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

