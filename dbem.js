$j=jQuery.noConflict();   

function remove_booking() {
	console.log('ecco qui'); 
	eventId = ($j(this).parents('table:first').attr('id').split("-"))[3]; 
	idToRemove = ($j(this).parents('tr:first').attr('id').split("-"))[1];     
    console.log('event: ' + eventId);
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