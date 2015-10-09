OC.Users = {
        user : [] ,
        initDropDown : function() {
        OC.Users.user[OC.Share.SHARE_TYPE_USER]  = [];
        OC.Users.user[OC.Share.SHARE_TYPE_GROUP] = [];

        $('.ui-autocomplete-input').autocomplete({
            minLength : 2,
            source : function(search, response) {
                $.get(OC.filePath('files_accounting', 'ajax', 'users.php'), {
                    fetch : 'getShareWith',
                    search : search.term,
                    itemShares : [OC.Users.user[OC.Share.SHARE_TYPE_USER]]
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
            var user = selected.item.value.shareWith;
	    var gift = $('#gift').val();
            $.post(OC.filePath('files_accounting', 'ajax', 'settingsactions.php'), { user : user, gift : gift, action : "adduser"} , function ( jsondata ){
              if(jsondata.status == 'success' ) {
                            $('.ui-autocomplete-input').val('');
                            OC.Users.user[OC.Share.SHARE_TYPE_USER].push(user);
                            OC.Users.initDropDown() ;
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
			 'action': 'addcharge', 'charges': charges, 'taxes': taxes, 'url': url, 'gift': gift
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
	$('#filesAccountingSettings .gift-all:checkbox').click(function() {
    		var $this = $(this);
		var gift = $('#gift').val();
    		if ($this.is(':checked')) {
			 $.ajax(OC.linkTo('files_accounting','ajax/settingsactions.php'), {
                 		type:'POST',
                  		data:{
                         		'action': 'selectall', 'gift': gift 
                 		},
                 		dataType:'json',
                 		success: function(data){
					window.alert('ok');
                 		},
                		error:function(data){
                        		alert("Unexpected error!");
                		}
        		});

    		} 
	});
	OC.Users.initDropDown() ;
});

