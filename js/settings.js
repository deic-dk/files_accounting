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
                }, function(result) {
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
	    	var groupGift = $('#group-gift').val();
            $.post(OC.filePath('files_accounting', 'ajax', 'settingsactions.php'), { group : group, groupGift : groupGift, action : "addgroup"} , function ( jsondata ){
              if(jsondata.status == 'success' ) {
                            $('.ui-autocomplete-input').val('');
                            OC.Groups.group[OC.Share.SHARE_TYPE_USER].push(group);
                            OC.Groups.initDropDown() ;
              }else{
                OC.dialogs.alert( jsondata.data.message , jsondata.data.title ) ;

              }
            });

            return false;
          },
        });
    }
};

function add_charge_settings(charges, taxes, url, gift){
	$.ajax(OC.linkTo('files_accounting','ajax/settingsactions.php'), {
		 type:'POST',
		  data:{
			 action: 'addcharge', 'charges': charges, 'taxes': taxes, 'url': url, 'gift': gift
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
		gift = $('#gift').val();
		add_charge_settings(charges, taxes, url, gift);
	});
	OC.Groups.initDropDown() ;
});

