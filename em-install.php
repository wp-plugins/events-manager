<?php

function em_install() {
	global $wp_rewrite;
   	$wp_rewrite->flush_rules();
	$old_version = get_option('dbem_version');
	//Won't upgrade 2 anymore, let 3 do that and we worry about 3.
   	if( $old_version != '' && $old_version < 4.1 ){
		function em_update_required_notification(){ 
			global $EM_Booking; 
			?><div class="error"><p><strong>Events Manager upgrade not complete, please upgrade to the version 4.303 or higher first from <a href="http://wordpress.org/extend/plugins/events-manager/download/">here</a> first before upgrading to this version.</p></div><?php
		}
		add_action ( 'admin_notices', 'em_update_required_notification' );
		return; 
   	}
	if( EM_VERSION > $old_version || $old_version == '' ){
	 	// Creates the events table if necessary
		em_create_events_table(); 
		em_create_events_meta_table();
		em_create_locations_table();
	  	em_create_bookings_table();
		em_create_tickets_table();
		em_create_tickets_bookings_table();
		em_set_capabilities();
		em_add_options();
		
		//New install, or Migrate?
		if( $old_version < 5 && !empty($old_version) ){
			em_migrate_v4();
		}elseif( empty($old_version) ){
			update_option('dbem_hello_to_user',1);
		}
		//Upate Version	
	  	update_option('dbem_version', EM_VERSION); 
	}
}

/**
 * Magic function that takes a table name and cleans all non-unique keys not present in the $clean_keys array. if no array is supplied, all but the primary key is removed.
 * @param string $table_name
 * @param array $clean_keys
 */
function em_sort_out_table_nu_keys($table_name, $clean_keys = array()){
	global $wpdb;
	//sort out the keys
	$new_keys = $clean_keys;
	$table_key_changes = array();
	$table_keys = $wpdb->get_results("SHOW KEYS FROM $table_name WHERE Key_name != 'PRIMARY'", ARRAY_A);
	foreach($table_keys as $table_key_row){
		if( !in_array($table_key_row['Key_name'], $clean_keys) ){
			$table_key_changes[] = "ALTER TABLE $table_name DROP INDEX ".$table_key_row['Key_name'];
		}elseif( in_array($table_key_row['Key_name'], $clean_keys) ){
			foreach($clean_keys as $key => $clean_key){
				if($table_key_row['Key_name'] == $clean_key){
					unset($new_keys[$key]);
				}
			}
		}
	}
	//delete duplicates
	foreach($table_key_changes as $sql){
		$wpdb->query($sql);
	}
	//add new keys
	foreach($new_keys as $key){
		$wpdb->query("ALTER TABLE $table_name ADD INDEX ($key)");
	}
}

function em_create_events_table() {
	global  $wpdb, $user_level, $user_ID;
	get_currentuserinfo();
	require_once(ABSPATH . 'wp-admin/includes/upgrade.php'); 
	
	$table_name = EM_EVENTS_TABLE; 
	$sql = "CREATE TABLE ".$table_name." (
		event_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		post_id bigint(20) unsigned NOT NULL,
		event_slug VARCHAR( 200 ) NULL DEFAULT NULL,
		event_owner bigint(20) unsigned DEFAULT NULL,
		event_status int(1) NULL DEFAULT NULL, 
		event_name text NULL DEFAULT NULL,
		event_start_time time NULL DEFAULT NULL,
		event_end_time time NULL DEFAULT NULL,
		event_start_date date NULL DEFAULT NULL,
		event_end_date date NULL DEFAULT NULL, 
		post_content longtext NULL DEFAULT NULL,
		event_rsvp bool NOT NULL DEFAULT 0,
		event_spaces int(5) NULL DEFAULT 0,
		location_id bigint(20) unsigned NULL DEFAULT NULL,
		recurrence_id bigint(20) unsigned NULL DEFAULT NULL,
  		event_category_id bigint(20) unsigned NULL DEFAULT NULL,
  		event_attributes text NULL DEFAULT NULL,
  		event_date_created datetime NULL DEFAULT NULL,
  		event_date_modified datetime NULL DEFAULT NULL,
		recurrence bool NOT NULL DEFAULT 0,
		recurrence_interval int(4) NULL DEFAULT NULL,
		recurrence_freq tinytext NULL DEFAULT NULL,
		recurrence_byday tinytext NULL DEFAULT NULL,
		recurrence_byweekno int(4) NULL DEFAULT NULL,	
		recurrence_days int(4) NULL DEFAULT NULL,	
		blog_id bigint(20) unsigned NULL DEFAULT NULL,
		group_id bigint(20) unsigned NULL DEFAULT NULL,
		PRIMARY KEY  (event_id)
		) DEFAULT CHARSET=utf8 ;";
	
	$old_table_name = EM_OLD_EVENTS_TABLE; 

	if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name && $wpdb->get_var("SHOW TABLES LIKE '$old_table_name'") != $old_table_name) {
		dbDelta($sql);		
		//Add default events
		$in_one_week = date('Y-m-d', time() + 60*60*24*7);
		$in_four_weeks = date('Y-m-d', time() + 60*60*24*7*4); 
		$in_one_year = date('Y-m-d', time() + 60*60*24*365);
		
		$wpdb->query("INSERT INTO ".$table_name." (event_name, event_start_date, event_start_time, event_end_time, location_id, event_slug, event_owner, event_status) VALUES ('Orality in James Joyce Conference', '$in_one_week', '16:00:00', '18:00:00', 1, 'oralty-in-james-joyce-conference','".get_current_user_id()."',1)");
		$wpdb->query("INSERT INTO ".$table_name." (event_name, event_start_date, event_start_time, event_end_time, location_id, event_slug, event_owner, event_status)	VALUES ('Traditional music session', '$in_four_weeks', '20:00:00', '22:00:00', 2, 'traditional-music-session','".get_current_user_id()."',1)");
		$wpdb->query("INSERT INTO ".$table_name." (event_name, event_start_date, event_start_time, event_end_time, location_id, event_slug, event_owner, event_status) VALUES ('6 Nations, Italy VS Ireland', '$in_one_year','22:00:00', '24:00:00', 3, '6-nations-italy-vs-ireland','".get_current_user_id()."',1)");
	}else{
		if( get_option('dbem_version') < 4.939 ){
			//if updating from version 4 (4.934 is beta v5) then set all statuses to 1 since it's new
			$wpdb->query("ALTER TABLE $table_name CHANGE event_notes post_content longtext NULL DEFAULT NULL");
			$wpdb->query("ALTER TABLE $table_name CHANGE event_name event_name text NULL DEFAULT NULL");
			$wpdb->query("ALTER TABLE $table_name CHANGE location_id location_id bigint(20) unsigned NULL DEFAULT NULL");
			$wpdb->query("ALTER TABLE $table_name CHANGE recurrence_id recurrence_id bigint(20) unsigned NULL DEFAULT NULL");
			$wpdb->query("ALTER TABLE $table_name CHANGE event_start_time event_start_time time NULL DEFAULT NULL");
			$wpdb->query("ALTER TABLE $table_name CHANGE event_end_time event_end_time time NULL DEFAULT NULL");
			$wpdb->query("ALTER TABLE $table_name CHANGE event_start_date event_start_date date NULL DEFAULT NULL");
		}
		dbDelta($sql);
	}
	em_sort_out_table_nu_keys($table_name, array('event_status','post_id','blog_id','group_id'));
}

function em_create_events_meta_table(){
	global  $wpdb, $user_level;
	$table_name = EM_META_TABLE;

	// Creating the events table
	$sql = "CREATE TABLE ".$table_name." (
		meta_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		object_id bigint(20) unsigned NOT NULL,
		meta_key varchar(255) DEFAULT NULL,
		meta_value longtext,
		meta_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
		PRIMARY KEY  (meta_id)
		) DEFAULT CHARSET=utf8 ";
		
	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	
	$old_table_name = EM_OLD_LOCATIONS_TABLE;     
	dbDelta($sql);	
	em_sort_out_table_nu_keys($table_name, array('object_id','meta_key'));
}

function em_create_locations_table() {
	
	global  $wpdb, $user_level;
	$table_name = EM_LOCATIONS_TABLE;

	// Creating the events table
	$sql = "CREATE TABLE ".$table_name." (
		location_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		post_id bigint(20) unsigned NOT NULL,
		blog_id bigint(20) unsigned NULL DEFAULT NULL,
		location_slug VARCHAR( 200 ) NULL DEFAULT NULL,
		location_name text NULL DEFAULT NULL,
		location_owner bigint(20) unsigned NOT NULL DEFAULT 0,
		location_address VARCHAR( 200 ) NULL DEFAULT NULL,
		location_town VARCHAR( 200 ) NULL DEFAULT NULL,
		location_state VARCHAR( 200 ) NULL DEFAULT NULL,
		location_postcode VARCHAR( 10 ) NULL DEFAULT NULL,
		location_region VARCHAR( 200 ) NULL DEFAULT NULL,
		location_country CHAR( 2 ) NULL DEFAULT NULL,
		location_latitude float NULL DEFAULT NULL,
		location_longitude float NULL DEFAULT NULL,
		post_content longtext NULL DEFAULT NULL,
		location_status int(1) NULL DEFAULT NULL
		PRIMARY KEY  (location_id)
		) DEFAULT CHARSET=utf8 ;";
		
	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	
	$old_table_name = EM_OLD_LOCATIONS_TABLE; //for 3.0 
	if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name && $wpdb->get_var("SHOW TABLES LIKE '$old_table_name'") != $old_table_name) {
		dbDelta($sql);		
		//Add default values
		$wpdb->query("INSERT INTO ".$table_name." (location_name, location_address, location_town, location_state, location_country, location_latitude, location_longitude, location_slug, location_owner) VALUES ('Arts Millenium Building', 'Newcastle Road','Galway','Galway','IE', 53.275, -9.06532, 'arts-millenium-building','".get_current_user_id()."')");
		$wpdb->query("INSERT INTO ".$table_name." (location_name, location_address, location_town, location_state, location_country, location_latitude, location_longitude, location_slug, location_owner) VALUES ('The Crane Bar', '2, Sea Road','Galway','Galway','IE', 53.2692, -9.06151, 'the-crane-bar','".get_current_user_id()."')");
		$wpdb->query("INSERT INTO ".$table_name." (location_name, location_address, location_town, location_state, location_country, location_latitude, location_longitude, location_slug, location_owner) VALUES ('Taaffes Bar', '19 Shop Street','Galway','Galway','IE', 53.2725, -9.05321, 'taffes-bar','".get_current_user_id()."')");
	}else{
		if( get_option('dbem_version') < 4.938 ){
			$wpdb->query("ALTER TABLE $table_name CHANGE location_description post_content longtext NULL DEFAULT NULL");
		}
		dbDelta($sql);
		if( get_option('dbem_version') < 4.93 ){
			//if updating from version 4 (4.93 is beta v5) then set all statuses to 1 since it's new
			$wpdb->query("UPDATE ".$table_name." SET location_status=1");
		}
	}
	em_sort_out_table_nu_keys($table_name, array('location_state','location_region','location_country','post_id','blog_id'));
}

function em_create_bookings_table() {
	
	global  $wpdb, $user_level;
	$table_name = EM_BOOKINGS_TABLE;
		
	$sql = "CREATE TABLE ".$table_name." (
		booking_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		event_id bigint(20) unsigned NOT NULL,
		person_id bigint(20) unsigned NOT NULL,
		booking_spaces int(5) NOT NULL,
		booking_comment text DEFAULT NULL,
		booking_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
		booking_status bool NOT NULL DEFAULT 1,
 		booking_price decimal(10,2) unsigned NOT NULL DEFAULT 0,
		booking_meta LONGTEXT NULL,
		PRIMARY KEY  (booking_id)
		) DEFAULT CHARSET=utf8 ;";
	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	dbDelta($sql);
	em_sort_out_table_nu_keys($table_name, array('event_id'));
}


//Add the categories table
function em_create_tickets_table() {
	
	global  $wpdb, $user_level;
	$table_name = EM_TICKETS_TABLE;

	// Creating the events table
	$sql = "CREATE TABLE {$table_name} (
		ticket_id BIGINT( 20 ) UNSIGNED NOT NULL AUTO_INCREMENT,
		event_id BIGINT( 20 ) UNSIGNED NOT NULL ,
		ticket_name TINYTEXT NOT NULL ,
		ticket_description TEXT NULL ,
		ticket_price DECIMAL( 10 , 2 ) NULL ,
		ticket_start DATETIME NULL ,
		ticket_end DATETIME NULL ,
		ticket_min INT( 10 ) NULL ,
		ticket_max INT( 10 ) NULL ,
		ticket_spaces INT NULL ,
		PRIMARY KEY  (ticket_id)
		) DEFAULT CHARSET=utf8 ;";
	
	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	dbDelta($sql);
	em_sort_out_table_nu_keys($table_name, array('event_id'));
}

//Add the categories table
function em_create_tickets_bookings_table() {	
	global  $wpdb, $user_level;
	$table_name = EM_TICKETS_BOOKINGS_TABLE;

	// Creating the events table
	$sql = "CREATE TABLE {$table_name} (
		  ticket_booking_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		  booking_id bigint(20) unsigned NOT NULL,
		  ticket_id bigint(20) unsigned NOT NULL,
		  ticket_booking_spaces int(6) NOT NULL,
		  ticket_booking_price decimal(10,2) NOT NULL,
		  PRIMARY KEY  (ticket_booking_id)
		) DEFAULT CHARSET=utf8 ;";
	
	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	dbDelta($sql);
	em_sort_out_table_nu_keys($table_name, array('booking_id','ticket_id'));
}

function em_add_options() {
	$contact_person_email_body_localizable = __("#_BOOKINGNAME (#_BOOKINGEMAIL) will attend #_NAME on #F #j, #Y. He wants to reserve #_BOOKINGSPACES spaces.<br/> Now there are #_BOOKEDSPACES spaces reserved, #_AVAILABLESPACES are still available.<br/>Yours faithfully,<br/>Events Manager - http://wp-events-plugin.com",'dbem').__('<br/><br/>-------------------------------<br/>Powered by Events Manager - http://wp-events-plugin.com','dbem');
	$contact_person_email_cancelled_body_localizable = __("#_BOOKINGNAME (#_BOOKINGEMAIL) cancelled his booking at #_NAME on #F #j, #Y. He wanted to reserve #_BOOKINGSPACES spaces.<br/> Now there are #_BOOKEDSPACES spaces reserved, #_AVAILABLESPACES are still available.<br/>Yours faithfully,<br/>Events Manager - http://wp-events-plugin.com",'dbem').__('<br/><br/>-------------------------------<br/>Powered by Events Manager - http://wp-events-plugin.com','dbem');
	$respondent_email_body_localizable = __("Dear #_BOOKINGNAME, <br/>you have successfully reserved #_BOOKINGSPACES space/spaces for #_NAME.<br/>Yours faithfully,<br/>#_CONTACTNAME",'dbem').__('<br/><br/>-------------------------------<br/>Powered by Events Manager - http://wp-events-plugin.com','dbem');
	$respondent_email_pending_body_localizable = __("Dear #_BOOKINGNAME, <br/>You have requested #_BOOKINGSPACES space/spaces for #_NAME.<br/>Your booking is currently pending approval by our administrators. Once approved you will receive an automatic confirmation.<br/>Yours faithfully,<br/>#_CONTACTNAME",'dbem').__('<br/><br/>-------------------------------<br/>Powered by Events Manager - http://wp-events-plugin.com','dbem');
	$respondent_email_rejected_body_localizable = __("Dear #_BOOKINGNAME, <br/>Your requested booking for #_BOOKINGSPACES spaces at #_NAME on #F #j, #Y has been rejected.<br/>Yours faithfully,<br/>#_CONTACTNAME",'dbem').__('<br/><br/>-------------------------------<br/>Powered by Events Manager - http://wp-events-plugin.com','dbem');
	$respondent_email_cancelled_body_localizable = __("Dear #_BOOKINGNAME, <br/>Your requested booking for #_BOOKINGSPACES spaces at #_NAME on #F #j, #Y has been cancelled.<br/>Yours faithfully,<br/>#_CONTACTNAME",'dbem').__('<br/><br/>-------------------------------<br/>Powered by Events Manager - http://wp-events-plugin.com','dbem');
	$event_approved_email_body = __("Dear #_CONTACTNAME, <br/>Your event #_NAME on #F #j, #Y has been approved.<br/>You can view your event here: #_EVENTURL",'dbem').__('<br/><br/>-------------------------------<br/>Powered by Events Manager - http://wp-events-plugin.com','dbem');
	
	$dbem_options = array(
		//defaults
		'dbem_default_category'=>0,
		'dbem_default_location'=>0,
		//Event List Options
		'dbem_events_default_orderby' => 'event_start_date,event_start_time,event_name',
		'dbem_events_default_order' => 'ASC',
		'dbem_events_default_limit' => 10,
		'dbem_list_events_page' => 1,
		//Event Anonymous submissions
		'dbem_events_anonymous_submissions' => 0,
		'dbem_events_anonymous_user' => 0,
		'dbem_events_anonymous_result_success' => 'You have successfully submitted your event, which will be published pending approval.',
		//Event Emails
		'dbem_event_approved_email_subject' => __("Event Approved",'dbem'). " - #_NAME" ,
		'dbem_event_approved_email_body' => str_replace("<br/>", "\n\r", $event_approved_email_body),		
		//Event Formatting
		'dbem_events_page_title' => __('Events','dbem'),
		'dbem_events_page_scope' => 'future',
		'dbem_events_page_search' => 1,
		'dbem_event_list_item_format_header' => '<table cellpadding="0" cellspacing="0" id="current-events" >
    <thead>
        <tr>
			<th id="event-time" width="150">Date/Time</th>
			<th id="event-description" width="*">Event</th>
   	</thead>
    <tbody>',
		'dbem_event_list_item_format' => '<tr>
			<td>
                #_{d/m/Y} #@_{- d/m/Y}<br/>
                #H:#i -#@H:#@i
            </td>
            <td>
                #_EVENTLINK<br/>
                <i>#_LOCATIONNAME, #_LOCATIONTOWN #_LOCATIONSTATE</i>
            </td>
        </tr>',
		'dbem_event_list_item_format_footer' => '</tbody></table>',
		'dbem_display_calendar_in_events_page' => 0,
		'dbem_single_event_format' => '<div style="float:right; margin:0px 0px 15px 15px;">#_MAP</div>
<p>	
	<strong>Date/Time</strong><br/>
	Date(s) - #j #M #Y #@_{ \u\n\t\i\l j M Y}<br />
	<i>#_12HSTARTTIME - #_12HENDTIME</i>
</p>
<p>	
	<strong>Location</strong><br/>
	#_LOCATIONLINK
</p>
<p>	
	<strong>Category(ies)</strong>
	#_CATEGORIES
</p>
<br style="clear:both" />
#_NOTES
{has_bookings}
<h3>Bookings</h3>
#_BOOKINGFORM
{/has_bookings}',
		'dbem_event_page_title_format' => '#_NAME',
		'dbem_no_events_message' => sprintf(__( 'No %s', 'dbem' ),__('Events','dbem')),
		//Location Formatting
		'dbem_locations_default_orderby' => 'name',
		'dbem_locations_default_order' => 'ASC',
		'dbem_locations_page_title' => __('Event','dbem')." ".__('Locations','dbem'),
		'dbem_no_locations_message' => sprintf(__( 'No %s', 'dbem' ),__('Locations','dbem')),
		'dbem_location_default_country' => 'US',
		'dbem_location_list_item_format' => '#_LOCATIONLINK<ul><li>#_ADDRESS, #_LOCATIONTOWN, #_LOCATIONSTATE</li></ul>',
		'dbem_location_page_title_format' => '#_LOCATIONNAME',
		'dbem_single_location_format' => '<div style="float:right; margin:0px 0px 15px 15px;">#_MAP</div>
<p>	
	<strong>Address</strong><br/>
	#_LOCATIONADDRESS<br/>
	#_LOCATIONTOWN<br/>
	#_LOCATIONSTATE<br/>
	#_LOCATIONREGION<br/>
	#_LOCATIONPOSTCODE<br/>
	#_LOCATIONCOUNTRY
</p>
<br style="clear:both" />
#_DESCRIPTION

<h3>Upcoming Events</h3>
<p>#_NEXTEVENTS</p>',
		'dbem_location_no_events_message' => __('<li>No events in this location</li>', 'dbem'),
		'dbem_location_event_list_item_format' => "<li>#_EVENTLINK - #j #M #Y - #H:#i</li>",
		//Category Formatting
		'dbem_category_page_title_format' => '#_CATEGORYNAME',
		'dbem_category_page_format' => '<p>#_CATEGORYNAME</p>#_CATEGORYNOTES<h3>Upcoming Events</h3>#_CATEGORYNEXTEVENTS',
		'dbem_categories_page_title' => __('Event','dbem')." ".__('Categories','dbem'),
		'dbem_categories_list_item_format' => '<li>#_CATEGORYLINK</li>',
		'dbem_no_categories_message' =>  sprintf(__( 'No %s', 'dbem' ),__('Categories','dbem')),
		'dbem_categories_default_orderby' => 'name',
		'dbem_categories_default_order' =>  'ASC',
		'dbem_category_no_events_message' => __('<li>No events in this category</li>', 'dbem'),
		'dbem_category_event_list_item_format' => "<li>#_EVENTLINK - #j #M #Y - #H:#i</li>",
		//RSS Stuff
		'dbem_rss_limit' => 10,
		'dbem_rss_scope' => 'future',
		'dbem_rss_main_title' => get_bloginfo('title')." - ".__('Events', 'dbem'),
		'dbem_rss_main_description' => get_bloginfo('description')." - ".__('Events', 'dbem'),
		'dbem_rss_description_format' => "#j #M #y - #H:#i <br/>#_LOCATION <br/>#_LOCATIONADDRESS <br/>#_LOCATIONTOWN",
		'dbem_rss_title_format' => "#_NAME",
		'em_rss_pubdate' => date('D, d M Y H:i:s T'),
		//iCal Stuff
		'dbem_ical_limit' => 0,
		'dbem_ical_description_format' => "#_NAME - #_LOCATIONNAME - #j #M #y #H:#i",
		//Google Maps
		'dbem_gmap_is_active'=> 1,
		'dbem_location_baloon_format' =>  "<strong>#_LOCATIONNAME</strong><br/>#_LOCATIONADDRESS - #_LOCATIONTOWN<br/><a href='#_LOCATIONPAGEURL'>Details</a>",
		'dbem_map_text_format' => '<strong>#_LOCATION</strong><p>#_LOCATIONADDRESS</p><p>#_LOCATIONTOWN</p>',
		//Email Config
		'dbem_email_disable_registration' => 0,
		'dbem_rsvp_mail_port' => 465,
		'dbem_smtp_host' => 'localhost',
		'dbem_mail_sender_name' => '',
		'dbem_rsvp_mail_send_method' => 'mail',
		'dbem_rsvp_mail_SMTPAuth' => 1,
		//Image Manipulation
		'dbem_image_max_width' => 700,
		'dbem_image_max_height' => 700,
		'dbem_image_max_size' => 204800,
		//Calendar Options
		'dbem_list_date_title' => __('Events', 'dbem').' - #j #M #y',
		'dbem_full_calendar_event_format' => '<li>#_EVENTLINK</li>',
		'dbem_full_calendar_long_events' => '0',
		'dbem_small_calendar_event_title_format' => "#_NAME",
		'dbem_small_calendar_event_title_separator' => ", ", 
		//General Settings
		'dbem_require_location' => 1,
		'dbem_use_select_for_locations' => 0,
		'dbem_attributes_enabled' => 1,
		'dbem_recurrence_enabled'=> 1,
		'dbem_rsvp_enabled'=> 1,
		'dbem_categories_enabled'=> 1,
		'dbem_placeholders_custom' => '',
		//Title rewriting compatability
		'dbem_disable_title_rewrites'=> false,
		'dbem_title_html' => '<h2>#_PAGETITLE</h2>',
		//Bookings
		'dbem_bookings_form_max' => 20,
		'dbem_bookings_registration_disable' => 0,
		'dbem_bookings_registration_user' => '',
		'dbem_bookings_anonymous' => 1, 
		'dbem_bookings_approval' => 1, //approval is on by default
		'dbem_bookings_approval_reserved' => 0, //overbooking before approval?
		'dbem_bookings_login_form' => 1, //show login form on booking area
		'dbem_bookings_approval_overbooking' => 0, //overbooking possible when approving?
		'dbem_bookings_double'=>0,//double bookings or more, users can't double book by default
		'dbem_bookings_user_cancellation' => 1, //can users cancel their booking?
		'dbem_bookings_tax' => 0, //extra tax
		'dbem_bookings_tax_auto_add' => 0, //adjust prices to show tax?
			//messages
			'dbem_booking_feedback_pending' =>__('Booking successful, pending confirmation (you will also receive an email once confirmed).', 'dbem'),
			'dbem_booking_feedback' => __('Booking successful.', 'dbem'),
			'dbem_booking_feedback_full' => __('Booking cannot be made, not enough spaces available!', 'dbem'),
			'dbem_booking_feedback_log_in' => __('You must log in or register to make a booking.','dbem'),
			'dbem_booking_feedback_nomail' => __('However, there were some problems whilst sending confirmation emails to you and/or the event contact person. You may want to contact them directly and letting them know of this error.', 'dbem'),
			'dbem_booking_feedback_error' => __('Booking could not be created','dbem').':',
			'dbem_booking_feedback_email_exists' => __('This email already exists in our system, please log in to register to proceed with your booking.','dbem'),
			'dbem_booking_feedback_new_user' => __('A new user account has been created for you. Please check your email for access details.','dbem'),
			'dbem_booking_feedback_reg_error' => __('There was a problem creating a user account, please contact a website administrator.','dbem'),
			'dbem_booking_feedback_already_booked' => __('You already have booked a seat at this event.','dbem'),
			'dbem_booking_feedback_min_space' => __('You must request at least one space to book an event.','dbem'),
			//Emails
			'dbem_default_contact_person' => 1, //admin
			'dbem_bookings_notify_admin' => 0,
			'dbem_bookings_contact_email' => 1,
			'dbem_bookings_contact_email_subject' => __("New booking",'dbem'),
			'dbem_bookings_contact_email_body' => str_replace("<br/>", "\n\r", $contact_person_email_body_localizable),
			'dbem_contactperson_email_cancelled_subject' => __("Booking Cancelled",'dbem'),
			'dbem_contactperson_email_cancelled_body' => str_replace("<br/>", "\n\r", $contact_person_email_cancelled_body_localizable),
			'dbem_bookings_email_pending_subject' => __("Booking Pending",'dbem'),
			'dbem_bookings_email_pending_body' => str_replace("<br/>", "\n\r", $respondent_email_pending_body_localizable),
			'dbem_bookings_email_rejected_subject' => __("Booking Rejected",'dbem'),
			'dbem_bookings_email_rejected_body' => str_replace("<br/>", "\n\r", $respondent_email_rejected_body_localizable),
			'dbem_bookings_email_confirmed_subject' => __('Booking Confirmed','dbem'),
			'dbem_bookings_email_confirmed_body' => str_replace("<br/>", "\n\r", $respondent_email_body_localizable),
			'dbem_bookings_email_cancelled_subject' => __('Booking Cancelled','dbem'),
			'dbem_bookings_email_cancelled_body' => str_replace("<br/>", "\n\r", $respondent_email_cancelled_body_localizable),
			//Bookings Form - beta - not working at all yet
			'dbem_bookings_page' => '<p>Date/Time - #j #M #Y #_12HSTARTTIME #@_{ \u\n\t\i\l j M Y}<br />Where - #_LOCATIONLINK</p>#_EXCERPT #_BOOKINGFORM<p>'.__('Powered by','dbem').'<a href="http://wp-events-plugin.com">events manager</a></p>',
			'dbem_bookings_page_title' => __('Bookings - #_NAME','dbem'),
			//Ticket Specific Options
			'dbem_bookings_tickets_orderby' => 'ticket_price DESC, ticket_name ASC',
			'dbem_bookings_tickets_priority' => 0,
			'dbem_bookings_tickets_show_unavailable' => 0,
			'dbem_bookings_tickets_show_loggedout' => 1,
			'dbem_bookings_tickets_single' => 0,
			'dbem_bookings_tickets_single_form' => 0, 
			//My Bookings Page
			'dbem_bookings_my_title_format' => __('My Bookings','dbem'),
		//Flags
		'dbem_hello_to_user' => 1,
		//BP Settings
		'dbem_bp_events_list_format_header' => '<ul class="em-events-list">',
		'dbem_bp_events_list_format' => '<li>#_EVENTLINK - #j #M #Y #_12HSTARTTIME #@_{ \u\n\t\i\l j M Y}<ul><li>#_LOCATIONLINK - #_LOCATIONADDRESS, #_LOCATIONTOWN</li></ul></li>',
		'dbem_bp_events_list_format_footer' => '</ul>',
		'dbem_bp_events_list_none_format' => '<p class="em-events-list">'.__('No Events','dbem').'</p>'
	);
	
	foreach($dbem_options as $key => $value){
		add_option($key, $value);
	}
	if( !get_option('dbem_version') ){ add_option('dbem_credits',1); }
	if( get_option('dbem_version') < 4.16 ){
		update_option('dbem_ical_limit',0); //fix, would rather do this than change the option name.
		update_option('dbem_category_no_events_message',get_option('dbem_location_no_events_message'));
		update_option('dbem_category_event_list_item_format',get_option('dbem_location_event_list_item_format'));
	}
	if( get_option('dbem_version') < 4.18 ){
		if( get_option('dbem_category_page_format') == '<p>#_CATEGORYNAME</p>#_CATEGORYNOTES<div><h3>Upcoming Events</h3>#_CATEGORYNEXTEVENTS' ){
			update_option('dbem_category_page_format',$dbem_options['dbem_category_page_format']);
		}
	}
	if( get_option('dbem_version') < 5 ){
		//reset orderby, or convert fields to new fieldnames
		$EM_Event = new EM_Event();
		$orderbyvals = explode(',', get_option('dbem_events_default_orderby'));
		$orderby = array();
		foreach($orderbyvals as $val){
			if(array_key_exists('event_'.$val, $EM_Event->fields)){
				$orderby[] = 'event_'.$val;
			}
		}
		$orderby = (count($orderby) > 0) ? implode(',',$orderby):$dbem_options['dbem_events_default_orderby'];
		update_option('dbem_events_default_orderby',$orderby);
	}
}    

function em_set_mass_caps( $roles, $caps ){
	global $wp_roles;
	foreach( $roles as $user_role ){
		foreach($caps as $cap){
			$wp_roles->add_cap($user_role, $cap);
		}
	}
}

function em_set_capabilities(){
	//Get default roles
	global $wp_roles;
	if( get_option('dbem_version') == '' || get_option('dbem_version') < 4 ){
		//Assign caps in groups, as we go down, permissions are "looser"
		$caps = array('publish_events', 'edit_others_events', 'delete_others_events', 'edit_others_locations', 'delete_others_locations', 'manage_others_bookings', 'edit_categories');
		em_set_mass_caps( array('administrator','editor'), $caps );
		
		//Add all the open caps
		$users = array('administrator','editor');
		$caps = array('edit_events', 'edit_locations', 'delete_events', 'manage_bookings', 'delete_locations', 'edit_recurrences', 'read_others_locations');
		em_set_mass_caps( array('administrator','editor'), $caps );
		if( get_option('dbem_version') == '' ){ //so pre v4 doesn't get security loopholes
			em_set_mass_caps(array('contributor','author','subscriber'), $caps);
		}
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
			'post_title' => __('Events','dbem')
		);
		$post_id = wp_insert_post($post_data, false);
	   	if( $post_id > 0 ){
	   		update_option('dbem_events_page', $post_id); 			
	   	}
	}
}   

// migrate old dbem tables to new em ones
function em_migrate_v4(){
	global $wpdb, $blog_id;
	//disable actions
	remove_action('save_post',array('EM_Location_Post_Admin','save_post'));	
	remove_action('save_post',array('EM_Event_Recurring_Post_Admin','save_post'));
	remove_action('save_post',array('EM_Event_Post_Admin','save_post'),10,1);
	//set shared vars
	$limit = 100;
	$global_mode = get_option('dbem_ms_global_table');
	//-- LOCATIONS --
	if( !is_multisite() || ($global_mode && is_multisite() && is_main_blog()) || (!$global_mode && is_multisite()) ){ //old locations will always belong to the main blog when migrated, since we didn't have previous blog ids
		if( is_multisite() ){ 
			$this_blog = $blog_id;
		}else{
			$this_blog = 0;
		}
		//set location statuses and blog id for all locations
		$wpdb->query('UPDATE '.EM_LOCATIONS_TABLE.' SET location_status=1, blog_id='.$this_blog);
		//first create location posts
		$sql = 'SELECT * FROM '.EM_LOCATIONS_TABLE.' WHERE post_id = 0 LIMIT '.$limit;
		$locations = $wpdb->get_results($sql, ARRAY_A);
		$post_fields = array('post_id','location_slug','location_name','post_content','location_owner');
		$location_metas = array();
		//get location image directory
		$dir = (EM_IMAGE_DS == '/') ? 'locations/':'';
		while( count($locations) > 0 ){
			foreach($locations as $location){
				//new post info
				$post_array = array();
				$post_array['post_type'] = EM_POST_TYPE_LOCATION;
				$post_array['post_title'] = $location['location_name'];
				$post_array['post_content'] = $location['post_content'];
				$post_array['post_status'] = 'publish';
				$post_array['post_author'] = $location['location_owner'];
				//Save post, register post id in index
				$post_id = wp_insert_post($post_array);
				if( is_wp_error($post_id) || $post_id == 0 ){ $post_id = 999999999999999999; }//hopefully nobody blogs that much... if you do, and you're reading this, maybe you should be hiring me for the upgrade ;) }
				$wpdb->query('UPDATE '.EM_LOCATIONS_TABLE." SET post_id='$post_id' WHERE location_id='{$location['location_id']}'");
				//meta
		 		foreach($location as $meta_key => $meta_val){
		 			if( !in_array($meta_key, $post_fields) ){
			 			$location_metas[] = $wpdb->prepare("(%d, '%s', '%s')", array($post_id, '_'.$meta_key, $meta_val));
		 			}
		 		}
			}
		 	//insert the metas in one go, faster than one by one
		 	if( count($location_metas) > 0 ){
			 	$result = $wpdb->query("INSERT INTO ".$wpdb->postmeta." (post_id,meta_key,meta_value) VALUES ".implode(',',$location_metas));
		 	}
			$locations = $wpdb->get_results($sql, ARRAY_A); //get more locations and continue looping		
		}		
	}
	//-- EVENTS & Recurrences --
	if( is_multisite() ){
		if($global_mode && is_main_blog()){
			$sql = "SELECT * FROM ".EM_EVENTS_TABLE." WHERE post_id=0 AND (blog_id=$blog_id OR blog_id=0 OR blog_id IS NULL) LIMIT $limit";
		}elseif($global_mode){
			$sql = "SELECT * FROM ".EM_EVENTS_TABLE." WHERE post_id=0 AND blog_id=$blog_id LIMIT $limit";
		}else{
			$sql = "SELECT * FROM ".EM_EVENTS_TABLE." WHERE post_id=0 LIMIT $limit";
		}
	}else{
		$sql = "SELECT * FROM ".EM_EVENTS_TABLE." WHERE post_id=0 LIMIT $limit";
	}
	//create posts
	$events = $wpdb->get_results($sql, ARRAY_A);
	$post_fields = array('event_slug','event_owner','event_name','event_attributes','post_id','post_content');
	while( count($events) > 0 ){
		$event_metas = array(); //restart metas
		foreach($events as $event){
			//new post info
			$post_array = array();
			$post_array['post_type'] = $event['recurrence'] == 1 ? 'event-recurring' : EM_POST_TYPE_EVENT;
			$post_array['post_title'] = $event['event_name'];
			$post_array['post_content'] = $event['post_content'];
			$post_array['post_status'] = 'publish';
			$post_array['post_author'] = $event['event_owner'];
			$post_array['post_slug'] = $event['event_slug'];
			$event['start_ts'] = strtotime($event['event_start_date']);
			$event['end_ts'] = strtotime($event['event_end_date']);
			//Save post, register post id in index
			$post_id = wp_insert_post($post_array);
			if( is_wp_error($post_id) || $post_id == 0 ){ $post_id = 999999999999999999; }//hopefully nobody blogs that much... if you do, and you're reading this, maybe you should be hiring me for the upgrade ;) }
			if( $post_id != 999999999999999999 ){
				$wpdb->query('UPDATE '.EM_EVENTS_TABLE." SET post_id='$post_id' WHERE event_id='{$event['event_id']}'");
				//meta
		 		foreach($event as $meta_key => $meta_val){
		 			if( !in_array($meta_key, $post_fields) && $meta_key != 'event_attributes' ){
			 			$event_metas[] = $wpdb->prepare("(%d, '%s', '%s')", array($post_id, '_'.$meta_key, $meta_val));
		 			}elseif($meta_key == 'event_attributes'){
		 				$event_attributes = unserialize($meta_val); //from em table it's serialized
						if( is_array($event_attributes) ){
			 				foreach($event_attributes as $att_key => $att_val){
				 				$event_metas[] = $wpdb->prepare("(%d, '%s', '%s')", array($post_id, $att_key, $att_val));
			 				}
						}
		 			}
		 		}
			}
		}
	 	//insert the metas in one go, faster than one by one
	 	if( count($event_metas) > 0 ){
		 	$result = $wpdb->query("INSERT INTO ".$wpdb->postmeta." (post_id,meta_key,meta_value) VALUES ".implode(',',$event_metas));
	 	}
		$events = $wpdb->get_results($sql, ARRAY_A); //get more locations and continue looping		
	}
	//-- CATEGORIES --
	//Create the terms according to category table, use the category owner for the term ids to store this
	$categories = $wpdb->get_results("SELECT * FROM ".EM_CATEGORIES_TABLE, ARRAY_A); //taking a wild-hope guess that there aren't too many categories on one site/blog
	foreach( $categories as $category ){
		$term = get_term_by('slug', $category['category_slug'], EM_TAXONOMY_CATEGORY);
		if( $term === false ){
			//term not created yet, let's create it
			$term_array = wp_insert_term($category['category_name'], EM_TAXONOMY_CATEGORY, array(
				'description' => $category['category_description'],
				'slug' => $category['category_slug']
			));
			if( is_array($term_array) ){
				//update category bg-color if used before
				$wpdb->query('UPDATE '.EM_META_TABLE." SET object_id='{$term_array['term_id']}' WHERE meta_key='category-bgcolor' AND object_id={$category['category_id']}");
				// and assign category image url if file exists
				$dir = (EM_IMAGE_DS == '/') ? 'categories/':'';
			  	foreach(array(1 => 'gif', 2 => 'jpg', 3 => 'png') as $mime_type) { 
					$file_name = $dir."category-{$category['category_id']}.$mime_type";
					if( file_exists( EM_IMAGE_UPLOAD_DIR.$file_name) ) {
			  			$wpdb->insert(EM_META_TABLE, array('object_id'=>$term_array['term_id'],'meta_key'=>'category-image','meta_value'=>EM_IMAGE_UPLOAD_URI.$file_name));
			  			break;
					}
				}
			}
		}
		//get all posts with this category
		$sql = "SELECT post_id FROM ".EM_EVENTS_TABLE.", ".EM_META_TABLE." WHERE event_id=object_id AND meta_key='event-category' AND meta_value='{$category['category_id']}'";
		$category_posts = $wpdb->get_col($sql);
		foreach($category_posts as $post_id){
			wp_set_object_terms($post_id, $category['category_slug'], EM_TAXONOMY_CATEGORY, true);
		}
	}
	//last but not least, flush the toilet
	global $wp_rewrite;
	$wp_rewrite->flush_rules(true);
}
?>