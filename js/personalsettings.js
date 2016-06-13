var PAYMENT_STATUS_PAID = 1;
var PAYMENT_STATUS_PENDING = 2;

function add_download_links() {
	$('#billingtable').find('a.invoice-link').on('click', function () {
		var file = $(this).text();
		OC.redirect( OC.linkTo('files_accounting', 'ajax/getInvoice.php') + '?file=' + file);
	});
}

$(document).ready(function() {
	$("#adaptive-payments").on("click", function() {
	  $.ajax(OC.linkTo('files_accounting', 'ajax/adaptivePayments.php'), {
		type: 'POST',
		dataType: 'json',
		success: function(jsondata) {
		  window.location.href = jsondata.data.url;
		},
		error: function(jsondata) {
		  alert("Unexpected error!");
		}
	  });
	});

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
				year: year
			},
			dataType:'json',
			success: function(data){
				if(data) {
					$("#billingtable tr").not(':first').not(':last').remove();
					$('#billingtable tr:first').after(data);
					$('.centertr').hide();
					add_download_links();
				}
			},
			error: function(data) {
				alert("Unexpected error!");
			}
		});
	});

	$("#storageSettings #pay-info").on("click", function () {
		var html = "<div><div>How to pay</div>\
				<a class='oc-dialog-close close svg'></a>\
				<div class='pay-instructions'></div>\
				</div>";
		$(html).dialog({
			  dialogClass: "oc-dialog",
			  resizeable: false,
			  draggable: false,
			  height: 600,
			  width: 720
			});

		$('body').append('<div class="modalOverlay">');

		$('.oc-dialog-close').live('click', function() {
			$(".oc-dialog").remove();
			$('.modalOverlay').remove();
		});

		$('.ui-helper-clearfix').css("display", "none");

		$.ajax(OC.linkTo('files_accounting', 'ajax/getInstructions.php'), {
			type: 'GET',
			success: function(jsondata){
				if(jsondata) {
					$('.pay-instructions').html(jsondata.data.page);
				}
			},
			error: function(data) {
				alert("Unexpected error!");
			}
		});
	}); 

	add_download_links();
});


