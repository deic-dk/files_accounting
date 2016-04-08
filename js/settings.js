OC.Groups = {
	group : [] ,
	initDropDown : function() {
		OC.Groups.group[OC.Share.SHARE_TYPE_USER]  = [];
		OC.Groups.group[OC.Share.SHARE_TYPE_GROUP] = [];
		
		$('.ui-autocomplete-input').autocomplete({
			minLength : 2,
			source : function(search, response) {
				$.get(OC.filePath('files_accounting', 'ajax', 'groups.php'), {
					fetch : 'getShareWith',
					search : search.term,
					itemShares : [OC.Groups.group[OC.Share.SHARE_TYPE_USER]]
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
				var group = selected.item.value.shareWith;
				var groupFreeQuota = $('#groupFreeQuota').val();
				$.post(OC.filePath('files_accounting', 'ajax', 'setFreeQuota.php'), {group : group, groupFreeQuota : groupFreeQuota},
					function ( jsondata ){
						if(jsondata.status == 'success' ) {
							$('.ui-autocomplete-input').val('');
							OC.Groups.group[OC.Share.SHARE_TYPE_USER].push(group);
							OC.Groups.initDropDown() ;
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

	OC.Groups.initDropDown() ;
});
