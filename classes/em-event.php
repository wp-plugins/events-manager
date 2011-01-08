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
		'recurrence_interval' => array( 'name'=>'interval', 'type'=>'%d' ), //every x day(s)/week(s)/month(s)
		'recurrence_freq' => array( 'name'=>'freq', 'type'=>'%s' ), //daily,weekly,monthly?
		'recurrence_byday' => array( 'name'=>'byday', 'type'=>'%s' ), //if weekly or monthly, what days of the week?
		'recurrence_byweekno' => array( 'name'=>'byweekno', 'type'=>'%d' ) //if monthly which week (-1 is last)
	);
	/* Field Names  - see above for matching DB field names and other field meta data */
	var $id;
	var $author;
	var $name;
	var $start_time;
	var $end_time;
	var $start_date;
	var $end_date;
	var $notes;
	var $rsvp;
	var $seats;
	var $contactperson_id;
	var $location_id;
	var $recurrence_id;
	var $category_id;
	var $attributes;
	var $recurrence;
	var $interval;
	var $freq;
	var $byday;
	var $byweekno;
	
	/**
	 * Timestamp of start date/time
	 * @var int
	 */
	var $start;
	
	/**
	 * Timestamp of end date/time
	 * @var int
	 */
	var $end;
	
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
	 * The category object
	 * @var EM_Category
	 */
	var $category;
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
				$this->location = new EM_Location( $event );
			}elseif( is_numeric($event_data) && $event_data > 0 ){
				//Retreiving from the database  
				$events_table = $wpdb->prefix . EM_EVENTS_TABLE;
				$locations_table = $wpdb->prefix . EM_LOCATIONS_TABLE;
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
			if( !empty($event['event_attributes']) ){
				if( is_serialized($event['event_attributes']) ){
					$event['event_attributes'] = @unserialize($event['event_attributes']);					
				}
				$event['event_attributes'] = (!is_array($event['event_attributes'])) ?  array() : $event['event_attributes'] ;
			}
			$event['recurrence_byday'] = ( $event['recurrence_byday'] == 7 ) ? 0:$event['recurrence_byday']; //Backward compatibility (since 3.0.3), using 0 makes more sense due to date() function
			$this->to_object($event, true);
			
			//Start/End times should be available as timestamp
			$this->start = strtotime($this->start_date." ".$this->start_time);
			$this->end = strtotime($this->end_date." ".$this->end_time);
			
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
	      		$this->contact->phone = get_metadata('user', $this->contact->ID, 'dbem_phone', true);
			}
			//Now, if this is a recurrence, get the recurring for caching to the $EM_Recurrences
			if( $this->is_recurrence() && !array_key_exists($this->recurrence_id, $EM_Recurrences) ){
				$EM_Recurrences[$this->recurrence_id] = new EM_Event($this->recurrence_id);
			}
		}else{
			$this->location = new EM_Location(); //blank location
		}
	}
	
	/**
	 * Retrieve event, location and recurring information via POST
	 * @return boolean
	 */
	function get_post(){
		//Build Event Array
		do_action('em_event_get_post_pre', $this);
		$this->name = ( !empty($_POST['event_name']) ) ? stripslashes($_POST['event_name']) : '' ;
		$this->start_date = ( !empty($_POST['event_start_date']) ) ? $_POST['event_start_date'] : '';
		$this->end_date = ( !empty($_POST['event_end_date']) ) ? $_POST['event_end_date'] : $this->start_date; 
		$this->rsvp = ( !empty($_POST['event_rsvp']) ) ? 1:0;
		$this->seats = ( !empty($_POST['event_seats']) && is_numeric($_POST['event_seats']) ) ? $_POST['event_seats']:0;
		$this->notes = ( !empty($_POST['content']) ) ? stripslashes($_POST['content']) : ''; //WP TinyMCE field
		//Sort out time
		//TODO make time handling less painful
		$match = array();
		if( !empty($_POST['event_start_time']) && preg_match ( '/^([01]\d|2[0-3]):([0-5]\d)(AM|PM)?$/', $_POST['event_start_time'], $match ) ){
			if( $match[3] == 'PM' && $match[1] != 12 ){
				$match[1] = 12+$match[1];
			}elseif( $match[3] == 'AM' && $match[1] == 12 ){
				$match[1] = '00';
			} 
			$this->start_time = $match[1].":".$match[2].":00";
		}else{
			$this->start_time = "00:00:00";
		}
		if( !empty($_POST['event_end_time']) && preg_match ( '/^([01]\d|2[0-3]):([0-5]\d)(AM|PM)?$/', $_POST['event_end_time'], $match ) ){
			if( $match[3] == 'PM' && $match[1] != 12 ){
				$match[1] = 12+$match[1];
			}elseif( $match[3] == 'AM' && $match[1] == 12 ){
				$match[1] = '00';
			}  
			$this->end_time = $match[1].":".$match[2].":00";
		}else{
			$this->end_time = $this->start_time;
		}
		//Start/End times should be available as timestamp
		$this->start = strtotime($this->start_date." ".$this->start_time);
		$this->end = strtotime($this->end_date." ".$this->end_time);
		//Contact Person
		if ( !empty($_POST['event_contactperson_id']) && is_numeric($_POST['event_contactperson_id']) ) {		
			//TODO contactperson choices needs limiting depending on role	
			$this->contactperson_id = $_POST['event_contactperson_id'];
		}
		//category
		if( !empty($_POST['event_category_id']) && is_numeric($_POST['event_category_id']) ){
			$this->category_id = $_POST['event_category_id'];
		}	
		//Attributes
		$event_attributes = array();
		for($i=1 ; !empty($_POST["mtm_{$i}_ref"]) && trim($_POST["mtm_{$i}_ref"]) != '' ; $i++ ){
	 		if( !empty($_POST["mtm_{$i}_name"]) && trim($_POST["mtm_{$i}_name"]) != '' ){
		 		$event_attributes[$_POST["mtm_{$i}_ref"]] = stripslashes($_POST["mtm_{$i}_name"]);
	 		}
	 	}
	 	$this->attributes = $event_attributes;
		//Recurrence data
		$this->recurrence_id = ( !empty($_POST['recurrence_id']) && is_numeric($_POST['recurrence_id']) ) ? $_POST['recurrence_id'] : 0 ;
		if( !empty($_POST['repeated_event']) ){
			$this->recurrence = 1;
			$this->freq = ( !empty($_POST['recurrence_freq']) && in_array($_POST['recurrence_freq'], array('daily','weekly','monthly')) ) ? $_POST['recurrence_freq']:'daily';
			if( !empty($_POST['recurrence_bydays']) && $this->freq == 'weekly' && self::array_is_numeric($_POST['recurrence_bydays']) ){
				$this->byday = implode ( ",", $_POST['recurrence_bydays'] );	
			}elseif( !empty($_POST['recurrence_byday']) && $this->freq == 'monthly' ){
				$this->byday = $_POST['recurrence_byday'];
			}
			$this->interval = ( !empty($_POST['recurrence_interval']) ) ? $_POST['recurrence_interval']:1;
			$this->byweekno = ( !empty($_POST['recurrence_byweekno']) ) ? $_POST['recurrence_byweekno']:'';
		}
		
		//Add location information, or just link to previous location, this is a requirement...
		if( !empty($_POST['location-select-id']) ) {
			$this->location = new EM_Location($_POST['location-select-id']);
		} else {
			$this->location = new EM_Location($_POST); 
			$this->location->load_similar($_POST);                
		}
		return apply_filters('em_event_get_post', $this->validate(), $this);
	}
	
	/**
	 * Will save the current instance into the database, along with location information if a new one was created and return true if successful, false if not.
	 * Will automatically detect what type of event it is (recurrent, recurrence or normal) and whether it's a new or existing event. 
	 * @return boolean
	 */
	function save(){
		//FIXME Event doesn't save title when inserting first time
		global $wpdb, $current_user;
		do_action('em_event_save_pre', $this);
   		get_currentuserinfo();
		$events_table = $wpdb->prefix.EM_EVENTS_TABLE;
		//First let's save the location, no location no event!
		if ( !$this->location->id && !$this->location->save() ){ //shouldn't try to save if location exists
			$this->errors[] = __ ( 'There was a problem saving the location so event was not saved.', 'dbem' );
	 		return apply_filters('em_event_save', false, $this);
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
				 		return apply_filters('em_event_save', false, $this);
				 	}
				 	//All good! Event Saved
					$this->feedback_message = __ ( 'New recurrent event inserted!', 'dbem' );
					return apply_filters('em_event_save', true, $this);
				}
				//Successful individual save
				$this->feedback_message = __ ( 'New event successfully inserted!', 'dbem' );
				return apply_filters('em_event_save', true, $this);
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
						return apply_filters('em_event_save', false, $this);
					}
					$this->feedback_message = __ ( 'Recurrence updated!', 'dbem' );
					return apply_filters('em_event_save', true, $this);			
				}
			}else{
				$this->errors[] = __('Could not save the event details due to a database error.', 'dbem');
				return apply_filters('em_event_save', false, $this);
			}
			//Successful individual or recurrence save
			$this->feedback_message = "{$this->name} " . __ ( 'updated', 'dbem' ) . "!";
			if($this->rsvp == 0){
				$this->delete_bookings();
			}
			return apply_filters('em_event_save', true, $this);
		}
	}
	
	/**
	 * Delete whole event, including recurrence and recurring data
	 * @param $recurrence_id
	 * @return boolean
	 */
	function delete(){
		global $wpdb;
		//TODO when only php5, no need to pass by reference
		do_action('em_event_delete_pre', $this);
		if( $this->is_recurring() ){
			//Delete the recurrences then this recurrence event
			$this->delete_events();
		}
		$result = $wpdb->query ( $wpdb->prepare("DELETE FROM ". $wpdb->prefix . EM_EVENTS_TABLE ." WHERE event_id=%d", $this->id) );
		if($result !== false){
			$result = $this->get_bookings()->delete();
		}
		return apply_filters('em_event_delete', $result, $this);
	}
	
	/**
	 * Duplicates this event and returns the duplicated event. Will return false if there is a problem with duplication.
	 * @return EM_Event
	 */
	function duplicate(){
		global $wpdb, $EZSQL_ERROR;
		//First, duplicate.
		$event_table_name = $wpdb->prefix . EM_EVENTS_TABLE;
		$eventArray = $this->to_array(true);
		unset($eventArray['event_id']);
		$EM_Event = new EM_Event( $eventArray );
		if( $EM_Event->save() ){
			$EM_Event->feedback_message = __("You are now viewing the duplicated event", 'dbem');
			return apply_filters('em_event_duplicate', $EM_Event, $this);
		}else{
			//TODO add error notifications for duplication failures.
			return apply_filters('em_event_duplicate', false, $this);;
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
		if ( !empty($_POST['repeated_event']) && $_POST['repeated_event'] == "1" && $this->end_date == "" ){
			$this->errors[] = __ ( 'Since the event is repeated, you must specify an event date.', 'dbem' );
		}
		if( !$this->location->validate() ){
			$this->errors = array_merge($this->errors, $this->location->errors);
		}
		//TODO validate recurrence during event validate
		return apply_filters('em_event_validate', count($this->errors) == 0, $this );
	}

	
	/**
	 * Returns an array with category id and name (in that order) of the EM_Event instance.
	 * @return array
	 */
	function get_category() {
		global $EM_Category;
		if( is_object($this->category) && get_class($this->category)=='EM_Category' && $this->category_id == $this->category->id ){
			return $this->category;
		}elseif( is_object($EM_Category) && $EM_Category->id == $this->category_id ){
			$this->category = $EM_Category;
		}else{
			$this->category = new EM_Category($this->category_id);
		}
		return $this->event; 
		global $wpdb; 
		$sql = "SELECT category_id, category_name FROM ".$wpdb->prefix.EM_EVENTS_TABLE." LEFT JOIN ".$wpdb->prefix.EM_CATEGORIES_TABLE." ON category_id=event_category_id WHERE event_id ='".$this->id."'";
	 	$category = $wpdb->get_row($sql, ARRAY_A);
		return apply_filters('em_event_get_category', $category, $this);
	}
	
	/**
	 * Shortcut function for $this->get_bookings()->delete(), because using the EM_Bookings requires loading previous bookings, which isn't neceesary. 
	 */
	function delete_bookings(){
		global $wpdb;
		do_action('em_event_delete_bookings_pre', $this);
		$result = $wpdb->query( $wpdb->prepare("DELETE FROM ".$wpdb->prefix.EM_BOOKINGS_TABLE." WHERE event_id=%d", $this->id) );
		return apply_filters('em_event_delete_bookings', $result, $this);
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
		return apply_filters('em_event_get_bookings', $this->bookings, $this);
	}
	
	/**
	 * Will output a single event format of this event. 
	 * Equivalent of calling EM_Event::output( get_option ( 'dbem_single_event_format' ) )
	 * @param string $target
	 * @return string
	 */
	function output_single($target='html'){
		$format = get_option ( 'dbem_single_event_format' );
		return apply_filters('em_event_output_single', $this->output($format, $target), $this, $target);
	}

	/**
	 * Will output a event list item format of this event. 
	 * Equivalent of calling EM_Event::output( get_option ( 'dbem_event_list_item_format' ) )
	 * @param string $target
	 * @return string
	 */
	function output_list($target='html'){
		$format = get_option ( 'dbem_event_list_item_format' );
		return apply_filters('em_event_output_list', $this->output($format, $target), $this, $target);
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
			$match = true;
			$replace = '';
			switch( $result ){
				//Event Details
				case '#_NAME':
					$replace = $this->name;
					break;
				case '#_NOTES':
				case '#_EXCERPT':
					//SEE AT BOTTOM OF FILE FOR OLD TARGET FILTERS FROM 2.x
					$replace = $this->notes;
					if($result == "#_EXCERPT"){
						$matches = explode('<!--more', $this->notes);
						$replace = $matches[0];
					}
					break;
				case '#_CATEGORY':
		      		$category = EM_Category::get($this->category_id);
					$replace = $category['category_name'];
					break;
				//Times
				case '#_24HSTARTTIME':
				case '#_24HENDTIME':
					$time = ($result == '#_24HSTARTTIME') ? $this->start_time:$this->end_time;
					$replace = substr($time, 0,5);
					break;
				case '#_12HSTARTTIME':
				case '#_12HENDTIME':
					$time = ($result == '#_12HSTARTTIME') ? $this->start_time:$this->end_time;
					$replace = date('g:i A', strtotime($time));
					break;
				//Links
				case '#_EVENTPAGEURL': //Depreciated	
				case '#_LINKEDNAME': //Depreciated
				case '#_EVENTURL': //Just the URL
				case '#_EVENTLINK': //HTML Link
					$joiner = (stristr(EM_URI, "?")) ? "&amp;" : "?";
					$event_link = EM_URI.$joiner."event_id=".$this->id;
					if($result == '#_LINKEDNAME' || $result == '#_EVENTLINK'){
						$replace = "<a href='{$event_link}' title='{$this->name}'>{$this->name}</a>";
					}else{
						$replace = $event_link;	
					}
					break;
				case '#_EDITEVENTLINK':
					if(is_user_logged_in()){
						//TODO user should have permission to edit the event
						$replace = "<a href='".get_bloginfo('wpurl')."/wp-admin/admin.php?page=events-manager-event&amp;event_id={$this->id}'>".__('Edit').' '.__('Event', 'dbem')."</a>";
					}	 
					break;
				//Bookings
				case '#_ADDBOOKINGFORM':
				case '#_REMOVEBOOKINGFORM':
				case '#_BOOKINGFORM':
					if ($this->rsvp && get_option('dbem_rsvp_enabled')){
						if($result == '#_BOOKINGFORM'){
							$replace = em_add_booking_form().em_delete_booking_form();
						}else{
							$replace = ($result == '#_ADDBOOKINGFORM') ? em_add_booking_form():em_delete_booking_form();
						}
					}
					break;
				case '#_AVAILABLESEATS': //Depreciated
				case '#_AVAILABLESPACES':
					if ($this->rsvp && get_option('dbem_rsvp_enabled')) {
					   $replace = $this->get_bookings()->get_available_seats();
					} else {
						$replace = "0";
					}
					break;
				case '#_BOOKEDSEATS': //Depreciated
				case '#_BOOKEDSPACES':
					if ($this->rsvp && get_option('dbem_rsvp_enabled')) {
					   $replace = $this->get_bookings()->get_booked_seats();
					} else {
						$replace = "0";
					}
					break;
				case '#_SEATS': //Depreciated
				case '#_SPACES':
					$replace = $this->seats;
					break;
				//Contact Person
				case '#_CONTACTNAME':
				case '#_CONTACTPERSON': //Depreciated (your call, I think name is better)
					$replace = $this->contact->display_name;
					break;
				case '#_CONTACTUSERNAME':
					$replace = $this->contact->user_login;
					break;
				case '#_CONTACTEMAIL':
				case '#_CONTACTMAIL': //Depreciated
					$replace = $this->contact->user_email;
					break;
				case '#_CONTACTPHONE':
		      		$replace = ( $this->contact->phone != '') ? $this->contact->phone : __('N/A', 'dbem');
					break;
				case '#_CONTACTAVATAR': 
					$replace = get_avatar( $this->contact->ID, $size = '50' ); 
					break;
				case '#_CONTACTPROFILELINK':
				case '#_CONTACTPROFILEURL':
					if( function_exists('bp_loggedin_user_link') ){
						$replace = bp_get_loggedin_user_link();
						if( $result == '#_CONTACTPROFILELINK' ){
							$replace = '<a href="'.$replace.'">'.__('Profile').'</a>';
						}
					}
					break;
				default:
					$match = false;
					break;
			}
			if($match){ //if true, we've got a placeholder that needs replacing
				//TODO FILTER - placeholder filter
				$replace = apply_filters('em_event_output_placeholder', $replace, $this, $result, $target);
				$event_string = str_replace($result, $replace , $event_string );
			}
		}
		//Time placeholders
		foreach($placeholders[0] as $result) {
			// matches all PHP START date and time placeholders
			if (preg_match('/^#[dDjlNSwzWFmMntLoYyaABgGhHisueIOPTZcrU]$/', $result)) {
				$replace = date_i18n(ltrim($result, "#"), $this->start);
				$replace = apply_filters('em_event_output_placeholder', $replace, $this, $result, $target);
				$event_string = str_replace($result, $replace, $event_string );
			}
			// matches all PHP END time placeholders for endtime
			if (preg_match('/^#@[dDjlNSwzWFmMntLoYyaABgGhHisueIOPTZcrU]$/', $result)) {
				$replace = date_i18n(ltrim($result, "#@"), $this->end);
				$replace = apply_filters('em_event_output_placeholder', $replace, $this, $result, $target);
				$event_string = str_replace($result, $replace, $event_string ); 
		 	}
		}
		//Time place holder that doesn't show if empty.
		//TODO add filter here too
		preg_match_all('/#@?_\{[A-Za-z0-9 -\/,\.\\\]+\}/', $format, $results);
		foreach($results[0] as $result) {
			if(substr($result, 0, 3 ) == "#@_"){
				$date = 'end_date';
				$offset = 4;
			}else{
				$date = 'start_date';
				$offset = 3;
			}
			if( $date == 'end_date' && $this->end_date == $this->start_date ){
				$replace = __( apply_filters('em_event_output_placeholder', '', $this, $result, $target) );
			}else{
				$replace = __( apply_filters('em_event_output_placeholder', mysql2date(substr($result, $offset, (strlen($result)-($offset+1)) ), $this->$date), $this, $result, $target) );
			}
			$event_string = str_replace($result,$replace,$event_string );
		}
		//This is for the custom attributes
		preg_match_all('/#_ATT\{.+?\}(\{.+?\})?/', $format, $results);
		foreach($results[0] as $resultKey => $result) {
			//Strip string of placeholder and just leave the reference
			$attRef = substr( substr($result, 0, strpos($result, '}')), 6 );
			$attString = '';
			if( array_key_exists($attRef, $this->attributes) ){
				$attString = $this->attributes[$attRef];
				if( trim($attString) == '' && $results[1][$resultKey] != '' ){
					//Check to see if we have a second set of braces;
					$attString = substr( $results[1][$resultKey], 1, strlen(trim($results[1][$resultKey]))-2 );
				}
			}
			$event_string = str_replace($result, $attString ,$event_string );
		}
		
		//Now do dependent objects
		$event_string = $this->location->output($event_string, $target);		
		return apply_filters('em_event_output', $event_string, $this, $target);
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
			do_action('em_event_save_events_pre', $this); //actions/filters only run if event is recurring
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
				$event_saves[] = $wpdb->insert($wpdb->prefix.EM_EVENTS_TABLE, $event, $this->get_types($event));
				//TODO should be EM_DEBUG, and do we really need it?
				if( DEBUG ){ echo "Entering recurrence " . date("D d M Y", $day)."<br/>"; }
		 	}
		 	return apply_filters('em_event_save_events', !in_array(false, $event_saves), $this);
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
		do_action('em_event_delete_events_pre', $this);
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
	 * Can the user manage this event? 
	 */
	function can_manage(){
		return ( get_option('dbem_disable_ownership') || $this->author == get_current_user_id() || empty($this->id) );
	}
	
	/**
	 * Returns the days that match the recurrance array passed (unix timestamps)
	 * @param array $recurrence
	 * @return array
	 */
	function get_recurrence_days(){
		if( $this->is_recurring() ){
			
			$start_date = strtotime($this->start_date);
			$end_date = strtotime($this->end_date);
					
			$weekdays = explode(",", $this->byday); //what days of the week (or if monthly, one value at index 0)
			 
			$matching_days = array(); 
			$aDay = 86400;  // a day in seconds
			$aWeek = $aDay * 7;		 
				
			//TODO can this be optimized?
			switch ( $this->freq ){
				case 'daily':
					//If daily, it's simple. Get start date, add interval timestamps to that and create matching day for each interval until end date.
					$current_date = $start_date;
					while( $current_date <= $end_date ){
						$matching_days[] = $current_date;
						$current_date = $current_date + ($aDay * $this->interval);
					}
					break;
				case 'weekly':
					//sort out week one, get starting days and then days that match time span of event (i.e. remove past events in week 1)
					$start_of_week = get_option('start_of_week'); //Start of week depends on wordpress
					//first, get the start of this week as timestamp
					$event_start_day = date('w', $start_date);
					$offset = 0;
					if( $event_start_day > $start_of_week ){
						$offset = $event_start_day - $start_of_week; //x days backwards
					}elseif( $event_start_day < $start_of_week ){
						$offset = $start_of_week;
					}
					$start_week_date = $start_date - ( ($event_start_day - $start_of_week) * $aDay );
					//then get the timestamps of weekdays during this first week, regardless if within event range
					$start_weekday_dates = array(); //Days in week 1 where there would events, regardless of event date range
					for($i = 0; $i < 7; $i++){
						$weekday_date = $start_week_date+($aDay*$i); //the date of the weekday we're currently checking
						$weekday_day = date('w',$weekday_date); //the day of the week we're checking, taking into account wp start of week setting
						if( in_array( $weekday_day, $weekdays) ){
							$start_weekday_dates[] = $weekday_date; //it's in our starting week day, so add it
						}
					}					
					//for each day of eventful days in week 1, add 7 days * weekly intervals
					foreach ($start_weekday_dates as $weekday_date){
						//Loop weeks by interval until we reach or surpass end date
						while($weekday_date <= $end_date){
							if( $weekday_date >= $start_date && $weekday_date <= $end_date ){
								$matching_days[] = $weekday_date;
							}
							$weekday_date = $weekday_date + ($aWeek *  $this->interval);
						}
					}//done!
					break;  
				case 'monthly':
					//loop months starting this month by intervals
					$current_arr = getdate($start_date);
					$end_arr = getdate($end_date);
					$end_month_date = strtotime( date('Y-m-t', $end_date) ); //End date on last day of month
					$current_date = strtotime( date('Y-m-1', $start_date) ); //Start date on first day of month
					while( $current_date <= $end_month_date ){
						$last_day_of_month = date('t', $current_date);
						//Now find which day we're talking about
						$current_week_day = date('w',$current_date);
						$matching_month_days = array();
						//Loop through days of this years month and save matching days to temp array
						for($day = 1; $day <= $last_day_of_month; $day++){
							if($current_week_day == $this->byday){
								$matching_month_days[] = $day;
							}
							$current_week_day = ($current_week_day < 6) ? $current_week_day+1 : 0;							
						}
						//Now grab from the array the x day of the month
						$matching_day = ($this->byweekno > 0) ? $matching_month_days[$this->byweekno-1] : array_pop($matching_month_days);
						$matching_date = strtotime(date('Y-m',$current_date).'-'.$matching_day);
						if($matching_date >= $start_date && $matching_date <= $end_date){
							$matching_days[] = $matching_date;
						}
						//add the number of days in this month to make start of next month
						$current_arr['mon'] += $this->interval;
						if($current_arr['mon'] > 12){
							//FIXME this won't work if interval is more than 12
							$current_arr['mon'] = $current_arr['mon'] - 12;
							$current_arr['year']++;
						}
						$current_date = strtotime("{$current_arr['year']}-{$current_arr['mon']}-1"); 
					}
					break;
			}	
			sort($matching_days);
			//TODO delete this after testing
			/*Delete*/
			$test_dates = array();
			foreach($matching_days as $matching_day){
				$test_dates[] = date('d/m/Y', $matching_day);
			}	
			/*end delete*/		
			return $matching_days;
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
		$weekdays_name = array(__('Sunday'),__('Monday'),__('Tuesday'),__('Wednesday'),__('Thursday'),__('Friday'),__('Saturday'));
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
			foreach($weekday_array as $day){
				array_push($natural_days, $weekdays_name[$day]);
			}
			$output .= implode(" and ", $natural_days);
			$freq_desc = ", " . __("every week", 'dbem');
			if ($recurrence['recurrence_interval'] > 1 ) {
				$freq_desc = ", ".sprintf (__("every %s weeks", 'dbem'), $recurrence['recurrence_interval']);
			}
			
		} 
		if ($recurrence['recurrence_freq'] == 'monthly')  {
			$weekday_array = explode(",", $recurrence['recurrence_byday']);
			$natural_days = array();
			foreach($weekday_array as $day){
				array_push($natural_days, $weekdays_name[$day]);
			}
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

//TODO placeholder targets filtering could be streamlined better
/**
 * This is a temporary filter function which mimicks the old filters in the old 2.x placeholders function
 * @param string $result
 * @param EM_Event $event
 * @param string $placeholder
 * @param string $target
 * @return mixed
 */
function em_event_output_placeholder($result,$event,$placeholder,$target='html'){	
	if( ($placeholder == "#_EXCERPT" || $placeholder == "#_LOCATIONEXCERPT") && $target == 'html' ){
		$result = apply_filters('dbem_notes_excerpt', $result);
	}elseif( $placeholder == '#_CONTACTEMAIL' && $target == 'html' ){
		$result = em_ascii_encode($event->contact->user_email);
	}elseif( $placeholder == "#_NOTES" || $placeholder == "#_EXCERPT" || $placeholder == "#_LOCATIONEXCERPT" ){
		if($target == 'html'){
			$result = apply_filters('dbem_notes', $result);
		}elseif($target == 'map'){
			$result = apply_filters('dbem_notes_map', $result);
		}else{
			$result = apply_filters('dbem_notes_rss', $result);
			$result = apply_filters('the_content_rss', $result);
		}
	}elseif( in_array($placeholder, array("#_NAME",'#_ADDRESS','#_LOCATION','#_TOWN')) ){
		if ($target == "html"){    
			$result = apply_filters('dbem_general', $result); 
	  	}else{
			$result = apply_filters('dbem_general_rss', $result);
	  	}				
	}
	return $result;
}
add_filter('em_event_output_placeholder','em_event_output_placeholder',1,4);
?>