<?   
// should be called if get_option('dbem_version') < EM_VERSION, after the last create table statement.                   
// Should work, but we'll need to test this againt old datasets
// Hadn't time to test it with your latest build yet, will do this evening
         
// Also, to use this you need to change the TBName constants

function em_migrate_to_new_tables(){
	define('OLD_EVENTS_TBNAME','dbem_events') ; 
	define('OLD_RECURRENCE_TBNAME','dbem_recurrence'); //TABLE NAME   
	define('OLD_LOCATIONS_TBNAME','dbem_locations'); //TABLE NAME  
	define('OLD_BOOKINGS_TBNAME','dbem_bookings'); //TABLE NAME
	define('OLD_PEOPLE_TBNAME','dbem_people'); //TABLE NAME  
	define('OLD_BOOKING_PEOPLE_TBNAME','dbem_bookings_people'); //TABLE NAME   
	define('OLD_DBEM_CATEGORIES_TBNAME', 'dbem_categories'); //TABLE NAME
	
	global $wpdb;                       
	
	// migrating events
	$events = $wpdb->get_results('SELECT * FROM '.$wpdb->prefix.OLD_EVENTS_TBNAME,ARRAY_A)  ;
	foreach($events as $e) {                
		$wpdb->insert($wpdb->prefix.EVENTS_TBNAME, $e);
	}     
	
	// inserting recurrences into events          
	$results = $wpdb->get_results('SELECT * FROM '.$wpdb->prefix.RECURRENCE_TBNAME, ARRAY_A);
	foreach($results as $recurrence_raw){
		//Save copy of recurrence_id
		$recurrence_id = $recurrence_raw['recurrence_id'];
		//First insert the event into events table
		$recurrence = array(); //Save new array with correct indexes
		$recurrence['event_id'] = $recurrence_raw['recurrence_id'];
		$recurrence['event_author'] = $user_ID;
		$recurrence['event_name'] = $recurrence_raw['recurrence_name'];
		$recurrence['event_start_date'] = $recurrence_raw['recurrence_start_date'];
		$recurrence['event_end_date'] = $recurrence_raw['recurrence_end_date'];
		$recurrence['event_start_time'] = $recurrence_raw['recurrence_start_time'];
		$recurrence['event_end_time'] = $recurrence_raw['recurrence_end_time'];
		$recurrence['event_notes'] = $recurrence_raw['recurrence_notes'];
		$recurrence['location_id'] = $recurrence_raw['location_id'];
		$recurrence['recurrence'] = 1;
		$recurrence['recurrence_interval'] = $recurrence_raw['recurrence_interval'];
		$recurrence['recurrence_freq'] = $recurrence_raw['recurrence_freq'];
		$recurrence['recurrence_byday'] = $recurrence_raw['recurrence_byday'];
		$recurrence['recurrence_byweekno'] = $recurrence_raw['recurrence_byweekno'];
		if ($recurrence_raw['event_contactperson_id'] != '') $recurrence['event_contactperson_id'] = $recurrence_raw['event_contactperson_id'];
		$result = $wpdb->insert($table_name, $recurrence);
		//Then change the id of all the events with recurrence_id
		if($result == 1){
			$wpdb->query("UPDATE {$table_name} SET recurrence_id='{$wpdb->insert_id}' WHERE recurrence_id='{$recurrence_id}'");
		}else{
			//FIXME Better fallback in case of bad install 
			die( __('We could not mirgrate old recurrence data over. Please try again, or contact the developers to let them know of this bug.', 'dbem'));
		} 
	}                                                                                        
	
	// migrating locations
	$bookings = $wpdb->get_results('SELECT * FROM '.$wpdb->prefix.OLD_LOCATIONS_TBNAME,ARRAY_A)  ;
	foreach($locations as $l) {                
		$wpdb->insert($wpdb->prefix.LOCATIONS_TBNAME, $l);
	}
	
	// migrating people
	$people = $wpdb->get_results('SELECT * FROM '.$wpdb->prefix.OLD_PEOPLE_TBNAME,ARRAY_A)  ;
	foreach($people as $p) {                
		$wpdb->insert($wpdb->prefix.PEOPLE_TBNAME, $p);
	}
	 
	// migrating bookings
	$bookings = $wpdb->get_results('SELECT * FROM '.$wpdb->prefix.OLD_BOOKINGS_TBNAME,ARRAY_A)  ;
	foreach($bookings as $b) {                
		$wpdb->insert($wpdb->prefix.BOOKINGS_TBNAME, $b);
	}  
	// migrating categories
	$categories = $wpdb->get_results('SELECT * FROM '.$wpdb->prefix.OLD_DBEM_CATEGORIES_TBNAME,ARRAY_A)  ;
	foreach($categories as $c) {                
		$wpdb->insert($wpdb->prefix.DBEM_CATEGORIES_TBNAME, $c);
	}  
	   

} 
?>