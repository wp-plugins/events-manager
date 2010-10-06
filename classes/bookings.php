<?php
/**
 * Deals with the booking info for an event
 * @author marcus
 *
 */
class EM_Bookings extends EM_Object{
	//TODO Bookings needs redoing
	
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
	function EM_Bookings( $event ){
		//TODO maybe not load bookings here (for speed), and have an init() function called by all functions?
		if( is_object($event) && get_class($event) == "EM_Event" ){ //Creates a blank bookings object if needed
			global $wpdb;
			$this->event_id = $event->id;
			$this->seats = $event->seats;
			$sql = "SELECT * FROM ". $wpdb->prefix . BOOKINGS_TBNAME ." b, ". $wpdb->prefix . PEOPLE_TBNAME ." p WHERE event_id ='{$this->event_id}' AND p.person_id=b.person_id";
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
		global $wpdb; 
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
					$email = $this->email($EM_Booking);
				}
			} else {
				//New booking, so let's save the booking
				$result = $EM_Booking->save();
				if($result){
					$email = $this->email($EM_Booking);
				}
			}
			if($result){
				//Success
				$this->feedback_message = __('Booking successful.', 'dbem');
				if(!$email){
					$this->feedback_message .= ' '.__('However, we were not able to send you an email.', 'dbem');
					if( current_user_can('activate_plugins') ){
						$this->feedback_message .= '<br/><strong>Errors:</strong> (only admins see this)<br/><ul><li>'. implode('</li><li>', $EM_Mailer->errors).'</li></ul>';
					}
				}
				return true;
			}else{
				//Failure
				$this->errors[] = "<strong>".__('Booking could not be created').":</strong><br />". implode('<br />', $EM_Booking->errors);
			}
		} else {
			 $this->errors[] = __('Booking cannot be made, not enough seats available!', 'dbem');
			 return false;
		} 
	}
	
	/**
	 * Delete all numbers
	 * @return boolean
	 */
	function delete(){
		global $wpdb;
		$result = $wpdb->query("DELETE FROM ".$wpdb->prefix.BOOKINGS_TBNAME." WHERE event_id='{$this->event_id}'");
		return ($result);
	}

	/**
	 * Returns number of available seats for this event
	 * @return int
	 */
	function get_available_seats(){
		$booked_seats = 0;
		foreach ( $this->bookings as $booking ){
			$booked_seats += $booking->seats;
		}
		return $this->seats - $booked_seats;
	}

	/**
	 * Returns number of booked seats for this event
	 * @return int
	 */
	function get_booked_seats(){
		$booked_seats = 0;
		foreach ( $this->bookings as $booking ){
			$booked_seats += $booking->seats;
		}
		return $booked_seats;
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
	 * @param $EM_Booking
	 * @return boolean
	 */
	function email($EM_Booking){
		global $EM_Event, $EM_Mailer;
		
		$contact_id = ( $EM_Event->contactperson_id != "") ? $EM_Event->contactperson_id : get_option('dbem_default_contact_person');
		 
		$contact_body = $EM_Event->output(get_option('dbem_contactperson_email_body'));
		$booker_body = $EM_Event->output(get_option('dbem_respondent_email_body'));
		
		// email specific placeholders
		// TODO pre 3.1 this is probably not needed anymore...
		$placeholders = array(
			'#_CONTACTPERSON'=> $EM_Event->contact->display_name,
			'#_RESPNAME' =>  $EM_Booking->person->name,
			'#_RESPEMAIL' => $EM_Booking->person->email,
			'#_RESPPHONE' => $EM_Booking->person->phone,
			'#_SPACES' => $EM_Booking->seats,
			'#_COMMENT' => $EM_Booking->comment,
			'#_RESERVEDSPACES' => $this->get_available_seats(),
			'#_AVAILABLESPACES' => $this->get_booked_seats()
		);
		 
		foreach($placeholders as $key => $value) {
			$contact_body= str_replace($key, $value, $contact_body);  
			$booker_body= str_replace($key, $value, $booker_body);
		}
		
		//TODO offer subject changes
		if( !$EM_Mailer->send(__('Reservation confirmed','dbem'),$booker_body, $EM_Booking->person->email) ){
			return false;
		}
		if( !$EM_Mailer->send(__("New booking",'dbem'), $contact_body, $EM_Event->contact->user_email) && current_user_can('activate_plugins')){
			$EM_Mailer->errors[] = 'Confirmation email could not be sent to contact person. Registrant should have gotten their email (only admin see this warning).';
			return false;
		}
		
		//TODO need error checking for booking mail send
		return true;
	}
	
}
?>