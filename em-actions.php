<?php
/**
 * Performs actions on init. This works for both ajax and normal requests, the return results depends if an em_ajax flag is passed via POST or GET.
 */
function em_init_actions() {
	//NOTE - No EM objects are globalized at this point, as we're hitting early init mode.
	//TODO Clean this up.... use a uniformed way of calling EM Ajax actions
	if( !empty($_REQUEST['em_ajax']) || !empty($_REQUEST['em_ajax_action']) ){
		if(isset($_REQUEST['em_ajax_action']) && $_REQUEST['em_ajax_action'] == 'get_location') {
			if(isset($_REQUEST['id'])){
				$EM_Location = new EM_Location($_REQUEST['id']);
				$location_array = $EM_Location->to_array();
				$location_array['location_balloon'] = $EM_Location->output(get_option('dbem_location_baloon_format'));
		     	echo EM_Object::json_encode($location_array);
			}
			die();
		}   
	 	if(isset($_REQUEST['em_ajax_action']) && $_REQUEST['em_ajax_action'] == 'delete_ticket') {
			if(isset($_REQUEST['id'])){
				$EM_Ticket = new EM_Ticket($_REQUEST['id']);
				$result = $EM_Ticket->delete();
				if($result){
					$result = array('result'=>true);
				}else{
					$result = array('result'=>false, 'error'=>$EM_Ticket->feedback_message);
				}
			}else{
				$result = array('result'=>false, 'error'=>__('No ticket id provided','dbem'));	
			}			
		    echo EM_Object::json_encode($result);
			die();
		} 
		if(isset($_REQUEST['query']) && $_REQUEST['query'] == 'GlobalMapData') {
			$EM_Locations = EM_Locations::get( $_REQUEST );
			$json_locations = array();
			foreach($EM_Locations as $location_key => $EM_Location) {
				$json_locations[$location_key] = $EM_Location->to_array();
				$json_locations[$location_key]['location_balloon'] = $EM_Location->output(get_option('dbem_map_text_format'));
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
		if( is_admin() && is_user_logged_in() ){
			//Admin operations
			global $EM_Booking;
			//TODO multiple deletion won't work in ajax
			if( !empty($_REQUEST['action']) && (!empty($_REQUEST['bookings']) || !empty($_REQUEST['booking_id'])) ){
				$allowed_actions = array('bookings_approve'=>'approve','bookings_reject'=>'reject','bookings_unapprove'=>'unapprove', 'bookings_delete'=>'delete');
				if( array_key_exists($_REQUEST['action'], $allowed_actions) ){
					$action = $allowed_actions[$_REQUEST['action']];
					//Just do it here, since we may be deleting bookings of different events.
					if( !empty($_REQUEST['bookings']) && EM_Object::array_is_numeric($_REQUEST['bookings'])){
						$results = array();
						foreach($_REQUEST['bookings'] as $booking_id){
							$EM_Booking = new EM_Booking($booking_id);
							$result = $EM_Booking->$action();
							$results[] = $result;
							if( !in_array(false, $results) || !$result ){
								$feedback = $EM_Booking->feedback_message;
							}
						}
						$result = !in_array(false,$results);
					}elseif( !empty($_REQUEST['bookings']) && is_numeric($_REQUEST['bookings'])){
						$EM_Booking = new EM_Booking($_REQUEST['bookings']);
						$result = $EM_Booking->$action();
						$feedback = $EM_Booking->feedback_message;
					}elseif( is_object($EM_Booking) ){
						$result = $EM_Booking->$action();
						$feedback = $EM_Booking->feedback_message;
					}
					if( $result ){
						echo $feedback;
					}else{
						echo '<span style="color:red">'.$feedback.'</span>';
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
	//Event Actions
	if( !empty($_REQUEST['action']) && substr($_REQUEST['action'],0,5) == 'event' ){
		global $EM_Event, $EM_Notices;
		//Load the event object, with saved event if requested
		if( !empty($_REQUEST['event_id']) ){
			$EM_Event = new EM_Event($_REQUEST['event_id']);
		}else{
			$EM_Event = new EM_Event();
		}
		if( $_REQUEST['action'] == 'event_save' && current_user_can('edit_events') ){
			//Check Nonces
			if( is_admin() ){
				if( !wp_verify_nonce($_REQUEST['_wpnonce'] && 'event_save') ) check_admin_referer('trigger_error');				
			}else{
				if( !wp_verify_nonce($_REQUEST['_wpnonce'] && 'event_save') ) exit('Trying to perform an illegal action.');
			}
			//Grab and validate submitted data
			if ( $EM_Event->get_post() && $EM_Event->save() ) { //EM_Event gets the event if submitted via POST and validates it (safer than to depend on JS)
				$EM_Notices->add_confirm($EM_Event->feedback_message);
				if( is_admin() ){
					$page = !empty($_REQUEST['pno']) ? $_REQUEST['pno']:'';
					$scope = !empty($_REQUEST['scope']) ? $_REQUEST['scope']:'';
					//wp_redirect( get_bloginfo('wpurl').'/wp-admin/admin.php?page=events-manager&pno='.$page.'&scope='.$scope.'&message='.urlencode($EM_Event->feedback_message));
				}else{
					$redirect = !empty($_REQUEST['redirect_to']) ? $_REQUEST['redirect_to'] : wp_get_referer();
					wp_redirect( $redirect );
				}
				$events_result = true;
			}else{
				foreach($EM_Event->get_errors() as $error){
					$EM_Notices->add_error( $error );	
				}
				$events_result = false;				
			}
		}
		if ( $_REQUEST['action'] == 'event_duplicate' ) {
			global $EZSQL_ERROR;
			$EM_Event = $EM_Event->duplicate();
			if( $EM_Event === false ){
				$EM_Notices->add_error($EM_Event->errors, true);
			}else{
				if( $EM_Event->id == $_REQUEST['event_id'] ){
					$EM_Notices->add_confirm($EM_Event->feedback_message ." ". sprintf(__('You are now viewing the duplicated %s.', 'dbem'),__('event','dbem')), true);
				}else{
					$EM_Notices->add_confirm($EM_Event->feedback_message, true);
				}
			}
		}
		if ( $_REQUEST['action'] == 'event_delete' ) { 
			//DELETE action
			$selectedEvents = !empty($_REQUEST['events']) ? $_REQUEST['events']:'';
			if(  EM_Object::array_is_numeric($selectedEvents) ){
				$events_result = EM_Events::delete( $selectedEvents );
			}elseif( is_object($EM_Event) ){
				$events_result = $EM_Event->delete();
			}		
			$plural = (count($selectedEvents) > 1) ? __('Events','dbem'):__('Event','dbem');
			if($events_result){
				$message = ( is_object($EM_Event) ) ? $EM_Event->feedback_message : sprintf(__('%s successfully deleted.','dbem'),$plural);
				$EM_Notices->add_confirm( $message );
			}else{
				$message = ( is_object($EM_Event) ) ? $EM_Event->errors : sprintf(__('%s could not be deleted.','dbem'),$plural);
				$EM_Notices->add_confirm( $message );		
			}
		}elseif( $_REQUEST['action'] == 'event_approve' ){ 
			//Approve Action
			$events_result = $EM_Event->approve();
			if($events_result){
				$EM_Notices->add_confirm( $EM_Event->feedback_message );
			}else{
				$EM_Notices->add_error( $EM_Event->errors );			
			}
		}
		
		//AJAX Exit
		if( isset($events_result) && !empty($_REQUEST['em_ajax']) ){
			if( $events_result ){
				$return = array('result'=>true, 'message'=>$EM_Event->feedback_message);
			}else{		
				$return = array('result'=>false, 'message'=>$EM_Event->feedback_message, 'errors'=>$EM_Event->errors);
			}	
		}
	}
	
	//Location Actions
	if( !empty($_REQUEST['action']) && substr($_REQUEST['action'],0,8) == 'location' ){
		global $EM_Location, $EM_Notices;
		//Load the location object, with saved event if requested
		if( !empty($_REQUEST['location_id']) ){
			$EM_Location = new EM_Location($_REQUEST['location_id']);
		}else{
			$EM_Location = new EM_Location();
		}
		if( $_REQUEST['action'] == 'location_save' && current_user_can('edit_locations') ){
			//Check Nonces
			em_verify_nonce('location_save');
			//Grab and validate submitted data
			$EM_Location->get_post();
			if ( $EM_Location->save() ) { //EM_location gets the location if submitted via POST and validates it (safer than to depend on JS)
				$EM_Notices->add_confirm($EM_Location->feedback_message);
				$result = true;
			}else{
				foreach($EM_Location->get_errors() as $error){
					$EM_Notices->add_error( $error );	
				}
				$result = false;				
			}
		}elseif( !empty($_REQUEST['action']) && $_REQUEST['action'] == "location_delete" ){
			//delete location
			//get object or objects			
			if( !empty($_REQUEST['locations']) || !empty($_REQUEST['location_id']) ){
				$args = !empty($_REQUEST['locations']) ? $_REQUEST['locations']:$_REQUEST['location_id'];
				$locations = EM_Locations::get($args);
				foreach($locations as $location) {
					if( !$location->delete() ){
						$EM_Notices->add_error($location->get_errors());
						$errors = true;
					}			
				}
				if( empty($errors) ){
					$result = true;
					$location_term = ( count($locations) > 1 ) ?__('Locations', 'dbem') : __('Location', 'dbem'); 
					$EM_Notices->add_confirm( sprintf(__('%s successfully deleted', 'dbem'), $location_term) );
				}else{
					$result = false;
				}
			}
		}
		if( isset($result) && $result && !empty($_REQUEST['em_ajax']) ){
			$return = array('result'=>true, 'message'=>$EM_Location->feedback_message);
			echo EM_Object::json_encode($return);
			die();
		}elseif( isset($result) && !$result && !empty($_REQUEST['em_ajax']) ){
			$return = array('result'=>false, 'message'=>$EM_Location->feedback_message, 'errors'=>$EM_Notices->get_errors());
			echo EM_Object::json_encode($return);
			die();
		}
	}
}  
add_action('init','em_init_actions');

?>