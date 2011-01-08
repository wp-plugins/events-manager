<?php
/**
 * Deals with the booking info for an event
 * @author marcus
 *
 */
class EM_Bookings extends EM_Object{
	
	/**
	 * Array of EM_Booking objects for a specific event
	 * @var array
	 */
	var $bookings = array();
	/**
	 * Event ID
	 * @var int
	 */
	var $event_id;
	/**
	 * Number of seats for this event
	 * @var int
	 */
	var $seats;
	
	var $feedback_message = "";
	var $errors = array();
	
	/**
	 * Creates an EM_Bookings instance, 
	 * @param EM_Event $event
	 * @return null
	 */
	function EM_Bookings( $event = false ){
		if( is_object($event) && get_class($event) == "EM_Event" ){ //Creates a blank bookings object if needed
			global $wpdb;
			$this->event_id = $event->id;
			$this->seats = $event->seats;
			$sql = "SELECT * FROM ". $wpdb->prefix . EM_BOOKINGS_TABLE ." b, ". $wpdb->prefix . EM_PEOPLE_TABLE ." p WHERE event_id ='{$this->event_id}' AND p.person_id=b.person_id";
			$bookings = $wpdb->get_results($sql, ARRAY_A);
			foreach ($bookings as $booking){
				$this->bookings[] = new EM_Booking($booking);
			}
		}
	}
	
	/**
	 * Add a booking into this event (or add seats if person already booked this), checking that there's enough space for the event
	 * @param $EM_Booking
	 * @return boolean
	 */
	function add( $EM_Booking ){
		global $wpdb,$EM_Mailer;
		if ( $this->get_available_seats() >= $EM_Booking->seats ) {  
			$EM_Booking->event_id = $this->event_id;
			// checking whether the booker has already booked places
			$previous_booking = $this->find_previous_booking( $EM_Booking );
			$email = false;
			if ( is_object($previous_booking) ) { 
				//Previously booked, so we add these seats to the booking
				$new_seats = $EM_Booking->seats;
				$EM_Booking = $previous_booking;
				$EM_Booking->seats += $new_seats;
				$result = $EM_Booking->save();
				if($result){
					//remove old booking
					foreach($this->bookings as $key=>$booking){
						if($booking->id == $EM_Booking->id){ unset($this->bookings[$key]); }
					}
					$this->bookings[] = $EM_Booking;
					$email = $EM_Booking->email();
				}
			} else {
				//New booking, so let's save the booking
				$result = $EM_Booking->save();
				if($result){
					$this->bookings[] = $EM_Booking;
					$email = $EM_Booking->email();
				}
			}
			if($result){
				//Success
				if( get_option('dbem_bookings_approval') == 1 ){
					$this->feedback_message = __('Booking successful, pending confirmation (you will also receive an email once confirmed).', 'dbem');
				}else{
					$this->feedback_message = __('Booking successful.', 'dbem');
				}
				if(!$email){
					$this->feedback_message .= ' '.__('However, there were some problems whilst sending confirmation emails to you and/or the event contact person. You may want to contact them directly and letting them know of this error.', 'dbem');
					if( current_user_can('activate_plugins') ){
						if( is_array($this->errors) && count($this->errors) > 0 ){
							$this->feedback_message .= '<br/><strong>Errors:</strong> (only admins see this message)<br/><ul><li>'. implode('</li><li>', $EM_Mailer->errors).'</li></ul>';
						}else{
							$this->feedback_message .= '<br/><strong>No errors returned by mailer</strong> (only admins see this message)';
						}
					}
				}
				return true;
			}else{
				//Failure
				$this->errors[] = "<strong>".__('Booking could not be created','dbem').":</strong><br />". implode('<br />', $EM_Booking->errors);
			}
		} else {
			 $this->errors[] = __('Booking cannot be made, not enough seats available!', 'dbem');
			 return false;
		} 
	}
	
	/**
	 * Delete bookings on this id
	 * @return boolean
	 */
	function delete(){
		global $wpdb;
		$result = $wpdb->query("DELETE FROM ".$wpdb->prefix.EM_BOOKINGS_TABLE." WHERE event_id='{$this->event_id}'");
		return ($result);
	}

	/**
	 * Returns number of available seats for this event. If approval of bookings is on, will include pending bookings depending on em option.
	 * @return int
	 */
	function get_available_seats(){
		$booked_seats = 0;
		if( get_option('dbem_bookings_approval_reserved') == 1 ){
			return $this->seats - $this->get_booked_seats() - $this->get_pending_seats();
		}else{	
			return $this->seats - $this->get_booked_seats();	
		}
	}

	/**
	 * Returns number of booked seats for this event. If approval of bookings is on, will return number of booked confirmed seats.
	 * @return int
	 */
	function get_booked_seats(){
		$booked_seats = 0;
		foreach ( $this->bookings as $booking ){
			if( get_option('dbem_bookings_approval') == 0 || $booking->status == 1 ){
				$booked_seats += $booking->seats;
			}
		}
		return $booked_seats;
	}
	
	/**
	 * Gets number of pending seats awaiting approval. Will return 0 if booking approval is not enabled.
	 * @return int
	 */
	function get_pending_seats(){
		if( get_option('dbem_bookings_approval') == 0 ){
			return 0;
		}
		$pending = 0;
		foreach ( $this->bookings as $booking ){
			if($booking->status == 0){
				$pending += $booking->seats;
			}
		}
		return $pending;
	}
	
	/**
	 * Gets number of bookings (not seats). If booking approval is enabled, only the number of approved bookings will be shown.
	 * @return array EM_Booking
	 */
	function get_bookings(){
		if( get_option('dbem_bookings_approval') == 0 ){
			return $this->bookings;
		}
		$confirmed = array();
		foreach ( $this->bookings as $booking ){
			if($booking->status == 1){
				$confirmed[] = $booking;
			}
		}
		return $confirmed;		
	}
	
	/**
	 * Get pending bookings. If booking approval is disabled, will return no bookings. 
	 * @return array EM_Booking
	 */
	function get_pending_bookings(){
		if( get_option('dbem_bookings_approval') == 0 ){
			return array();
		}
		$pending = array();
		foreach ( $this->bookings as $booking ){
			if($booking->status == 0){
				$pending[] = $booking;
			}
		}
		return $pending;
	}	
	
	/**
	 * Get rejected bookings. If booking approval is disabled, will return no bookings. 
	 * @return array EM_Booking
	 */
	function get_rejected_bookings(){
		if( get_option('dbem_bookings_approval') == 0 ){
			return array();
		}
		$pending = array();
		foreach ( $this->bookings as $booking ){
			if($booking->status == 2){
				$pending[] = $booking;
			}
		}
		return $pending;
	}	
	
	/**
	 * Checks if a person with similar details has booked for this before
	 * @param $person_id
	 * @return EM_Booking
	 */
	function find_previous_booking($EM_Booking){
		//First see if we have a similar person on record that's making this booking
		$EM_Booking->person->load_similar();
		//If person exists on record, see if they've booked this event before, if so return the booking.
		if( is_numeric($EM_Booking->person->id) && $EM_Booking->person->id > 0 ){
			$EM_Booking->person_id = $EM_Booking->person->id;
			foreach ($this->bookings as $booking){
				if( $booking->person_id == $EM_Booking->person->id ){
					return $booking;
				}
			}
		}
		return false;
	}
	
	/**
	 * Will approve all supplied booking ids, which must be in the form of a numeric array or a single number.
	 * @param array|int $booking_ids
	 * @return boolean
	 */
	function approve( $booking_ids ){
		$this->set_status(1, $booking_ids);
		return false;
	}
	
	/**
	 * Will reject all supplied booking ids, which must be in the form of a numeric array or a single number.
	 * @param array|int $booking_ids
	 * @return boolean
	 */
	function reject( $booking_ids ){
		return $this->set_status(2, $booking_ids);
	}
	
	/**
	 * Will unapprove all supplied booking ids, which must be in the form of a numeric array or a single number.
	 * @param array|int $booking_ids
	 * @return boolean
	 */
	function unapprove( $booking_ids ){
		return $this->set_status(0, $booking_ids);
	}
	
	/**
	 * @param int $status
	 * @param array|int $booking_ids
	 * @return bool
	 */
	function set_status($status, $booking_ids){
		//FIXME there is a vulnerability where any user can approve/reject bookings if they know the ID
		if( EM_Object::array_is_numeric($booking_ids) ){
			//Get all the bookings
			$results = array();
			$mails = array();
			foreach( $booking_ids as $booking_id ){
				$EM_Booking = new EM_Booking($booking_id);
				$results[] = $EM_Booking->set_status($status);
			}
			if( !in_array('false',$results) ){
				$this->feedback_message = __('Bookings %s. Mails Sent.', 'dbem');
				return true;
			}else{
				//TODO Better error handling needed if some bookings fail approval/failure
				$this->feedback_message = __('An error occurred.', 'dbem');
				return false;
			}
		}elseif( is_numeric($booking_ids) || is_object($booking_ids) ){
			$EM_Booking = ( is_object($booking_ids) && get_class($booking_ids) == 'EM_Booking') ? $booking_ids : new EM_Booking($booking_ids);
			$result = $EM_Booking->set_status($status);
			$this->feedback_message = $EM_Booking->feedback_message;
			return $result;
		}
		return false;	
	}
	
	/**
	 * Get all pending bookings for this event  
	 */
	function get_pending(){
		$pending = array();
		foreach($this->bookings as $booking){
			if($booking->status == 0){
				$pending[] = $booking;
			}
		}
		return $pending;
	}
	
	/**
	 * Gets the pending number of bookings as a raw associative array.
	 * @return array 
	 */
	function get_pending_raw(){
		global $wpdb,$current_user;
		$bookings_table = $wpdb->prefix . EM_BOOKINGS_TABLE;
		$events_table = $wpdb->prefix . EM_EVENTS_TABLE;
		$people_table = $wpdb->prefix . EM_PEOPLE_TABLE;
		if( get_option('dbem_events_disable_ownership') && !current_user_can('activate_plugins') ){
			$sql = "SELECT * FROM $bookings_table b LEFT JOIN $events_table e ON e.event_id=b.event_id LEFT JOIN $people_table p ON p.person_id=b.person_id WHERE booking_status = 0 AND event_author=".$current_user->ID;
		} else {
			$sql = "SELECT * FROM $bookings_table b LEFT JOIN $events_table e ON e.event_id=b.event_id LEFT JOIN $people_table p ON p.person_id=b.person_id WHERE booking_status = 0";
		}
		$bookings_array = $wpdb->get_results($sql, ARRAY_A);
		return $bookings_array;
	}
}
?>