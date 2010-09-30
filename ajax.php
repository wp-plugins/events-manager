<?php
//AJAX function 
function em_ajax_actions() {
	//Clean this up.... use a uniformed way of calling EM Ajax actions
 	if(isset($_REQUEST['dbem_ajax_action']) && $_REQUEST['dbem_ajax_action'] == 'booking_data') {
		if(isset($_REQUEST['id'])){
			$EM_Event = new EM_Event($_REQUEST['id']);
	     	echo "[ {bookedSeats:".$EM_Event->bookings->get_booked_seats().", availableSeats:".$EM_Event->bookings->get_available_seats()."}]";
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
		$locations = EM_Locations::get(array('eventful' => 1));
		$json_locations = array();
		foreach($locations as $location_key => $location) {
			$json_locations[$location_key] = $location->to_array();
			$json_locations[$location_key]['location_balloon'] = $location->output(get_option('dbem_location_baloon_format'));
		}
		echo EM_Object::json_encode($json_locations);
	 	die();   
 	}

	if(isset($_REQUEST['ajaxCalendar']) && $_REQUEST['ajaxCalendar']) {
		//FIXME if long events enabled originally, this won't show up on ajax call
		$args = array();
		if( isset($_REQUEST['full']) && $_REQUEST['full'] == 1 ) {
			$args['full'] = 1;
		}
		if( isset($_REQUEST['longevents']) && $_REQUEST['longevents'] ) {
			$args['long_events'] = 1;
		}
		if( isset($_REQUEST['calmonth']) ) {
			$args['month'] = $_REQUEST['calmonth'];
		}
		if( isset($_REQUEST['calyear']) ) {
			$args['year'] = $_REQUEST['calyear'];
		}
		echo EM_Calendar::get($args);
		die();
	}
}  
add_action('init','em_ajax_actions');

?>