<?php
class EM_Booking extends EM_Object{
	//DB Fields
	var $id;
	var $event_id;
	var $person_id;
	var $seats;
	var $comment;
	var $approved = 0;
	//Other Vars
	var $fields = array(
		'booking_id' => array('name'=>'id','type'=>'%d'),
		'event_id' => array('name'=>'event_id','type'=>'%d'),
		'person_id' => array('name'=>'person_id','type'=>'%d'),
		'booking_seats' => array('name'=>'seats','type'=>'%d'),
		'booking_comment' => array('name'=>'comment','type'=>'%s'),
		'booking_approved' => array('name'=>'approved','type'=>'%d')
	);
	var $person;
	var $required_fields = array('booking_id', 'event_id', 'person_id', 'booking_seats');
	var $feedback_message = "";
	var $errors = array();
	
	/**
	 * Creates booking object and retreives booking data (default is a blank booking object). Accepts either array of booking data (from db) or a booking id.
	 * @param mixed $booking_data
	 * @return null
	 */
	function EM_Booking( $booking_data = false ){
		if( $booking_data !== false ){
			//Load booking data
			$booking = array();
			if( is_array($booking_data) ){
				$booking = $booking_data;
				//Also create a person out of this...
			  	$this->person = new EM_Person($booking_data);
			}elseif( $booking_data > 0 ){
				//Retreiving from the database		
				global $wpdb;			
				$sql = "SELECT * FROM ". $wpdb->prefix . EM_BOOKINGS_TABLE ." WHERE booking_id ='$booking_data'";   
			  	$booking = $wpdb->get_row($sql, ARRAY_A);
			  	//Get the person for this booking
			  	$this->person = new EM_Person($booking['person_id']);
			}
			//Save into the object
			$this->to_object($booking);
		}
	}
	
	/**
	 * Saves the booking into the database, whether a new or existing booking
	 * @return boolean
	 */
	function save(){
		global $wpdb;
		$table = $wpdb->prefix.EM_BOOKINGS_TABLE;
		//First the person
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
		}else{
			$result = $wpdb->insert($table, $data, $this->get_types($data));
		    $this->id = $wpdb->insert_id;   
		}
		if( $result === false ){
			$this->errors[] = __('There was a problem saving the booking.', 'dbem');
		}
		
		//Give feedback on result
		if( count($this->errors) == 0 ){
			//Success
			$this->feedback_message = __('Your booking has been recorded','dbem');
			return true;
		}else{
			return false;
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
	 * I wonder what this does....
	 * @return boolean
	 */
	function delete(){
		global $wpdb;
		$sql = $wpdb->prepare("DELETE FROM ". $wpdb->prefix.EM_BOOKINGS_TABLE . " WHERE booking_id=%d", $this->id);
		return ( $wpdb->query( $sql ) !== false );
	}
	
	/**
	 * Approve a booking.
	 */
	function approve(){
		$this->approved = 1;
		return $this->save();
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