
/**
 * Check if bills apearing in notifications dropdown have been paid. If not, reset priority to veryhigh and have them reappear.
 * We call this from the theme (deic-data.js), as binding to bootstrap events from here apparentlyt doesn't work.
 */
function checkPaid(){

	var unpaidArr = $('.unpaid_invoice').map(function() {
    return $(this).attr('activity_id');
 }).get();
		
	if(unpaidArr && unpaidArr.length>0){
		var unpaidActivityIDs = JSON.stringify(unpaidArr);
		$.ajax({
			type: 'POST',
			url: OC.filePath('user_notification', 'ajax', 'unseen.php'),
			data: {activity_ids : unpaidActivityIDs}, 
			cache: false,
			success: function(){
				$('.bell').addClass('ringing');
				$('.num-notifications').text(unpaidArr.length);
				}
			});
	}
	
}
