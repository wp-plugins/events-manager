<?php
//AJAX function 
function em_ajax_actions() {
	//TODO Clean this up.... use a uniformed way of calling EM Ajax actions
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
}  
add_action('init','em_ajax_actions');

?>