function add_charge_settings(charges, taxes, url){
	$.ajax(OC.linkTo('files_accounting','ajax/settingsactions.php'), {
		 type:'POST',
		  data:{
			 'action': 'addcharge', 'charges': charges, 'taxes': taxes, 'url': url
		 },
		 dataType:'json',
		 success: function(data){

		 },
		error:function(data){
			alert("Unexpected error!");
		}
	});
}



$(document).ready(function() {
 	$('#billsubmit').click(function() {
		charges = $('#charges').val();
		taxes = $('#taxes').val(); 
		url = $('#url').val();
		add_charge_settings(charges, taxes, url);
	});
});

