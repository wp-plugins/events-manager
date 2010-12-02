<?php
/**
 * Object that holds event recurrence info
 * The EM_Event objects will handle adding / editing / deleting recurrences, this just holds the recurring info 
 * and provides extra functions to obtain related events.
 * @author marcus
 */
class EM_Recurrence extends EM_Object{
	//TODO We NEED to add an author and rsvp to this recurrence.....
	var $fields = array(
		'recurrence_id' => 'id',
		'recurrence_author' => 'author',
		'recurrence_name' => 'name',
		'recurrence_start_time' => 'start_time',
		'recurrence_end_time' => 'end_time',
		'recurrence_start_date' => 'start_date',
		'recurrence_end_date' => 'end_date',
		'recurrence_notes' => 'notes',
		'recurrence_rsvp' => 'rsvp',
		'recurrence_seats' => 'seats',
		'recurrence_contactperson_id' => 'contactperson_id',
		'location_id' => 'location_id',
		'recurrence_id' => 'recurrence_id',
		'recurrence_category_id' => 'category_id',
		'recurrence_attributes' => 'attributes',
		'recurrence_interval' => 'interval',
		'recurrence_freq' => 'freq',
		'recurrence_byday' => 'byday',
		'recurrence_byweekno' => 'byweekno',
	);
	/**
	 * Array of EM_Event objects
	 * @var array
	 */
	var $events = array();
	
	/**
	 * Initialize object. You can provide event data in an associative array (using database table field names), an id number, or false (default) to create empty event.
	 * If you provide an event array or object, it will convert it into a recurrence (useful if you want to change event data into recurrence).
	 * @param mixed $event_data
	 * @return null
	 */	
	function EM_Recurrence($event_data = false) {
		global $wpdb;
		if( $event_data !== false ){
			$recurrence = array();
			if( is_array($event_data) && isset($event_data['recurrence_name']) ){
				//Directly inserting array of recurrence data
				$recurrence = $event_data;
			}elseif( is_numeric($event_data) || isset($event_data['recurrence_id']) ){
				//$event_data is recurrence_id -  Retreiving from the database
				$recurrence_id = (is_array($event_data)) ? $event_data['recurrence_id']:$event_data;
				$sql = "SELECT * FROM ". $wpdb->prefix . EM_RECURRENCE_TABLE ." WHERE recurrence_id = $recurrence_id";
				$result = $wpdb->get_row( $sql, ARRAY_A );
				if($result){
					$this->location = new EM_Location ( $recurrence ['location_id'] );
					$recurrence = $result;
				}
			}
			$this->to_object($recurrence);
		}
	}

	/**
	 * Removes recurrence record.
	 * @param $recurrence_id
	 * @return null
	 */
	function delete() {
		global $wpdb;
		$sql = "DELETE FROM ".$wpdb->prefix.EM_RECURRENCE_TABLE." WHERE recurrence_id = '{$this->id}';";
		$wpdb->query($sql);
	}

	/**
	 * Save an array into this class
	 * @param $array
	 * @return null
	 */
	function to_object( $array = array() ){
		//Save event core data
		parent::to_object($array);
		//Save location info
		$this->location = new EM_Location($array['location_id']);
		//Save contact person info
	}
	
}
?>