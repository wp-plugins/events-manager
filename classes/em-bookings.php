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
			if( $booking->status != 3 && (get_option('dbem_bookings_approval') == 0 || $booking->status == 1) ){
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
		$confirmed = array();
		foreach ( $this->bookings as $booking ){
			if( $booking->status == 1 || (get_option('dbem_bookings_approval') == 0 && $booking->status != 3) ){
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
	 * Get cancelled bookings. 
	 * @return array EM_Booking
	 */
	function get_cancelled_bookings(){
		$pending = array();
		foreach ( $this->bookings as $booking ){
			if($booking->status == 3){
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
	function get( $args = array() ){
		global $wpdb,$current_user;
		$bookings_table = $wpdb->prefix . EM_BOOKINGS_TABLE;
		$events_table = $wpdb->prefix . EM_EVENTS_TABLE;
		$people_table = $wpdb->prefix . EM_PEOPLE_TABLE;
		$locations_table = $wpdb->prefix . EM_LOCATIONS_TABLE;
		
		//Quick version, we can accept an array of IDs, which is easy to retrieve
		if( self::array_is_numeric($args) ){ //Array of numbers, assume they are event IDs to retreive
			//We can just get all the events here and return them
			$sql = "
				SELECT * FROM $bookings_table b 
				LEFT JOIN $events_table e ON e.event_id=b.event_id 
				LEFT JOIN $people_table p ON p.person_id=b.person_id 
				WHERE booking_id".implode(" OR booking_id=", $args);
			$results = $wpdb->get_results(apply_filters('em_bookings_get_sql',$sql),ARRAY_A);
			$bookings = array();
			foreach($results as $result){
				$bookings[$result['event_id']] = new EM_Event($result);
			}
			return $bookings; //We return all the events matched as an EM_Event array. 
		}
		
		//We assume it's either an empty array or array of search arguments to merge with defaults			
		$args = self::get_default_search($args);
		$limit = ( $args['limit'] && is_numeric($args['limit'])) ? "LIMIT {$args['limit']}" : '';
		$offset = ( $limit != "" && is_numeric($args['offset']) ) ? "OFFSET {$args['offset']}" : '';
		
		//Get the default conditions
		$conditions = self::build_sql_conditions($args);
		//Put it all together
		$where = ( count($conditions) > 0 ) ? " WHERE " . implode ( " AND ", $conditions ):'';
		
		//Get ordering instructions
		$EM_Booking = new EM_Booking();
		$accepted_fields = $EM_Booking->get_fields(true);
		$orderby = self::build_sql_orderby($args, $accepted_fields);
		//Now, build orderby sql
		$orderby_sql = ( count($orderby) > 0 ) ? 'ORDER BY '. implode(', ', $orderby) : '';
		
		//Create the SQL statement and execute
		$sql = "
			SELECT * FROM $bookings_table 
			LEFT JOIN $events_table ON {$events_table}.event_id={$bookings_table}.event_id 
			LEFT JOIN $people_table ON {$people_table}.person_id={$bookings_table}.person_id 
			LEFT JOIN $locations_table ON {$locations_table}.location_id={$events_table}.location_id
			$where
			$orderby_sql
			$limit $offset
		";
	
		$results = $wpdb->get_results( apply_filters('em_events_get_sql',$sql, $args), ARRAY_A);

		//If we want results directly in an array, why not have a shortcut here?
		if( $args['array'] == true ){
			return $results;
		}
		
		//Make returned results EM_Booking objects
		$results = (is_array($results)) ? $results:array();
		$bookings = array();
		foreach ( $results as $booking ){
			$bookings[] = new EM_Booking($booking);
		}
		
		return apply_filters('em_bookings_get', $bookings);
	}
	

	//List of patients in the patient database, that a user can choose and go on to edit any previous treatment data, or add a new admission.
	function export_csv() {
		global $EM_Event;
		if($EM_Event->id != $this->event_id ){
			$event = new EM_Event($this->event_id);
			$event_name = $event->name;
		}else{
			$event_name = $EM_Event->name;
		}
		// The name of the file on the user's pc
		$file_name = sanitize_title($event_name). "-bookings.csv";
		
		header("Content-Type: application/octet-stream");
		header("Content-Disposition: Attachment; filename=$file_name");
		
		//Headers
		$labels = array(
			'ID',
			'Name',
			'Email',
			'Phone',
			'Date',
			'Status',
			'Spaces',
			'Comment'
		);
		$file = sprintf(__('Booking details for "%s" as of %s','dbem'),$event_name, date_i18n('D d M Y h:i', current_time('timestamp'))) .  "\n";
		$file = '"'. implode('","', $labels). '"' .  "\n";
		
		//Rows
		foreach( $this->bookings as $EM_Booking ) {
			$row = array(
				$EM_Booking->id,
				$EM_Booking->person->name,
				$EM_Booking->person->email,
				$EM_Booking->person->phone,
				date('Y-m-d h:i', $EM_Booking->timestamp),
				$EM_Booking->seats,
				$EM_Booking->get_status(),
				$EM_Booking->comment
			);
			//Display all values
			foreach($row as $value){
				$value = str_replace('"', '""', $value);
				$value = str_replace("=", "", $value);
				$file .= '"' .  preg_replace("/\n\r|\r\n|\n|\r/", ".     ", $value) . '",';
			}
			$file .= "\n";
		}
		
		// $file holds the data
		echo $file;
		$file = "";
	}
	
	/* Overrides EM_Object method to apply a filter to result
	 * @see wp-content/plugins/events-manager/classes/EM_Object#build_sql_conditions()
	 */
	function build_sql_conditions( $args = array() ){
		$conditions = apply_filters( 'em_bookings_build_sql_conditions', parent::build_sql_conditions($args), $args );
		if( is_numeric($args['status']) ){
			$conditions['status'] = 'booking_status='.$args['status'];
		}
		return apply_filters('em_bookings_build_sql_conditions', $conditions, $args);
	}
	
	/* Overrides EM_Object method to apply a filter to result
	 * @see wp-content/plugins/events-manager/classes/EM_Object#build_sql_orderby()
	 */
	function build_sql_orderby( $args, $accepted_fields, $default_order = 'ASC' ){
		return apply_filters( 'em_bookings_build_sql_orderby', parent::build_sql_orderby($args, $accepted_fields, get_option('dbem_events_default_order')), $args, $accepted_fields, $default_order );
	}
	
	/* 
	 * Adds custom Events search defaults
	 * @param array $array
	 * @return array
	 * @uses EM_Object#get_default_search()
	 */
	function get_default_search( $array = array() ){
		$defaults = array(
			'status' => false,
			'person' => true //to add later, search by person's bookings...
		);
		return apply_filters('em_bookings_get_default_search', parent::get_default_search($defaults,$array), $array, $defaults);
	}
}
?>