<?php
function em_install() {
	if( EM_VERSION > get_option('dbem_version') ){
	 	// Creates the events table if necessary
		em_create_events_table(); 
		em_create_locations_table();
	  	em_create_bookings_table();
	  	em_create_people_table();
		em_create_categories_table();
		em_add_options();
		
		//Migrate?
		$old_version = get_option('dbem_version');
		if( $old_version < 2.3 && $old_version != '' ){
			em_migrate_to_new_tables();
			em_import_verify();
		}
		//Upate Version	
	  	update_option('dbem_version', EM_VERSION); 
	  	
		// wp-content must be chmodded 777. Maybe just wp-content.
		if(!file_exists("../".IMAGE_UPLOAD_DIR))
			mkdir("../".IMAGE_UPLOAD_DIR, 0777); //do we need to 777 it? it'll be owner apache anyway, like normal uploads
		
		em_create_events_page(); 
	}
}

function em_create_events_table() {
	global  $wpdb, $user_level, $user_ID;
	get_currentuserinfo();
	require_once(ABSPATH . 'wp-admin/includes/upgrade.php'); 
	
	$table_name = $wpdb->prefix.EVENTS_TBNAME; 
	$sql = "CREATE TABLE ".$table_name." (
		event_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		event_author bigint(20) unsigned DEFAULT NULL,
		event_name tinytext NOT NULL,
		event_start_time time NOT NULL,
		event_end_time time NOT NULL,
		event_start_date date NOT NULL,
		event_end_date date NULL, 
		event_notes text NULL DEFAULT NULL,
		event_rsvp bool NOT NULL DEFAULT 0,
		event_seats int(5),
		event_contactperson_id bigint(20) unsigned NULL,  
		location_id bigint(20) unsigned NOT NULL,
		recurrence_id bigint(20) unsigned NULL,
  		event_category_id bigint(20) unsigned NULL DEFAULT NULL,
  		event_attributes text NULL,
		recurrence bool NOT NULL DEFAULT 0,
		recurrence_interval int(4) NULL DEFAULT NULL,
		recurrence_freq tinytext NULL DEFAULT NULL,
		recurrence_byday tinytext NULL DEFAULT NULL,
		recurrence_byweekno int(4) NULL DEFAULT NULL,  		
		UNIQUE KEY (event_id)
		) DEFAULT CHARSET=utf8 ;";
	
	$old_table_name = $wpdb->prefix.OLD_EVENTS_TBNAME; 

	if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name && $wpdb->get_var("SHOW TABLES LIKE '$old_table_name'") != $old_table_name) {
		dbDelta($sql);		
		//Add default events
		$in_one_week = date('Y-m-d', time() + 60*60*24*7);
		$in_four_weeks = date('Y-m-d', time() + 60*60*24*7*4); 
		$in_one_year = date('Y-m-d', time() + 60*60*24*7*365);
		
		$wpdb->query("INSERT INTO ".$table_name." (event_name, event_start_date, event_start_time, event_end_time, location_id) VALUES ('Orality in James Joyce Conference', '$in_one_week', '16:00:00', '18:00:00', 1)");
		$wpdb->query("INSERT INTO ".$table_name." (event_name, event_start_date, event_start_time, event_end_time, location_id)	VALUES ('Traditional music session', '$in_four_weeks', '20:00:00', '22:00:00', 2)");
		$wpdb->query("INSERT INTO ".$table_name." (event_name, event_start_date, event_start_time, event_end_time, location_id) VALUES ('6 Nations, Italy VS Ireland', '$in_one_year','22:00:00', '24:00:00', 3)");
	}else{
		dbDelta($sql);
	}
}

function em_create_locations_table() {
	
	global  $wpdb, $user_level;
	$table_name = $wpdb->prefix.LOCATIONS_TBNAME;

	// Creating the events table
	$sql = "CREATE TABLE ".$table_name." (
		location_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		location_name tinytext NOT NULL,
		location_address tinytext NOT NULL,
		location_town tinytext NOT NULL,
		location_province tinytext,
		location_latitude float DEFAULT NULL,
		location_longitude float DEFAULT NULL,
		location_description text DEFAULT NULL,
		UNIQUE KEY (location_id)
		) DEFAULT CHARSET=utf8 ;";
		
	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	
	$old_table_name = $wpdb->prefix.OLD_LOCATIONS_TBNAME;     

	if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name && $wpdb->get_var("SHOW TABLES LIKE '$old_table_name'") != $old_table_name) {
		dbDelta($sql);		
		//Add default values
		$wpdb->query("INSERT INTO ".$table_name." (location_name, location_address, location_town, location_latitude, location_longitude) VALUES ('Arts Millenium Building', 'Newcastle Road','Galway', 53.275, -9.06532)");
		$wpdb->query("INSERT INTO ".$table_name." (location_name, location_address, location_town, location_latitude, location_longitude) VALUES ('The Crane Bar', '2, Sea Road','Galway', 53.2692, -9.06151)");
		$wpdb->query("INSERT INTO ".$table_name." (location_name, location_address, location_town, location_latitude, location_longitude) VALUES ('Taaffes Bar', '19 Shop Street','Galway', 53.2725, -9.05321)");
	}else{
		dbDelta($sql);
	}
}

function em_create_bookings_table() {
	
	global  $wpdb, $user_level;
	$table_name = $wpdb->prefix.BOOKINGS_TBNAME;
		
	$sql = "CREATE TABLE ".$table_name." (
		booking_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		event_id bigint(20) unsigned NOT NULL,
		person_id bigint(20) unsigned NOT NULL, 
		booking_seats int(5) NOT NULL,
		booking_comment text DEFAULT NULL,
		booking_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
		UNIQUE KEY  (booking_id)
		) DEFAULT CHARSET=utf8 ;";
	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	dbDelta($sql);
}

function em_create_people_table() {
	
	global  $wpdb, $user_level;
	$table_name = $wpdb->prefix.PEOPLE_TBNAME;

	$sql = "CREATE TABLE ".$table_name." (
		person_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		person_name tinytext NOT NULL, 
		person_email tinytext NOT NULL,
		person_phone tinytext NOT NULL,
		UNIQUE KEY (person_id)
		) DEFAULT CHARSET=utf8 ;";
	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	dbDelta($sql);
} 

//Add the categories table
function em_create_categories_table() {
	
	global  $wpdb, $user_level;
	$table_name = $wpdb->prefix.DBEM_CATEGORIES_TBNAME;

	// Creating the events table
	$sql = "CREATE TABLE ".$table_name." (
		category_id bigint(20) unsigned NOT NULL auto_increment,
		category_name tinytext NOT NULL,
		PRIMARY KEY  (category_id)
		) DEFAULT CHARSET=utf8 ;";
	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	
	$old_table_name = $wpdb->prefix.OLD_CATEGORIES_TBNAME;     
	
	if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name && $wpdb->get_var("SHOW TABLES LIKE '$old_table_name'") != $old_table_name) {
		dbDelta($sql);
		$wpdb->insert( $table_name, array('category_name'=>__('Uncategorized', 'dbem')), array('%s') );
	}else{
		dbDelta($sql);
	}
}


function em_add_options() {
	$contact_person_email_body_localizable = __("#_RESPNAME (#_RESPEMAIL) will attend #_NAME on #m #d, #Y. He wants to reserve #_SPACES spaces.<br/> Now there are #_RESERVEDSPACES spaces reserved, #_AVAILABLESPACES are still available.<br/>Yours faithfully,<br/>Events Manager",'dbem') ;
	$respondent_email_body_localizable = __("Dear #_RESPNAME, <br/>you have successfully reserved #_SPACES space/spaces for #_NAME.<br/>Yours faithfully,<br/> #_CONTACTPERSON",'dbem');
	
	$dbem_options = array('dbem_event_list_item_format' => DEFAULT_EVENT_LIST_ITEM_FORMAT,
	'dbem_display_calendar_in_events_page' => 0,
	'dbem_single_event_format' => DEFAULT_SINGLE_EVENT_FORMAT,
	'dbem_event_page_title_format' => DEFAULT_EVENT_PAGE_TITLE_FORMAT,
	'dbem_list_events_page' => 1,   
	'dbem_events_page_title' => DEFAULT_EVENTS_PAGE_TITLE,
	'dbem_no_events_message' => __('No events','dbem'),
	'dbem_location_page_title_format' => DEFAULT_LOCATION_PAGE_TITLE_FORMAT,
	'dbem_location_baloon_format' => DEFAULT_LOCATION_BALOON_FORMAT,
	'dbem_location_event_list_item_format' => DEFAULT_LOCATION_EVENT_LIST_ITEM_FORMAT,
	'dbem_location_no_events_message' => DEFAULT_LOCATION_NO_EVENTS_MESSAGE,
	'dbem_single_location_format' => DEFAULT_SINGLE_LOCATION_FORMAT,
	'dbem_map_text_format' => DEFAULT_MAP_TEXT_FORMAT,
	'dbem_rss_main_title' => get_bloginfo('title')." - ".__('Events'),
	'dbem_rss_main_description' => get_bloginfo('description')." - ".__('Events'),
	'dbem_rss_description_format' => DEFAULT_RSS_DESCRIPTION_FORMAT,
	'dbem_rss_title_format' => DEFAULT_RSS_TITLE_FORMAT,
	'dbem_gmap_is_active'=> 1,
	'dbem_default_contact_person' => 1,
	'dbem_rsvp_mail_notify_is_active' => 0 ,
	'dbem_contactperson_email_body' => __(str_replace("<br/>", "\n\r", $contact_person_email_body_localizable)),        
	'dbem_respondent_email_body' => __(str_replace("<br>", "\n\r", $respondent_email_body_localizable)),
	'dbem_rsvp_mail_port' => 465,
	'dbem_smtp_host' => 'localhost',
	'dbem_mail_sender_name' => '',
	'dbem_rsvp_mail_send_method' => 'smtp',  
	'dbem_rsvp_mail_SMTPAuth' => 1,
	'dbem_image_max_width' => DEFAULT_IMAGE_MAX_WIDTH,
	'dbem_image_max_height' => DEFAULT_IMAGE_MAX_HEIGHT,
	'dbem_image_max_size' => DEFAULT_IMAGE_MAX_SIZE,
	'dbem_list_date_title' => DEFAULT_LIST_DATE_TITLE,
	'dbem_full_calendar_event_format' => DEFAULT_FULL_CALENDAR_EVENT_FORMAT,
	'dbem_small_calendar_event_title_format' => DEFAULT_SMALL_CALENDAR_EVENT_TITLE_FORMAT,
	'dbem_small_calendar_event_title_separator' => DEFAULT_SMALL_CALENDAR_EVENT_TITLE_SEPARATOR, 
	'dbem_hello_to_user' => 1,
	'dbem_use_select_for_locations' => DEFAULT_USE_SELECT_FOR_LOCATIONS,
	'dbem_attributes_enabled', DEFAULT_ATTRIBUTES_ENABLED,
	'dbem_recurrence_enabled', DEFAULT_RECURRENCE_ENABLED,
	'dbem_rsvp_enabled', DEFAULT_RSVP_ENABLED,
	'dbem_categories_enabled', DEFAULT_CATEGORIES_ENABLED);
	
	foreach($dbem_options as $key => $value){
		add_option($key, $value);
	}
		
}     

function em_create_events_page(){
	global $wpdb,$current_user;	
	if( get_option('dbem_events_page') == '' && get_option('dbem_dismiss_events_page') != 1 && !is_object( get_page( get_option('dbem_events_page') )) ){
		$post_data = array(
			'post_status' => 'publish', 
			'post_type' => 'page',
			'ping_status' => get_option('default_ping_status'),
			'post_content' => 'CONTENTS', 
			'post_excerpt' => 'CONTENTS',
			'post_title' => DEFAULT_EVENT_PAGE_NAME
		);
		$post_id = wp_insert_post($post_data, false);
	   	if( $post_id > 0 ){
	   		update_option('dbem_events_page', $post_id); 			
	   	}
	}
}   

// migrate old dbem tables to new em ones
function em_migrate_to_new_tables(){
	global $wpdb, $current_user;
	get_currentuserinfo();                       
	
	// migrating events
	$events_required = array('event_id', 'event_name','event_start_time','event_end_time','event_start_date','event_rsvp','location_id','recurrence');
	$events = $wpdb->get_results('SELECT * FROM '.$wpdb->prefix.OLD_EVENTS_TBNAME,ARRAY_A)  ;
	$events_values = array();
	$events_keys = array_keys($events[0]); 
	foreach($events as $event) {
		foreach($event as $key => $value){
			if($value == '' && !in_array($key,$events_required)){ $event[$key] = 'NULL'; }
			elseif ( $value == '-1' && !in_array($key,$events_required) ) { $event[$key] = 'NULL'; } 
			else { $event[$key] = "'".$wpdb->escape($event[$key])."'"; }
		}
		$events_values[] = "\n".'('. implode(', ', $event).')';
	}
	if( count($events_values) > 0 ){
		$events_sql = "INSERT INTO " . $wpdb->prefix.EVENTS_TBNAME . 
			"(`" . implode('` ,`', $events_keys) . "`) VALUES".
			implode(', ', $events_values);
		$wpdb->query($events_sql);
	}
	
	// inserting recurrences into events                 
	$table_name = $wpdb->prefix.EVENTS_TBNAME;  
	$results = $wpdb->get_results('SELECT * FROM '.$wpdb->prefix.RECURRENCE_TBNAME, ARRAY_A);
	foreach($results as $recurrence_raw){       
		
		//Save copy of recurrence_id
		$recurrence_id = $recurrence_raw['recurrence_id'];
		//First insert the event into events table
		$recurrence = array( //Save new array with correct indexes
			'event_author' => $current_user->ID,                  
			'event_name' => $recurrence_raw['recurrence_name'],
			'event_start_date' => $recurrence_raw['recurrence_start_date'],
			'event_end_date' => $recurrence_raw['recurrence_end_date'],
			'event_start_time' => $recurrence_raw['recurrence_start_time'],
			'event_end_time' => $recurrence_raw['recurrence_end_time'],
			'event_notes' => $recurrence_raw['recurrence_notes'],
			'location_id' => $recurrence_raw['location_id'],
			'recurrence' => 1,
			'recurrence_interval' => $recurrence_raw['recurrence_interval'],
			'recurrence_freq' => $recurrence_raw['recurrence_freq'],
	   		'recurrence_byday' => $recurrence_raw['recurrence_byday'],
	   		'recurrence_byweekno' => $recurrence_raw['recurrence_byweekno']
		);
		$result = $wpdb->insert($table_name, $recurrence, array('%d','%s','%s','%s','%s','%s','%s','%d','%d','%d','%d','%d','%d'));
		//Then change the id of all the events with recurrence_id
		if($result == 1){    
			$wpdb->query("UPDATE {$table_name} SET recurrence_id='{$wpdb->insert_id}' WHERE recurrence_id='{$recurrence_id}'");
		}else{
			//FIXME Better fallback in case of bad install 
			_e('We could not mirgrate old recurrence data over. DONT WORRY! You can just delete the current plugin, and re-install the previous 2.2.2 version and you wont lose any of your data. Either way, please contact the developers to let them know of this bug.', 'dbem');
		} 
	}                                                                                        
	
	// migrating locations
	$locations_required = array('location_id', 'location_name', 'location_address', 'location_town');
	$locations = $wpdb->get_results('SELECT * FROM '.$wpdb->prefix.OLD_LOCATIONS_TBNAME,ARRAY_A)  ;
	$locations_values = array();
	$locations_keys = array_keys($locations[0]); 
	foreach($locations as $location) {
		foreach($location as $key => $value){
			if($value == '' && !in_array($key, $locations_required)){ $location[$key] = 'NULL'; }
			elseif ( $value == '-1' && !in_array($key, $locations_required) ) { $location[$key] = 'NULL'; } 
			else { $location[$key] = "'".$wpdb->escape($location[$key])."'"; }
		}
		$locations_values[] = "\n".'('. implode(', ', $location).')';
	}
	if( count($locations_values) > 0 ){
		$locations_sql = "INSERT INTO " . $wpdb->prefix.LOCATIONS_TBNAME . 
			"(`" . implode('` ,`', $locations_keys) . "`) VALUES".
			implode(', ', $locations_values);
		$wpdb->query($locations_sql);
	}
	
	// migrating people
	$people = $wpdb->get_results('SELECT * FROM '.$wpdb->prefix.OLD_PEOPLE_TBNAME,ARRAY_A)  ;
	$people_values = array();
	$people_keys = array_keys($people[0]); 
	foreach($people as $person) {
		foreach($person as $key => $value){
			$person[$key] = "'".$wpdb->escape($person[$key])."'";
		}
		$people_values[] = "\n".'('. implode(', ', $person).')';
	}
	if( count($people_values) > 0 ){
		$people_sql = "INSERT INTO " . $wpdb->prefix.PEOPLE_TBNAME . 
			"(`" . implode('` ,`', $people_keys) . "`) VALUES".
			implode(', ', $people_values);
		$wpdb->query($people_sql);
	}
	 
	// migrating bookings
	$bookings = $wpdb->get_results('SELECT * FROM '.$wpdb->prefix.OLD_BOOKINGS_TBNAME,ARRAY_A)  ;
	$bookings_values = array();
	$bookings_keys = array_keys($bookings[0]); 
	foreach($bookings as $booking) {
		foreach($booking as $key => $value){
			if($value == '' && $key == 'booking_comment'){ $booking[$key] = 'NULL'; }
			elseif ( $value == '-1' ) { $booking[$key] = '0'; } 
			else { $booking[$key] = "'".$wpdb->escape($booking[$key])."'"; }
		}
		$bookings_values[] = "\n".'('. implode(', ', $booking).')';
	}
	if( count($bookings_values) > 0 ){
		$bookings_sql = "INSERT INTO " . $wpdb->prefix.BOOKINGS_TBNAME . 
			"(`" . implode('` ,`', $bookings_keys) . "`) VALUES".
			implode(', ', $bookings_values);
		$wpdb->query($bookings_sql);
	}
	 
	// migrating categories
	$categories = $wpdb->get_results('SELECT * FROM '.$wpdb->prefix.OLD_CATEGORIES_TBNAME,ARRAY_A)  ;
	foreach($categories as $c) {                
		$wpdb->insert($wpdb->prefix.DBEM_CATEGORIES_TBNAME, $c);
	} 
	 
}

function em_reimport(){
	//Check for reimport request
	global $wpdb;
	if($_GET['em_reimport'] == 1 ){
		check_admin_referer( 'em_reimport' );
		$p = $wpdb->prefix;
		$table_bookings = $p.BOOKINGS_TBNAME;
		$table_categories = $p.DBEM_CATEGORIES_TBNAME;
		$table_events = $p.EVENTS_TBNAME;
		$table_locations = $p.LOCATIONS_TBNAME;
		$table_people = $p.PEOPLE_TBNAME;
		$wpdb->query('DROP TABLE '.$table_bookings.', '.$table_categories.', '.$table_events.', '.$table_locations.', '.$table_people.';');
		update_option('dbem_version','2');
		em_install();
		return em_import_verify();
	}
}
add_action('admin_init', 'em_reimport');

/**
 * If importing from 2.x to 3.x, this function will be called to verify the import went well.
 * @return string|string
 */
function em_import_verify(){
	global $wpdb;
	$p = $wpdb->prefix;
	//Now go through each table and compare row counts, if all match (events is old recurrences + events, then we're fine
	$results[] = ( $wpdb->get_var("SELECT COUNT(*) FROM ".$p.BOOKINGS_TBNAME.";") == $wpdb->get_var("SELECT COUNT(*) FROM ".$p.OLD_BOOKINGS_TBNAME.";") );
	$results[] = ( $wpdb->get_var("SELECT COUNT(*) FROM ".$p.DBEM_CATEGORIES_TBNAME.";") == $wpdb->get_var("SELECT COUNT(*) FROM ".$p.OLD_CATEGORIES_TBNAME.";") );
	$results[] = ( $wpdb->get_var("SELECT COUNT(*) FROM ".$p.EVENTS_TBNAME.";") == $wpdb->get_var("SELECT COUNT(*) FROM ".$p.OLD_EVENTS_TBNAME.";") + $wpdb->get_var("SELECT COUNT(*) FROM ".$p.OLD_RECURRENCE_TBNAME.";") );
	$results[] = ( $wpdb->get_var("SELECT COUNT(*) FROM ".$p.LOCATIONS_TBNAME.";") == $wpdb->get_var("SELECT COUNT(*) FROM ".$p.OLD_LOCATIONS_TBNAME.";") );
	$results[] = ( $wpdb->get_var("SELECT COUNT(*) FROM ".$p.PEOPLE_TBNAME.";") == $wpdb->get_var("SELECT COUNT(*) FROM ".$p.OLD_PEOPLE_TBNAME.";") );
	if( in_array(false, $results) ){
		update_option( 'dbem_import_fail', 1 );
		return false;
	}else{
		update_option( 'dbem_import_fail', 0 );
		add_action ( 'admin_notices', 'em_import_message_success' );
		return true;
	}	
}

/**
 * Gets called if re-import was successful. 
 */
function em_import_message_success(){
	?>
	<div id="em_page_error" class="updated">
		<p><?php _e('Events Manager successfully imported your events, please check your records to verify.','dbem')?></p>
	</div>
	<?php
}

/*
 * If import failed, a persistant message will show unless ignored.
 */		
function em_import_message_fail(){
	if( $_GET['em_dismiss_import'] == '1' ){
		update_option('dbem_import_fail', 0);
	}	
	if( get_option('dbem_import_fail') == 1 ){
		$dismiss_link_joiner = ( count($_GET) > 0 ) ? '&amp;':'?';
		?>
			<div id="em_page_error" class="error">
				<p><?php printf( __('Something has gone wrong when importing your old event. See the <a href="%s">support page</a> for more information. <a href="%s">Dismiss this message</a>','dbem'), get_bloginfo('wpurl').'/wp-admin/admin.php?page=events-manager-support', $_SERVER['REQUEST_URI'].$dismiss_link_joiner.'em_dismiss_import=1'); ?></p>
			</div>
		<?php
	}
}
add_action ( 'admin_notices', 'em_import_message_fail' );
?>