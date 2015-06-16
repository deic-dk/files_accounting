function add_charge_settings(charges, taxes){
	$.ajax(OC.linkTo('files_accounting','ajax/settingsactions.php'), {
		 type:'POST',
		  data:{
			 'action': 'addcharge', 'charges': charges, 'taxes': taxes
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
		add_charge_settings(charges, taxes);
	});
});

