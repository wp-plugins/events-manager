<?php
//AJAX function 
function em_ajax_actions() {
	//TODO Clean this up.... use a uniformed way of calling EM Ajax actions
	if( !empty($_REQUEST['em_ajax']) || !empty($_REQUEST['em_ajax_action']) ){
	 	if(isset($_REQUEST['dbem_ajax_action']) && $_REQUEST['dbem_ajax_action'] == 'booking_data') {
			if(isset($_REQUEST['id'])){
				$EM_Event = new EM_Event($_REQUEST['id']);
		     	echo "[{bookedSeats:".$EM_Event->get_bookings()->get_booked_seats().", availableSeats:".$EM_Event->get_bookings()->get_available_seats()."}]";
			} 
			die();
		}  
	 	if(isset($_REQUEST['em_ajax_action']) && $_REQUEST['em_ajax_action'] == 'get_location') {
			if(isset($_REQUEST['id'])){
				$EM_Location = new EM_Location($_REQUEST['id']);
				$location_array = $EM_Location->to_array();
				$location_array['location_balloon'] = $EM_Location->output(get_option('dbem_location_baloon_format'));
		     	echo EM_Object::json_encode($location_array);
			}
			die();
		}  
		if(isset($_REQUEST['query']) && $_REQUEST['query'] == 'GlobalMapData') {
			$locations = EM_Locations::get( $_REQUEST );
			$json_locations = array();
			foreach($locations as $location_key => $location) {
				$json_locations[$location_key] = $location->to_array();
				$json_locations[$location_key]['location_balloon'] = $location->output(get_option('dbem_map_text_format'));
			}
			echo EM_Object::json_encode($json_locations);
		 	die();   
	 	}
	
		if(isset($_REQUEST['ajaxCalendar']) && $_REQUEST['ajaxCalendar']) {
			//FIXME if long events enabled originally, this won't show up on ajax call
			echo EM_Calendar::output($_REQUEST);
			die();
		}
		
		//EM Ajax requests require this flag.
		if( is_admin() ){
			//Admin operations
			//Booking Actions
			global $EM_Booking;
			if( !empty($_REQUEST['bookings']) || is_object($EM_Booking) ){
				if( is_object($EM_Booking) ){
					$_REQUEST['bookings'] = $EM_Booking; //small hack to prevent unecessary db reads
				}
				$EM_Bookings = new EM_Bookings(); //Empty, not bound to event.
				if( $_REQUEST['action'] == 'bookings_approve' ){
					$EM_Bookings->approve($_REQUEST['bookings']);
					echo $EM_Bookings->feedback_message;
					die();
				}elseif($_REQUEST['action'] == 'bookings_reject'){
					$EM_Bookings->reject($_REQUEST['bookings']);
					echo $EM_Bookings->feedback_message;	
					die();			
				}elseif($_REQUEST['action'] == 'bookings_unapprove'){
					$EM_Bookings->unapprove($_REQUEST['bookings']);
					echo $EM_Bookings->feedback_message;	
					die();			
				}elseif($_REQUEST['action'] == 'bookings_delete'){
					//Just do it here, since we may be deleting bookings of different events.
					$result = false;
					if(EM_Object::array_is_numeric($_REQUEST['bookings'])){
						$results = array();
						foreach($_REQUEST['bookings'] as $booking_id){
							$EM_Booking = new EM_Booking($booking_id);
							$results[] = $EM_Booking->delete();
						}
						$result = !in_array(false,$results);
					}elseif( is_numeric($_REQUEST['bookings']) ){
						$EM_Booking = new EM_Booking($_REQUEST['bookings']);
						$result = $EM_Booking->delete();
					}elseif( is_object($EM_Booking) ){
						$result = $EM_Booking->delete();
					}
					if( $result ){
						echo __('Booking Deleted','dbem');
					}else{
						echo '<span style="color:red">'.__('Booking deletion unsuccessful','dbem').'</span>';
					}	
					die();			
				}
			}
			//Specific Oject Ajax
			if( !empty($_REQUEST['em_obj']) ){
				switch( $_REQUEST['em_obj'] ){
					case 'em_bookings_events_table':
					case 'em_bookings_pending_table':
					case 'em_bookings_confirmed_table':
						call_user_func($_REQUEST['em_obj']);
						break;
				}
				die();
			}
		}
	}
}  
add_action('init','em_ajax_actions');

?>