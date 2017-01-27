OC.Groups = {
	group : [] ,
	initDropDown : function() {
		$('.ui-autocomplete-input').autocomplete({
			minLength : 2,
			source : function(search, response) {
				$.get(OC.filePath('files_accounting', 'ajax', 'groups.php'), {
					search : search.term
				},
				function(result) {
					if(result.status == 'success' && result.data.length > 0) {
						response(result.data);
					}
				});
			},
			focus : function(event, focused) {
				event.preventDefault();
			},
			select : function(event, selected) {
				var group = selected.item.value;
				$.post(OC.filePath('user_group_admin', 'ajax', 'actions.php'), {group : group, action : 'getinfo'},
					function ( jsondata ){
						if(jsondata.status == 'success' ) {
							$('.ui-autocomplete-input').val(jsondata.data.gid);
							$('#groupFreeQuota').val(jsondata.data.user_freequota)
							//OC.Groups.initDropDown() ;
						}
						else{
							OC.dialogs.alert( jsondata.data.message , jsondata.data.title ) ;
						}
					});
				return false;
		  },
		});
	}
};

function setDefaultFreeQuota(quota){
	$.ajax(OC.linkTo('files_accounting','ajax/setFreeQuota.php'), {
		 type:'POST',
		 data:{
			 'default': 'yes',  'freequota': quota
		 },
		 dataType:'json',
		 success: function(data){ },
		 error:function(data){
			 alert("Unexpected error!");
		 }
	});
}

function setGroupFreeQuota(group, quota){
	$.ajax(OC.linkTo('files_accounting','ajax/setFreeQuota.php'), {
		 type:'POST',
		 data:{
			 'group': group,  'freequota': quota
		 },
		 dataType:'json',
		 success: function(data){ },
		 error:function(data){
			 alert("Unexpected error!");
		 }
	});
}

function addAccountingScrollbar(){
	$('#filesAccountingSettings').width($(window).innerWidth()*0.85);
	$('#filesAccountingSettings .gift').width($(window).innerWidth()*0.85);
	var giftsHeight = $('#filesAccountingSettings #gifts').innerHeight()<600?
			$('#filesAccountingSettings #gifts').innerHeight():600;
	$('#filesAccountingSettings #gifts').height(giftsHeight);
}

function addGifts(data){
	$.post(OC.filePath('files_accounting', 'ajax', 'makeGifts.php'),
		data,
		function( jsondata ){
			if(jsondata.status == 'success' ) {
				// Render new rows
				location.reload();
			}
			else{
				OC.dialogs.alert(jsondata , 'Error') ;
			}
	});
}

function onQuotaSelect(ev) {
	var $select = $(ev.target);
	var quota = $select.val();
	// Nothing...
}

function deleteGift(code){
	$.ajax(OC.linkTo('files_accounting','ajax/deleteGift.php'), {
		 type:'POST',
		 data:{ 'code': code},
		 dataType:'json',
		 success: function(jsondata){ 
				if(jsondata.status == 'success' ) {
					location.reload();
				}
				else{
					OC.dialogs.alert(jsondata , 'Error') ;
				}
		 },
		 error:function(data){
			 alert("Unexpected error!");
		 }
	});
}

$(document).ready(function() {

	$('#filesAccountingSettings #defaultFreeQuotaSubmit').click(function() {
		quota = $('#filesAccountingSettings #defaultFreeQuota').val();
		setDefaultFreeQuota(quota);
	});

	$('#filesAccountingSettings #groupFreeQuotaSubmit').click(function() {
		group = $('#filesAccountingSettings #group').val();
		quota = $('#filesAccountingSettings #groupFreeQuota').val();
		setGroupFreeQuota(group, quota);
	});
	
	$('#filesAccountingSettings .add_gifts').click(function(ev){
		var form = $(this).closest('form');
		if(!form.find('[name="codes"]').val()){
			OC.dialogs.alert('You must fill in the number of gifts ' , 'Missing parameter') ;
			return false;
		}
		else if(form.find('[name="amount"]').length && !form.find('[name="amount"]').val().length ||
				form.find('[name="size"]').length && form.find('[name="size"]').val()=='none' ||
				form.find('[name="site"]').lenth && !form.find('[name="site"]').val().length ||
				form.find('[name="days"]').length && !form.find('[name="days"]').val().length){
			if(form.find('[name="amount"]').length){
				OC.dialogs.alert('You must fill in the amount' , 'Missing parameters') ;
			}
			else{
				OC.dialogs.alert('You must fill in size, site & days of validity ' , 'Missing parameters') ;
			}
			return false;
		}
		addGifts(form.serialize());
	});

	OC.Groups.initDropDown() ;
	addAccountingScrollbar();
	
	$('#filesAccountingSettings select[name="size"]').singleSelect().on('change', onQuotaSelect);
	
	$('#filesAccountingSettings .delete_gift').click(function(ev) {
		deleteGift($(ev.target).attr('code'));
	});

});

$(window).resize(function(){
	addAccountingScrollbar();
});
