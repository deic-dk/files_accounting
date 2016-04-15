var PAYMENT_STATUS_PAID = 1;
var PAYMENT_STATUS_PENDING = 2;

function add_download_links() {
	$('#billingtable').find('a.invoice-link').on('click', function () {
		var file = $(this).text();
		OC.redirect( OC.linkTo('files_accounting', 'ajax/getInvoice.php') + '?file=' + file);
	});
}

$(document).ready(function() {

	$("#billingtable #history").on ("click", function () {
		var year = $('#storageSettings #years').val();
		$.ajax(OC.linkTo('files_accounting', 'ajax/getBills.php'), {
			type: 'GET',
			data: {
				status: PAYMENT_STATUS_PAID,
				year: year
			},
			dataType:'json',
			success: function(data){
				if(data) {
					$("#billingtable").find("td.empty").remove();
					$("#billingtable").append(data);
					$('.centertr').hide();
					add_download_links();
				}
			},
			error:function(jsondata){
				alert("Unexpected error!");
				}
		});
	});

	$('#storageSettings #years').change(function() {
		var year = $(this).val();
		$.ajax(OC.linkTo('files_accounting', 'ajax/getBills.php'), {
			type: 'GET',
			data: {
				status: PAYMENT_STATUS_PENDING,
				year: year
			},
			dataType:'json',
			success: function(data){
				if(jsondata.status == 'success' ) {
					$("#billingtable tr").not(':first').not(':last').remove();
					$('#billingtable tr:first').after(data);
					add_download_links();
				}
			},
			error: function(jsondata) {
				alert("Unexpected error!");
			}
		});
	});
	add_download_links();
});


