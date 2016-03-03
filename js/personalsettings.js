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
		$.ajax(OC.linkTo('files_accounting','ajax/actions.php'), {
                	type:'GET',
                	data:{
                        	action: 'checkmaster'
               		},
                	dataType: 'json',
                	success: function(jsondata) {
                               var isMaster = jsondata.data;	
				if (isMaster) {
					document.location.href = OC.linkTo('files_accounting', 'ajax/download.php') + '?link=' + link;
				}else{
					callMasterInternalUrl( function(masterUrl){
                       	 			var uri = OC.webroot+'/apps/files_accounting/ws/getInvoice.php?filename='+link+'&user='+owner;
                        			var redirect_url = '';
                        			if(typeof masterUrl == 'undefined'){
                                			redirect_url = uri;
                        			}
                        			else{
                                			url = encodeURIComponent(masterUrl+uri);
                                			redirect_url = OC.webroot+'/apps/files_sharding/download_proxy.php?url='+url+'&mode=native';
                        			}
                        			OC.redirect(redirect_url);
                			});
				}
                	}
			error: function(jsondata){
                                alert("Unexpected error!");
                        }
        	});
  	});
}

function callMasterInternalUrl(callback){
			$.ajax(OC.linkTo('files_sharding','ajax/get_master_url.php'), {
				 type:'GET',
				  data:{
				  	internal: true,
					user_id: $("head").attr("data-user")
				  },
				 dataType:'json',
				 success: function(s){
					 if(s.error){
						 // files_sharding probably not installed
						 callback();
					 }
					callback(s.url);
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


