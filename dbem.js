$j=jQuery.noConflict();   

function remove_booking() {
	eventId = ($j(this).parents('table:first').attr('id').split("-"))[3]; 
	idToRemove = ($j(this).parents('tr:first').attr('id').split("-"))[1];     
	$j.ajax({
  	  type: "POST",
	    url: "admin.php?page=people&action=remove_booking",
	    data: "booking_id="+ idToRemove,
	    success: function(){  
				
				$j('tr#booking-' + idToRemove).fadeOut('slow');
                update_booking_data();
	   		}
	 	});         
	  	

}   
                  
function update_booking_data () {
  	$j.getJSON("admin.php?page=people&dbem_ajax_action=booking_data",{id: eventId, ajax: 'true'}, function(data){
  	  	booked = data[0].bookedSeats;
  	    available = data[0].availableSeats; 
		$j('td#booked-seats').text(booked);
		$j('td#available-seats').text(available);          
  		});  
}

$j(document).ready( function() {
    // Managing bookings delete operations 
	$j('a.bookingdelbutton').click(remove_booking);
});

jQuery(document).ready( function($) {
	jQuery('#mtm_add_tag').click( function(event){
		event.preventDefault();
		//Get All meta rows
			var metas = jQuery('#mtm_body').children();
		//Copy first row and change values
			var metaCopy = jQuery(metas[0]).clone(true);
			newId = metas.length + 1;
			metaCopy.attr('id', 'mtm_'+newId);
			metaCopy.find('a').attr('rel', newId);
			metaCopy.find('[name=mtm_1_ref]').attr({
				name:'mtm_'+newId+'_ref' ,
				value:'' 
			});
			metaCopy.find('[name=mtm_1_content]').attr({ 
				name:'mtm_'+newId+'_content' , 
				value:'' 
			});
			metaCopy.find('[name=mtm_1_name]').attr({ 
				name:'mtm_'+newId+'_name' ,
				value:'' 
			});
		//Insert into end of file
			jQuery('#mtm_body').append(metaCopy);
		//Duplicate the last entry, remove values and rename id
	});
	
	jQuery('#mtm_body a').click( function(event){
		event.preventDefault();
		//Only remove if there's more than 1 meta tag
		if(jQuery('#mtm_body').children().length > 1){
			//Remove the item
			jQuery(jQuery(this).parent().parent().get(0)).remove();
			//Renumber all the items
			jQuery('#mtm_body').children().each( function(i){
				metaCopy = jQuery(this);
				oldId = metaCopy.attr('id').replace('mtm_','');
				newId = i+1;
				metaCopy.attr('id', 'mtm_'+newId);
				metaCopy.find('a').attr('rel', newId);
				metaCopy.find('[name=mtm_'+ oldId +'_ref]').attr('name', 'mtm_'+newId+'_ref');
				metaCopy.find('[name=mtm_'+ oldId +'_content]').attr('name', 'mtm_'+newId+'_content');
				metaCopy.find('[name=mtm_'+ oldId +'_name]').attr( 'name', 'mtm_'+newId+'_name');
			});
		}else{
			metaCopy = jQuery(jQuery(this).parent().parent().get(0));
			metaCopy.find('[name=mtm_1_ref]').attr('value', '');
			metaCopy.find('[name=mtm_1_content]').attr('value', '');
			metaCopy.find('[name=mtm_1_name]').attr( 'value', '');
			alert("If you don't want any meta tags, just leave the text boxes blank and submit");
		}
	});
});