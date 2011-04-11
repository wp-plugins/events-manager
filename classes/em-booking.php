<?php
class EM_Booking extends EM_Object{
	//DB Fields
	var $id;
	var $event_id;
	var $person_id;
	var $seats;
	var $comment;
	var $status = 0;
	var $notes = array();
	var $fields = array(
		'booking_id' => array('name'=>'id','type'=>'%d'),
		'event_id' => array('name'=>'event_id','type'=>'%d'),
		'person_id' => array('name'=>'person_id','type'=>'%d'),
		'booking_seats' => array('name'=>'seats','type'=>'%d'),
		'booking_comment' => array('name'=>'comment','type'=>'%s'),
		'booking_status' => array('name'=>'status','type'=>'%d')
	);
	//Other Vars
	var $timestamp;
	var $person;
	var $required_fields = array('booking_id', 'event_id', 'person_id', 'booking_seats');
	var $feedback_message = "";
	var $errors = array();
	/**
	 * If saved in this instance, you can see what previous approval status was.
	 * @var int
	 */
	var $previous_status = false;
	/**
	 * The booking approval status number corresponds to a state in this array.
	 * @var unknown_type
	 */
	var $status_array = array();
	
	/**
	 * Creates booking object and retreives booking data (default is a blank booking object). Accepts either array of booking data (from db) or a booking id.
	 * @param mixed $booking_data
	 * @return null
	 */
	function EM_Booking( $booking_data = false ){
		if( !get_option('dbem_bookings_approval') ){
			$this->status = 1;
		}
		if( $booking_data !== false ){
			//Load booking data
			$booking = array();
			if( is_array($booking_data) ){
				$booking = $booking_data;
				//Also create a person out of this...
			  	$this->person = new EM_Person($booking_data);
			  	if( !empty($booking['person_id']) && $booking['person_id'] != $this->person->id){
			  		$this->person = new EM_Person($booking['booking_id']);
			  	}
			}elseif( is_numeric($booking_data) ){
				//Retreiving from the database		
				global $wpdb;			
				$sql = "SELECT * FROM ". $wpdb->prefix . EM_BOOKINGS_TABLE ." WHERE booking_id ='$booking_data'";   
			  	$booking = $wpdb->get_row($sql, ARRAY_A);
			  	//Get the person for this booking
			  	$this->person = new EM_Person($booking['person_id']);
			  	//Booking notes
			  	$notes = $wpdb->get_results("SELECT * FROM ". $wpdb->prefix . EM_META_TABLE ." WHERE meta_key='booking-note' AND object_id ='$booking_data'", ARRAY_A);
			  	foreach($notes as $note){
			  		$this->notes[] = unserialize($note['meta_value']);
			  	}
			}
			//Save into the object
			$this->to_object($booking);
			if( !empty($booking['booking_date']) ){
				$this->timestamp = strtotime($booking['booking_date']);
			}
		}
		//Do it here so things appear in the po file.
		$this->status_array = array(
			0 => __('Pending','dbem'),
			1 => __('Approved','dbem'),
			2 => __('Rejected','dbem'),
			3 => __('Cacncelled','dbem')
		);
	}
	
	/**
	 * Saves the booking into the database, whether a new or existing booking
	 * @return boolean
	 */
	function save(){
		global $wpdb;
		$table = $wpdb->prefix.EM_BOOKINGS_TABLE;
		do_action('em_booking_save_pre',$this);
		//First the person
		if($this->validate()){
			//Does this person exist?
			$person_result = $this->person->save();
			if( $person_result === false ){
				$this->errors = array_merge($this->errors, $this->person->errors);
				return false;
			}
			$this->person_id = $this->person->id;
			
			//Now we save the booking
			$data = $this->to_array();
			if($this->id != ''){
				$where = array( 'booking_id' => $this->id );  
				$result = $wpdb->update($table, $data, $where, $this->get_types($data));
				$this->feedback_message = __('Changes saved');
			}else{
				$result = $wpdb->insert($table, $data, $this->get_types($data));
			    $this->id = $wpdb->insert_id;  
				$this->feedback_message = __('Your booking has been recorded','dbem'); 
			}
			if( $result === false ){
				$this->feedback_message = __('There was a problem saving the booking.', 'dbem');
				$this->errors[] = __('There was a problem saving the booking.', 'dbem');
			}
			return apply_filters('em_booking_save', ( count($this->errors) == 0 ), $this);
		}else{
			$this->feedback_message = __('There was a problem saving the booking.', 'dbem');
			$this->errors[] = __('There was a problem saving the booking.', 'dbem');
			return apply_filters('em_booking_save', false, $this);
		}
		return true;
	}
	
	/**
	 * Load an record into this object by passing an associative array of table criterie to search for. 
	 * Returns boolean depending on whether a record is found or not. 
	 * @param $search
	 * @return boolean
	 */
	function get($search) {
		global $wpdb;
		$conds = array(); 
		foreach($search as $key => $value) {
			if( array_key_exists($key, $this->fields) ){
				$value = $wpdb->escape($value);
				$conds[] = "`$key`='$value'";
			} 
		}
		$sql = "SELECT * FROM ". $wpdb->EM_BOOKINGS_TABLE ." WHERE " . implode(' AND ', $conds) ;
		$result = $wpdb->get_row($sql, ARRAY_A);
		if($result){
			$this->to_object($result);
			return true;	
		}else{
			return false;
		}
	}
	
	/**
	 * Get posted data and save it into the object (not db)
	 * @return boolean
	 */
	function get_post(){
		//Currently, only editing allowed, so here we are.
		$this->comment = (!empty($_REQUEST['booking_comment'])) ? $_REQUEST['booking_comment']:'';
		$this->seats = (!empty($_REQUEST['booking_seats'])) ? $_REQUEST['booking_seats']:'';
		return apply_filters('em_booking_get_post',$this->validate(),$this);
	}
	
	function validate(){
		return ( 
			(empty($this->event_id) || is_numeric($this->event_id)) && 
			(empty($this->person_id) || is_numeric($this->person_id)) &&
			is_numeric($this->seats)
		);
	}
	
	/**
	 * Smart event locator, saves a database read if possible. 
	 */
	function get_event(){
		global $EM_Event;
		if( !empty($this->event) && is_object($this->event) && get_class($this->event)=='EM_Event' && $this->event->id == $this->event_id ){
			return $this->event;
		}elseif( is_object($EM_Event) && $EM_Event->id == $this->event_id ){
			$this->event = $EM_Event;
		}else{
			$this->event = new EM_Event($this->event_id);
		}
		return $this->event;
	}

	/**
	 * Returns a string representation of the booking's status
	 * @return string
	 */
	function get_status(){
		return $this->status_array[$this->status];
	}
	/**
	 * I wonder what this does....
	 * @return boolean
	 */
	function delete(){
		global $wpdb;
		$sql = $wpdb->prepare("DELETE FROM ". $wpdb->prefix.EM_BOOKINGS_TABLE . " WHERE booking_id=%d", $this->id);
		$result = $wpdb->query( $sql );
		return ( $result !== false );
	}
	
	function cancel(){
		return $this->set_status(3);
	}
	
	/**
	 * Approve a booking.
	 * @return bool
	 */
	function approve(){
		return $this->set_status(1);
	}	
	/**
	 * Reject a booking and save
	 * @return bool
	 */
	function reject(){
		return $this->set_status(2);
	}	
	/**
	 * Unpprove a booking.
	 * @return bool
	 */
	function unapprove(){
		return $this->set_status(0);
	}
	
	/**
	 * Change the status of the booking. This will save to the Database too. 
	 * @param unknown_type $status
	 * @return string|string|string
	 */
	function set_status($status){		
		$this->previous_status = $this->status;
		$this->status = $status;
		$result = $this->save();
		$action_string = strtolower($this->status_array[$status]); 
		if($result){
			$this->feedback_message = sprintf(__('Booking %s.','dbem'), $action_string);
			if( $this->email() ){
				$this->feedback_message .= " ".__('Mail Sent.','dbem');
			}elseif( $this->previous_status == 0 ){
				//extra errors may be logged by email() in EM_Object
				$this->feedback_message .= ' <span style="color:red">'.__('ERROR : Mail Not Sent.','dbem').'</span>';
				return false;
			}
			return true;
		}else{
			//errors should be logged by save()
			$this->feedback_message = sprintf(__('Booking could not be %s.','dbem'), $action_string);
			return false;
		}
	}
	
	/**
	 * Add a booking note to this booking. returns wpdb result or false if use can't manage this event.
	 * @param string $note
	 * @return mixed
	 */
	function add_note( $note_text ){
		global $wpdb;
		if( $this->can_manage() ){
			$note = array('author'=>get_current_user_id(),'note'=>$note_text,'timestamp'=>current_time('timestamp'));
			$this->notes[] = $note;
			$this->feedback_message = __('Booking note successfully added.','dbem');
			return $wpdb->insert($wpdb->prefix.EM_META_TABLE, array('object_id'=>$this->id, 'meta_key'=>'booking-note', 'meta_value'=> serialize($note)),array('%d','%s','%s'));
		}
		return false;
	}
	
	/**
	 * @param EM_Booking $EM_Booking
	 * @param EM_Event $event
	 * @return boolean
	 */
	function email(){
		global $EM_Mailer;
		$EM_Event = $this->get_event(); //We NEED event details here.
		//Make sure event matches booking, and that booking used to be approved.
		if( $this->previous_status == 0 || $this->status == 3 ){
			$contact_id = ( $EM_Event->contactperson_id != "") ? $EM_Event->contactperson_id : get_option('dbem_default_contact_person');
	
			$contact_subject = get_option('dbem_bookings_contact_email_subject');
			$contact_body = get_option('dbem_bookings_contact_email_body');
			
			if( (get_option('dbem_bookings_approval') == 0 && $this->previous_status === false) || $this->status == 1 ){
				$booker_subject = get_option('dbem_bookings_email_confirmed_subject');
				$booker_body = get_option('dbem_bookings_email_confirmed_body');
			}elseif( $this->status == 0 ){
				$booker_subject = get_option('dbem_bookings_email_pending_subject');
				$booker_body = get_option('dbem_bookings_email_pending_body');
			}elseif( $this->status == 2 ){
				$booker_subject = get_option('dbem_bookings_email_rejected_subject');
				$booker_body = get_option('dbem_bookings_email_rejected_body');
			}elseif( $this->status == 3 ){
				$booker_subject = get_option('dbem_bookings_email_cancelled_subject');
				$booker_body = get_option('dbem_bookings_email_cancelled_body');
				$contact_subject = get_option('dbem_contactperson_email_cancelled_subject');
				$contact_body = get_option('dbem_contactperson_email_cancelled_body');
			}
			
			// email specific placeholders
			$placeholders = array(
				'#_RESPNAME' =>  '#_BOOKINGNAME',//Depreciated
				'#_RESPEMAIL' => '#_BOOKINGEMAIL',//Depreciated
				'#_RESPPHONE' => '#_BOOKINGPHONE',//Depreciated
				'#_COMMENT' => '#_BOOKINGCOMMENT',//Depreciated
				'#_RESERVEDSPACES' => '#_BOOKEDSPACES',//Depreciated
				'#_BOOKINGNAME' =>  $this->person->name,
				'#_BOOKINGEMAIL' => $this->person->email,
				'#_BOOKINGPHONE' => $this->person->phone,
				'#_BOOKINGSPACES' => $this->seats,
				'#_BOOKINGCOMMENT' => $this->comment,
			);		 
			foreach($placeholders as $key => $value) {
				$contact_subject = str_replace($key, $value, $contact_subject);
				$contact_body = str_replace($key, $value, $contact_body); 
				$booker_subject = str_replace($key, $value, $booker_subject); 
				$booker_body = str_replace($key, $value, $booker_body);
			}
			
			$booker_subject = $EM_Event->output($booker_subject, 'email'); 
			$booker_body = $EM_Event->output($booker_body, 'email');
			
			//Send to the person booking
			if( !$this->email_send( $booker_subject,$booker_body, $this->person->email) ){
				return false;
			}
			
			//Send admin emails
			if( (get_option('dbem_bookings_approval') == 0 || $this->status == 0) && (get_option('dbem_bookings_contact_email') == 1 || get_option('dbem_bookings_notify_admin') != '') ){
				//Only gets sent if this is a pending booking, unless approvals are disabled.
				$contact_subject = $EM_Event->output($contact_subject, 'email');
				$contact_body = $EM_Event->output($contact_body, 'email');
				
				if( get_option('dbem_bookings_contact_email') == 1 ){
					if( !$this->email_send( $contact_subject, $contact_body, $EM_Event->contact->user_email) && current_user_can('activate_plugins')){
						$this->errors[] = __('Confirmation email could not be sent to contact person. Registrant should have gotten their email (only admin see this warning).','dbem');
						return false;
					}
				}
		
				if( get_option('dbem_bookings_notify_admin') != '' && preg_match('/^[_\.0-9a-z-]+@([0-9a-z][0-9a-z-]+\.)+[a-z]{2,3}$/', get_option('dbem_bookings_notify_admin')) ){
					if( !$this->email_send( $contact_subject, $contact_body, get_option('dbem_bookings_notify_admin')) ){
						$this->errors[] = __('Confirmation email could not be sent to admin. Registrant should have gotten their email (only admin see this warning).','dbem');
						return false;
					}
				}
			}
			return true;
		}
		return false;
		//TODO need error checking for booking mail send
	}	
	
	/**
	 * Can the user manage this event? 
	 */
	function can_manage(){
		return ( get_option('dbem_permissions_events') || $this->get_event()->author == get_current_user_id() || em_verify_admin() );
	}
	
	/**
	 * Returns this object in the form of an array
	 * @return array
	 */
	function to_array($person = false){
		$booking = array();
		//Core Data
		$booking = parent::to_array();
		//Person Data
		if($person && is_object($this->person)){
			$person = $this->person->to_array();
			$booking = array_merge($booking, $person);
		}
		return $booking;
	}
}
?>