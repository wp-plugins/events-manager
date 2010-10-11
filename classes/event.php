<?php
/**
 * Event Object. This holds all the info pertaining to an event, including location and recurrence info.
 * An event object can be one of three "types" a recurring event, recurrence of a recurring event, or a single event.
 * The single event might be part of a set of recurring events, but if loaded by specific event id then any operations and saves are 
 * specifically done on this event. However, if you edit the recurring group, any changes made to single events are overwritten.
 * 
 * @author marcus
 */
//TODO Can add more recurring functionality such as "also update all future recurring events" or "edit all events" like google calendar does.
//TODO Integrate recurrences into events table
//FIXME If you create a super long recurrence timespan, there could be thousands of events... need an upper limit here.
class EM_Event extends EM_Object{
	/**
	 * Assoc array where keys are names of database fields and values are array corresponding object property name, regex, data types, etc. 
	 * for use when importing/exporting event data between database and object
	 * @var array
	 */
	var $fields = array(
		'event_id' => array( 'name'=>'id', 'type'=>'%d' ),
		'event_author' => array( 'name'=>'author', 'type'=>'%d' ),
		'event_name' => array( 'name'=>'name', 'type'=>'%s' ),
		'event_start_time' => array( 'name'=>'start_time', 'type'=>'%s' ),
		'event_end_time' => array( 'name'=>'end_time', 'type'=>'%s' ),
		'event_start_date' => array( 'name'=>'start_date', 'type'=>'%s' ),
		'event_end_date' => array( 'name'=>'end_date', 'type'=>'%s' ),
		'event_notes' => array( 'name'=>'notes', 'type'=>'%s' ),
		'event_rsvp' => array( 'name'=>'rsvp', 'type'=>'%d' ),
		'event_seats' => array( 'name'=>'seats', 'type'=>'%d' ),
		'event_contactperson_id' => array( 'name'=>'contactperson_id', 'type'=>'%d' ),
		'location_id' => array( 'name'=>'location_id', 'type'=>'%d' ),
		'recurrence_id' => array( 'name'=>'recurrence_id', 'type'=>'%d' ),
		'event_category_id' => array( 'name'=>'category_id', 'type'=>'%d' ),
		'event_attributes' => array( 'name'=>'attributes', 'type'=>'%s' ),
		'recurrence' => array( 'name'=>'recurrence', 'type'=>'%d' ),
		'recurrence_interval' => array( 'name'=>'interval', 'type'=>'%d' ),
		'recurrence_freq' => array( 'name'=>'freq', 'type'=>'%s' ),
		'recurrence_byday' => array( 'name'=>'byday', 'type'=>'%s' ),
		'recurrence_byweekno' => array( 'name'=>'byweekno', 'type'=>'%d' )
	);
	
	/**
	 * @var EM_Location
	 */
	var $location;
	/**
	 * @var EM_Bookings
	 */
	var $bookings;
	/**
	 * The contact person for this event
	 * @var WP_User
	 */
	var $contact;
	/**
	 * If there are any errors, they will be added here.
	 * @var array
	 */
	var $errors = array();	
	/**
	 * If something was successful, a feedback message might be supplied here.
	 * @var string
	 */
	var $feedback_message;
	/**
	 * Array of dbem_event field names required to create an event 
	 * @var array
	 */
	var $required_fields = array('event_name', 'event_start_date');
	
	/**
	 * Initialize an event. You can provide event data in an associative array (using database table field names), an id number, or false (default) to create empty event.
	 * @param mixed $event_data
	 * @param boolean $recurrent
	 * @return null
	 */
	function EM_Event($event_data = false, $recurrent = false) {
		global $wpdb, $EM_Recurrences;
		//TODO Change the way we deal with time, maybe revert to timestamps for manipulation, and worry about output in html and db writes?
		if( $event_data !== false ){
			$event = array();
			if( is_array($event_data) ){
				//Accepts a raw array that'll just be imported directly into the object with no DB lookups (same for event and recurrence)
				$event = $event_data;
				//FIXME this could lead to potential blank locations, if not supplied in array... do we load or not?
				$this->location = new EM_Location( $event );
			}elseif( is_numeric($event_data) && $event_data > 0 ){
				//Retreiving from the database  
				$events_table = $wpdb->prefix . EVENTS_TBNAME;
				$locations_table = $wpdb->prefix . LOCATIONS_TBNAME;
				$sql = "
					SELECT * FROM $events_table
					LEFT JOIN $locations_table ON {$locations_table}.location_id={$events_table}.location_id 
					WHERE event_id = $event_data
				"; //We get event and location data here to avoid extra queries
				$event = $wpdb->get_row ( $sql, ARRAY_A );
				//Sort Location
				$this->location = new EM_Location ( $event );
			}
			//Sort out attributes
			$event ['event_attributes'] = @unserialize($event ['event_attributes']);
			$event ['event_attributes'] = (!is_array($event ['event_attributes'])) ?  array() : $event ['event_attributes'] ;
			$this->to_object($event, true);
			//Add Contact Person
			if($this->contactperson_id){
				if($this->contactperson_id > 0){
					$this->contact = get_userdata($this->contactperson_id);
				}
			}
			if( !is_object($this->contact) ){
				$this->contactperson_id = get_option('dbem_default_contact_person');
				$this->contact = get_userdata($this->contactperson_id);
			}
			if( is_object($this->contact) ){
	      		$this->contact->phone = get_user_meta($this->contact->ID, 'dbem_phone', true);
			}
			//Now, if this is a recurrence, get the recurring for caching to the $EM_Recurrences
			if( $this->is_recurrence() && !array_key_exists($this->recurrence_id, $EM_Recurrences) ){
				$EM_Recurrences[$this->recurrence_id] = new EM_Event($this->recurrence_id);
			}
		}
	}
	
	/**
	 * Retrieve event, location and recurring information via POST
	 * @return boolean
	 */
	function get_post(){
		//Build Event Array
		$post = $_POST;
		$this->name = stripslashes ( $_POST ["event_name"] );
		$this->start_date = $_POST ["event_start_date"];
		$this->end_date = ($_POST ['event_end_date'] == '') ? $this->start_date : $_POST ["event_end_date"]; 
		$this->rsvp = ( $_POST ['event_rsvp'] == 1 ) ? 1:0;
		$this->seats = ( is_numeric($_POST ['event_seats']) ) ? $_POST ['event_seats']:0;
		$this->notes = stripslashes ( $_POST ['content'] ); //WP TinyMCE field
		//Sort out time
		//TODO make time handling less painful
		$match = array();
		if( preg_match ( '/^([01]\d|2[0-3]):([0-5]\d)(AM|PM)?$/', $_POST['event_start_time'], $match ) ){
			$match[1] = ($match[3] == 'PM') ? 12+$match[1] : $match[1]; 
			$this->start_time = $match[1].":".$match[2].":00";
		}else{
			$this->start_time = "00:00:00";
		}
		if( preg_match ( '/^([01]\d|2[0-3]):([0-5]\d)(AM|PM)?$/', $_POST['event_end_time'], $match ) ){
			$match[1] = ($match[3] == 'PM') ? 12+$match[1] : $match[1]; 
			$this->end_time = $match[1].":".$match[2].":00";
		}else{
			$this->end_time = $this->start_time;
		}
		//Contact Person
		if ( is_numeric($_POST['event_contactperson_id']) ) {		
			//TODO contactperson choices needs limiting depending on role	
			$this->contactperson_id = $_POST ['event_contactperson_id'];
		}
		//category
		if( is_numeric($_POST ['event_category_id']) ){
			$this->category_id = $_POST ['event_category_id'];
		}	
		//Attributes
		$event_attributes = array();
		for($i=1 ; trim($_POST["mtm_{$i}_ref"])!='' ; $i++ ){
	 		if(trim($_POST["mtm_{$i}_name"]) != ''){
		 		$event_attributes[$_POST["mtm_{$i}_ref"]] = stripslashes($_POST["mtm_{$i}_name"]);
	 		}
	 	}
	 	$this->attributes = $event_attributes;
		//Recurrence data
		$this->recurrence_id = ( is_numeric($_POST ['recurrence_id']) ) ? $_POST ['recurrence_id'] : 0 ;
		if($_POST ['repeated_event']){
			$this->recurrence = 1;
			$this->freq = $_POST ['recurrence_freq'];
			$this->byday = ($this->freq == 'weekly') ? implode ( ",", $_POST ['recurrence_bydays'] ) : $_POST ['recurrence_bydays'];
			$this->interval = ($_POST ['recurrence_interval'] == "") ? 1 : $_POST ['recurrence_interval'];
			$this->byweekno = $_POST ['recurrence_byweekno'];
		}
		
		//Add location information, or just link to previous location, this is a requirement...
		if( isset($_POST['location-select-id']) && $_POST['location-select-id'] != "" ) {
			$this->location = new EM_Location($_POST['location-select-id']);
		} else {
			$this->location = new EM_Location($_POST); 
			$this->location->load_similar($_POST);                
		}
		return $this->validate();
	}
	
	/**
	 * Will save the current instance into the database, along with location information if a new one was created and return true if successful, false if not.
	 * Will automatically detect what type of event it is (recurrent, recurrence or normal) and whether it's a new or existing event. 
	 * @return boolean
	 */
	function save(){
		//FIXME Event doesn't save title when inserting first time
		global $wpdb, $current_user;
   		get_currentuserinfo();;
		$events_table = $wpdb->prefix.EVENTS_TBNAME;
		//First let's save the location, no location no event!
		if ( !$this->location->id && !$this->location->save() ){ //shouldn't try to save if location exists
			$this->errors[] = __ ( 'There was a problem saving the location so event was not saved.', 'dbem' );
	 		return false;
		}
		$this->location_id = $this->location->id;
		//TODO make contactperson_id NULL if not used
		$this->contactperson_id = ( $this->contactperson_id > 0 ) ? $this->contactperson_id:0;
		//Now save the event
		if ( !$this->id ) {
			// Insert New Event
			$this->author = $current_user->ID; //Record creator of event
			$event = $this->to_array(false, true);
			$event['event_attributes'] = serialize($this->attributes);
			$event['recurrence_id'] = ( is_numeric($this->recurrence_id) ) ? $this->recurrence_id : 0;
			$result = $wpdb->insert ( $events_table, $event, $this->get_types($event) );
			if($result !== false){
				$this->id = $wpdb->insert_id;
				//Deal with recurrences
				if ( $this->is_recurring() ) {
					//Recurrence master event saved, now Save Events & check errors
				 	if( !$this->save_events() ){
						$this->errors[] = 	__ ( 'Something went wrong with the recurrence update...', 'dbem' ).
											__ ( 'There was a problem saving the recurring events.', 'dbem' );
						$this->delete();
				 		return false;
				 	}
				 	//All good! Event Saved
					$this->feedback_message = __ ( 'New recurrent event inserted!', 'dbem' );
					return true;
				}
				//Successful individual save
				$this->feedback_message = __ ( 'New event successfully inserted!', 'dbem' );
				return true;
			}else{
				$this->errors[] = 	__ ( 'Could not save the event details due to a database error.', 'dbem' );
			}
		} else {
			// Update Event
			//TODO event privacy protection, only authors and authorized users can edit events
			//$this->author = $current_user->ID; //Record creator of event
			//FIXME Saving recurrence and disabling recurrence doesn't work
			$this->recurrence_id = 0; // If it's saved here, it becomes individual
			$event = $this->to_array();
			$event['event_attributes'] = serialize($event['event_attributes']);
			$result = $wpdb->update ( $events_table, $event, array('event_id' => $this->id), $this->get_types($event) );
			if($result !== false){ //Can't just do $result since if you don't make an actual record details change, it'll return 0 for no changes made
				//Deal with recurrences
				if ( $this->is_recurring() ) {
					if( !$this->save_events() ){
						$this->errors[] = 	__ ( 'Something went wrong with the recurrence update...', 'dbem' ).
											__ ( 'There was a problem saving the recurring events.', 'dbem' );
						return false;
					}
					$this->feedback_message = __ ( 'Recurrence updated!', 'dbem' );
					return true;			
				}
			}else{
				$this->errors[] = __('Could not save the event details due to a database error.', 'dbem');
				return false;
			}
			//Successful individual or recurrence save
			$this->feedback_message = "'{$this->name}' " . __ ( 'updated', 'dbem' ) . "!";
			if($this->rsvp == 0){
				$this->delete_bookings();
			}
			return true;
		}
	}
	
	/**
	 * Delete whole event, including recurrence and recurring data
	 * @param $recurrence_id
	 * @return null
	 */
	function delete(){
		global $wpdb;
		if( $this->is_recurring() ){
			//Delete the recurrences then this recurrence event
			$this->delete_events();
		}
		$result = $wpdb->query ( $wpdb->prepare("DELETE FROM ". $wpdb->prefix . EVENTS_TBNAME ." WHERE event_id=%d", $this->id) );
		if($result !== false){
			$bookings_result = $this->get_bookings()->delete();
		}
	}
	
	/**
	 * Duplicates this event and returns the duplicated event. Will return false if there is a problem with duplication.
	 * @return EM_Event
	 */
	function duplicate(){
		global $wpdb, $EZSQL_ERROR;
		//First, duplicate.
		$event_table_name = $wpdb->prefix . EVENTS_TBNAME;
		$eventArray = $this->to_array();
		unset($eventArray['event_id']);
		$result = $wpdb->insert($event_table_name, $eventArray);
		if($result !== false){
			//Get the ID of the new item
			$event_ID = $wpdb->insert_id;
			$EM_Event = new EM_Event( $event_ID );
			return $EM_Event;
		}else{
			//TODO add error notifications for duplication failures.
			return false;
		}
	}
	
	
	/**
	 * Validates the event. Should be run during any form submission or saving operation.
	 * @return boolean
	 */
	function validate() {
		$missing_fields = Array ();
		foreach ( $this->required_fields as $field ) {
			$true_field = $this->fields[$field]['name'];
			if ( $this->$true_field == "") {
				$missing_fields[] = $field;
			}
		}
		if ( count($missing_fields) > 0){
			// TODO Create friendly equivelant names for missing fields notice in validation 
			$this->errors[] = __ ( 'Missing fields: ' ) . implode ( ", ", $missing_fields ) . ". ";
		}
		if ( $_POST ['repeated_event'] == "1" && $this->end_date == "" ){
			$this->errors[] = __ ( 'Since the event is repeated, you must specify an event date.', 'dbem' );
		}
		if( !$this->location->validate() ){
			$this->errors = array_merge($this->errors, $this->location->errors);
		}
		//TODO validate recurrence during event validate
		return ( count($this->errors) == 0 );
	}

	
	/**
	 * Returns an array with category id and name (in that order) of the EM_Event instance.
	 * @return array
	 */
	function get_category() { 
		global $wpdb; 
		$sql = "SELECT category_id, category_name FROM ".$wpdb->prefix.EVENTS_TBNAME." LEFT JOIN ".$wpdb->prefix.DBEM_CATEGORIES_TBNAME." ON category_id=event_category_id WHERE event_id ='".$this->id."'";
	 	$category = $wpdb->get_row($sql, ARRAY_A);
		return $category;
	}
	
	/**
	 * Shortcut function for $this->get_bookings()->delete(), because using the EM_Bookings requires loading previous bookings, which isn't neceesary. 
	 */
	function delete_bookings(){
		global $wpdb;
		return $wpdb->query( $wpdb->prepare("DELETE FROM ".$wpdb->prefix.BOOKINGS_TBNAME." WHERE event_id=%d", $this->id) );
	}
	
	/**
	 * Retrieve and save the bookings belonging to instance. If called again will return cached version, set $force_reload to true to create a new EM_Bookings object.
	 * @param boolean $force_reload
	 * @return EM_Bookings
	 */
	function get_bookings( $force_reload = false ){
		if( get_option('dbem_rsvp_enabled') ){
			if( (!$this->bookings || $force_reload) ){
				$this->bookings = new EM_Bookings($this);
			}
		}
		return $this->bookings;
	}
	
	/**
	 * Will output a single event format of this event. 
	 * Equivalent of calling EM_Event::output( get_option ( 'dbem_single_event_format' ) )
	 * @param string $target
	 * @return string
	 */
	function output_single($target='html'){
		$format = get_option ( 'dbem_single_event_format' );
		return $this->output($format, $target);
	}

	/**
	 * Will output a event list item format of this event. 
	 * Equivalent of calling EM_Event::output( get_option ( 'dbem_event_list_item_format' ) )
	 * @param string $target
	 * @return string
	 */
	function output_list($target='html'){
		$format = get_option ( 'dbem_event_list_item_format' );
		return $this->output($format, $target);
	}
	
	/**
	 * Will output a event in the format passed in $format by replacing placeholders within the format.
	 * @param string $format
	 * @param string $target
	 * @return string
	 */	
	function output($format, $target="html") {
	 	$event_string = $format;
		preg_match_all("/#@?_?[A-Za-z0-9]+/", $format, $placeholders);
		foreach($placeholders[0] as $result) {
			if (preg_match('/#_EDITEVENTLINK/', $result)) { 
				$link = "";
				if(is_user_logged_in()){
					$link = "<a href=' ".get_bloginfo('wpurl')."/wp-admin/edit.php?page=events-manager/events-manager.php&action=edit_event&event_id=".$this->id."'>".__('Edit').' '.__('Event', 'dbem')."</a>";
					
				}
				$event_string = str_replace($result, $link , $event_string );		
			}
			if (preg_match('/#_24HSTARTTIME/', $result)) { 
				$time = substr($this->start_time, 0,5);
				$event_string = str_replace($result, $time , $event_string );		
			}
			if (preg_match('/#_24HENDTIME/', $result)) { 
				$time = substr($this->end_time, 0,5);
				$event_string = str_replace($result, $time , $event_string );		
			}			
			if (preg_match('/#_12HSTARTTIME/', $result)) {
				$AMorPM = "AM"; 
				$hour = substr($this->start_time, 0,2);   
				$minute = substr($this->start_time, 3,2);
				if ($hour > 12) {
					$hour = $hour -12;
					$AMorPM = "PM";
				}
				$time = "$hour:$minute $AMorPM";
				$event_string = str_replace($result, $time , $event_string );		
			}
			if (preg_match('/#_12HENDTIME/', $result)) {
				$AMorPM = "AM"; 
				$hour = substr($this->end_time, 0,2);   
				$minute = substr($this->end_time, 3,2);
				if ($hour > 12) {
					$hour = $hour -12;
					$AMorPM = "PM";
				}
				$time = "$hour:$minute $AMorPM";
				$event_string = str_replace($result, $time , $event_string );		
			}			
			if (preg_match('/#_ADDBOOKINGFORM/', $result)) {
			 	$rsvp_is_active = get_option('dbem_rsvp_enabled'); 
				if ($this->rsvp) {
				   $rsvp_add_module .= em_add_booking_form();
				} else {
					$rsvp_add_module .= "";
				}
			 	$event_string = str_replace($result, $rsvp_add_module , $event_string );
			}
			if (preg_match('/#_REMOVEBOOKINGFORM/', $result)) {
			 	$rsvp_is_active = get_option('dbem_rsvp_enabled'); 
				if ($this->rsvp) {
				   $rsvp_delete_module .= em_delete_booking_form();
				} else {
					$rsvp_delete_module .= "";
				}
			 	$event_string = str_replace($result, $rsvp_delete_module , $event_string );
			}
			if (preg_match('/#_AVAILABLESEATS/', $result)) {
			 	$rsvp_is_active = get_option('dbem_rsvp_enabled');
				if ($this->rsvp) {
				   $availble_seats = $this->get_bookings()->get_available_seats();
				} else {
					$availble_seats = "0";
				}
			 	$event_string = str_replace($result, $availble_seats , $event_string );
			} 
			if (preg_match('/#_LINKEDNAME/', $result)) {
				$events_page_id = get_option('dbem_events_page');
				$event_page_link = get_permalink($events_page_id);
				if (stristr($event_page_link, "?"))
					$joiner = "&amp;";
				else
					$joiner = "?";
				$event_string = str_replace($result, "<a href='".get_permalink($events_page_id).$joiner."event_id=".$this->id."'   title='".$this->name."'>".$this->name."</a>" , $event_string );
			} 
			if (preg_match('/#_EVENTPAGEURL(\[(.+\)]))?/', $result)) {
				$events_page_id = get_option('dbem_events_page');
				if (stristr($event_page_link, "?"))
					$joiner = "&amp;";
				else
					$joiner = "?";
				$event_string = str_replace($result, get_permalink($events_page_id).$joiner."event_id=".$this->id , $event_string );
			}	
		 	
		 	if (preg_match('/#_(NAME|NOTES|SEATS|EXCERPT)/', $result)) {
				$field = ltrim(strtolower($result), "#_");
			 	$field_value = $this->$field;      
				
				if ($field == "notes" || $field == "excerpt") {
					if ($target == "html"){
						//If excerpt, we use more link text
						if($field == "excerpt"){
							$matches = explode('<!--more-->', $this->notes);
							$field_value = $matches[0];
							$field_value = apply_filters('dbem_notes_excerpt', $field_value);
						}else{
							$field_value = apply_filters('dbem_notes', $field_value);
						}
						//$field_value = apply_filters('the_content', $field_value); - chucks a wobbly if we do this.
					}else{
					  if ($target == "map"){
						$field_value = apply_filters('dbem_notes_map', $field_value);
					  } else {
			  			if($field == "excerpt"){
							$matches = explode('<!--more-->', $this->notes);
							$field_value = htmlentities($matches[0]);
							$field_value = apply_filters('dbem_notes_rss', $field_value);
						}else{
							$field_value = apply_filters('dbem_notes_rss', $field_value);
						}
						$field_value = apply_filters('the_content_rss', $field_value);
					  }
					}
			  	} else {
					if ($target == "html"){    
						$field_value = apply_filters('dbem_general', $field_value); 
			  		}else{
						$field_value = apply_filters('dbem_general_rss', $field_value);
			  		}
				}
				$event_string = str_replace($result, $field_value , $event_string ); 
		 	}
		 	if (preg_match('/#_(CONTACTNAME|CONTACTPERSON)$/', $result)) {
				$event_string = str_replace($result, $this->contact->display_name, $event_string );
			}
			if (preg_match('/#_CONTACTEMAIL$/', $result)) {         
				$event_string = str_replace($result, dbem_ascii_encode($this->contact->user_email), $event_string );
			}
			if (preg_match('/#_CONTACTPHONE$/', $result)) {   
	      		if( $this->contact->phone == ''){ $phone = __('N/A', 'dbem'); }
				$event_string = str_replace($result, $phone, $event_string );
			}
		
			// matches all PHP START date placeholders
			if (preg_match('/^#[dDjlNSwzWFmMntLoYy]$/', $result)) {
				$event_string = str_replace($result, mysql2date(ltrim($result, "#"), $this->start_date),$event_string );
			}
			// matches all PHP END time placeholders for endtime
			if (preg_match('/^#@[dDjlNSwzWFmMntLoYy]$/', $result)) {
				$event_string = str_replace($result, mysql2date(ltrim($result, "#@"), $this->end_date), $event_string ); 
		 	}
			// matches all PHP START time placeholders
			if (preg_match('/^#[aABgGhHisueIOPTZcrU]$/', $result)) {   
				$event_string = str_replace($result, mysql2date(ltrim($result, "#"), "2000-10-10 ".$this->start_time),$event_string );
			}
			// matches all PHP END time placeholders
			if (preg_match('/^#@[aABgGhHisueIOPTZcrU]$/', $result)) {
				$event_string = str_replace($result, mysql2date(ltrim($result, "#@"), "2000-10-10 ".$this->end_time),$event_string );
			}			
			//Add a placeholder for categories
		 	if (preg_match('/^#_CATEGORY$/', $result)) {
	      		$category = EM_Category::get($this->category_id);
				$event_string = str_replace($result, $category['category_name'], $event_string );
			}
			     
		}
		//Time place holder that doesn't show if empty.
		preg_match_all('/#@?_\{[A-Za-z0-9 -\/,\.\\\]+\}/', $format, $results);
		foreach($results[0] as $result) {
			if(substr($result, 0, 3 ) == "#@_"){
				$date = 'end_date';
				$offset = 4;
			}else{
				$date = 'start_date';
				$offset = 3;
			}
			if( $date == 'event_end_date' && $this->$date == $this->start_date ){
				$event_string = str_replace($result, '', $event_string);
			}else{
				$event_string = str_replace($result, mysql2date(substr($result, $offset, (strlen($result)-($offset+1)) ), $this->$date),$event_string );
			}
		}
		//This is for the custom attributes
		preg_match_all('/#_ATT\{.+?\}(\{.+?\})?/', $format, $results);
		foreach($results[0] as $resultKey => $result) {
			//Strip string of placeholder and just leave the reference
			$attRef = substr( substr($result, 0, strpos($result, '}')), 6 );
			$attString = $this->attributes[$attRef];
			if( trim($attString) == '' && $results[1][$resultKey] != '' ){
				//Check to see if we have a second set of braces;
				$attString = substr( $results[1][$resultKey], 1, strlen(trim($results[1][$resultKey]))-2 );
			}
			$event_string = str_replace($result, $attString ,$event_string );
		}
		
		//Now do dependent objects
		$event_string = $this->location->output($event_string, $target);		
		return $event_string;		
	}
	
	/**********************************************************
	 * RECURRENCE METHODS
	 ***********************************************************/
	
	/**
	 * Saves events and replaces old ones. Returns true if sucecssful or false if not.
	 * @return boolean
	 */
	function save_events() {
		if( $this->is_recurring() ){
			global $wpdb;
			$event_saves = array();
			$matching_days = $this->get_recurrence_days(); //Get days where events recur
			$this->delete_events(); //Delete old events beforehand
			//Make template event (and we just change dates)
			$event = $this->to_array();
			unset($event['event_id']); //remove id and we have a event template to feed to wpdb insert
			$event['event_attributes'] = serialize($event['event_attributes']);
			foreach($event as $key => $value ){ //remove recurrence information
				if( substr($key, 0, 10) == 'recurrence' ){
					unset($event[$key]);
				}
			}
			$event['recurrence_id'] = $this->id;
			//Save event template with different dates
			foreach( $matching_days as $day ) {
				$event['event_start_date'] = date("Y-m-d", $day);
				$event['event_end_date'] = $event['event_start_date'];				
				$event_saves[] = $wpdb->insert($wpdb->prefix.EVENTS_TBNAME, $event, $this->get_types($event));
				if( DEBUG ){ echo "Entering recurrence " . date("D d M Y", $day)."<br/>"; }
		 	}
		 	return !in_array(false, $event_saves);
		}
		return false;
	}
	
	/**
	 * Removes all reoccurring events.
	 * @param $recurrence_id
	 * @return null
	 */
	function delete_events(){
		global $wpdb;
		//So we don't do something we'll regret later, we could just supply the get directly into the delete, but this is safer
		$EM_Events = EM_Events::get( array('recurrence_id'=>$this->id) );
		$event_ids = array();
		foreach($EM_Events as $EM_Event){
			if($EM_Event->recurrence_id == $this->id){
				$event_ids[] = $EM_Event->id; //ONLY ADD if id's match - hard coded
			}
		}
		EM_Events::delete( $event_ids );
	}
	
	/**
	 * Returns true if this event is a recurring event, meaning that it's not an individual event, 
	 * but an event that defines many events that recur over a span of time.
	 * For checking if a specific event is part of a greater set of recurring events, use is_recurrence()
	 * @return boolean
	 */
	function is_recurring(){
		return ( $this->recurrence );
	}	
	/**
	 * Will return true if this individual event is part of a set of events that recur
	 * For checking if this is the "master recurring event", see is_recurring() 
	 * @return boolean
	 */
	function is_recurrence(){
		return ( $this->id > 0 && $this->recurrence_id > 0 );
	}
	/**
	 * Returns if this is an individual event and is not recurring or a recurrence
	 * @return boolean
	 */
	function is_individual(){
		return ( !$this->is_recurring() && !$this->is_recurrence() );
	}
	
	/**
	 * Returns the days that match the recurrance array passed
	 * @param array $recurrence
	 * @return array
	 */
	function get_recurrence_days(){
		if( $this->is_recurring() ){
			$start_date = mktime(0, 0, 0, substr($this->start_date,5,2), substr($this->start_date,8,2), substr($this->start_date,0,4));
			$end_date = mktime(0, 0, 0, substr($this->end_date,5,2), substr($this->end_date,8,2), substr($this->end_date,0,4));   
			
			$last_week_start = array(25, 22, 25, 24, 25, 24, 25, 25, 24, 25, 24, 25);			
			$weekdays = explode(",", $this->byday);
			
			$weekcounter = 0;
			$daycounter = 0; 
			$counter = 0;
			$cycle_date = $start_date;     
			$matching_days = array(); 
			$aDay = 86400;  // a day in seconds  
		 
		  
			while (date("d-M-Y", $cycle_date) != date('d-M-Y', $end_date + $aDay)) {
		 	 //echo (date("d-M-Y", $cycle_date));
				$style = "";
				$monthweek =  floor(((date("d", $cycle_date)-1)/7))+1;   
				 if($this->freq == 'daily') {
						
						if($counter % $this->interval == 0 )
							array_push($matching_days, $cycle_date);
						$counter++;
				}
			    $weekday_num = date("w", $cycle_date); if ($weekday_num == 0) { $weekday_num = 7; }
				if (in_array( $weekday_num, $weekdays )) {
					$monthday = date("j", $cycle_date); 
					$month = date("n", $cycle_date);      
		
					if($this->freq == 'weekly') {
					
						if($counter % $this->interval == 0 )
							array_push($matching_days, $cycle_date);
						$counter++;
					}
					if($this->freq == 'monthly') { 
					
				   		if(($this->byweekno == -1) && ($monthday >= $last_week_start[$month-1])) {
							if ($counter % $this->interval == 0)
								array_push($matching_days, $cycle_date);
							$counter++;
						} elseif($this->byweekno == $monthweek) {
							if ($counter % $this->interval == 0)
								array_push($matching_days, $cycle_date);
							$counter++;
					  }
					}
					$weekcounter++;
			  }
				$daycounter++;
			  $cycle_date = $cycle_date + $aDay;         //adding a day       
		
			}   
				
			return $matching_days ;
		}
	}
	
	/**
	 * Returns a string representation of this recurrence. Will return false if not a recurrence
	 * @return string
	 */
	function get_recurrence_description() { 
		//FIXME Recurrence description not working for recurrence 
		global $EM_Recurrences;
		if( $this->is_individual() ) return false;
		$recurrence = $EM_Recurrences[$this->recurrence_id]->to_array();
		$weekdays_name = array(__('Monday'),__('Tuesday'),__('Wednesday'),__('Thursday'),__('Friday'),__('Saturday'),__('Sunday'));
		$monthweek_name = array('1' => __('the first %s of the month', 'dbem'),'2' => __('the second %s of the month', 'dbem'), '3' => __('the third %s of the month', 'dbem'), '4' => __('the fourth %s of the month', 'dbem'), '-1' => __('the last %s of the month', 'dbem'));
		$output = sprintf (__('From %1$s to %2$s', 'dbem'),  $recurrence['event_start_date'], $recurrence['event_end_date']).", ";
		if ($recurrence['recurrence_freq'] == 'daily')  {
		  
			$freq_desc =__('everyday', 'dbem');
			if ($recurrence['recurrence_interval'] > 1 ) {
				$freq_desc = sprintf (__("every %s days", 'dbem'), $recurrence['recurrence_interval']);
			}
		}
		if ($recurrence['recurrence_freq'] == 'weekly')  {
			$weekday_array = explode(",", $recurrence['recurrence_byday']);
			$natural_days = array();
			foreach($weekday_array as $day)
				array_push($natural_days, $weekdays_name[$day-1]);
			$output .= implode(" and ", $natural_days);
			if ($recurrence['recurrence_interval'] > 1 ) {
				$freq_desc = ", ".sprintf (__("every %s weeks", 'dbem'), $recurrence['recurrence_interval']);
			}
			
		} 
		if ($recurrence['recurrence_freq'] == 'monthly')  {
			 $weekday_array = explode(",", $recurrence['recurrence_byday']);
				$natural_days = array();
				foreach($weekday_array as $day)
					array_push($natural_days, $weekdays_name[$day-1]);
				$freq_desc = sprintf (($monthweek_name[$recurrence['recurrence_byweekno']]), implode(" and ", $natural_days));
			if ($recurrence['recurrence_interval'] > 1 ) {
				$freq_desc .= ", ".sprintf (__("every %s months",'dbem'), $recurrence['recurrence_interval']);
			}
			
		}
		$output .= $freq_desc;
		return  $output;
	}
	
	/**********************************************************
	 * UTILITIES
	 ***********************************************************/

	/**
	 * Returns this object in the form of an array, useful for saving directly into the wp_dbem_events table.
	 * @param boolean $location
	 * @param boolean $for_database
	 * @return array
	 */
	function to_array($location = false, $for_database = false){
		$event = array();
		//Core Event Data
		foreach ( $this->fields as $key => $val ) {
			//TODO does it matter if it's for db or not... shouldn't it just not include blanks?
			if( !$for_database || $for_database && $this->$val['name'] ){
				$event[$key] = $this->$val['name'];
			}
		}
		//Location Data
		if($location && is_object($this->location)){
			$location = $this->location->to_array();
			$event = array_merge($event, $location);
		}
		return $event;
	}
}
?>