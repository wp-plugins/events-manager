<?php
/*
Plugin Name: Events Manager
Version: 2.0.1rc2
Plugin URI: http://davidebenini.it/wordpress-plugins/events-manager/
Description: Manage events specifying precise spatial data (Location, Town, Province, etc).
Author: Davide Benini, Marcus Skyes
Author URI: http://www.davidebenini.it/blog
*/

/*
Copyright (c) 2009, Davide Benini.  $Revision: 1 $

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/

/*************************************************/ 

// Setting constants
define('EVENTS_TBNAME','dbem_events'); //TABLE NAME
define('RECURRENCE_TBNAME','dbem_recurrence'); //TABLE NAME   
define('LOCATIONS_TBNAME','dbem_locations'); //TABLE NAME  
define('BOOKINGS_TBNAME','dbem_bookings'); //TABLE NAME
define('PEOPLE_TBNAME','dbem_people'); //TABLE NAME  
define('BOOKING_PEOPLE_TBNAME','dbem_bookings_people'); //TABLE NAME  
define('DEFAULT_EVENT_PAGE_NAME', 'Events');   
define('DBEM_PAGE','<!--DBEM_EVENTS_PAGE-->'); //EVENTS PAGE
define('MIN_CAPABILITY', 'edit_posts');	// Minimum user level to access calendars
define('SETTING_CAPABILITY', 'activate_plugins');	// Minimum user level to access calendars
define('DEFAULT_EVENT_LIST_ITEM_FORMAT', '<li>#j #M #Y - #H:#i<br/> #_LINKEDNAME<br/>#_TOWN </li>');
define('DEFAULT_SINGLE_EVENT_FORMAT', '<p>#j #M #Y - #H:#i</p><p>#_TOWN</p>'); 
define('DEFAULT_EVENTS_PAGE_TITLE',__('Events','dbem') ) ;
define('DEFAULT_EVENT_PAGE_TITLE_FORMAT', '	#_NAME'); 
define('DEFAULT_RSS_DESCRIPTION_FORMAT',"#j #M #y - #H:#i <br/>#_LOCATION <br/>#_ADDRESS <br/>#_TOWN");
define('DEFAULT_RSS_TITLE_FORMAT',"#_NAME");
define('DEFAULT_MAP_TEXT_FORMAT', '<strong>#_LOCATION</strong><p>#_ADDRESS</p><p>#_TOWN</p>');     
define('DEFAULT_WIDGET_EVENT_LIST_ITEM_FORMAT','<li>#_LINKEDNAME<ul><li>#j #M #y</li><li>#_TOWN</li></ul></li>');
define('DEFAULT_NO_EVENTS_MESSAGE', __('No events', 'dbem'));  
define('DEFAULT_SINGLE_LOCATION_FORMAT', '<p>#_ADDRESS</p><p>#_TOWN</p>'); 
define('DEFAULT_LOCATION_PAGE_TITLE_FORMAT', '	#_NAME'); 
define('DEFAULT_LOCATION_BALOON_FORMAT', "<strong>#_NAME</strong><br/>#_ADDRESS - #_TOWN<br/><a href='#_LOCATIONPAGEURL'>Details</a>");
define('DEFAULT_LOCATION_EVENT_LIST_ITEM_FORMAT', "<li>#_NAME - #j #M #Y - #H:#i</li>");
define('DEFAULT_LOCATION_NO_EVENTS_MESSAGE', __('<li>No events in this location</li>', 'dbem'));
define("IMAGE_UPLOAD_DIR", "wp-content/uploads/locations-pics");
define('DEFAULT_IMAGE_MAX_WIDTH', 700);  
define('DEFAULT_IMAGE_MAX_HEIGHT', 700);  
define('DEFAULT_IMAGE_MAX_SIZE', 204800);  
// DEBUG constant for developing
// if you are hacking this plugin, set to TRUE, a log will show in admin pages
define('DEBUG', false);     

// INCLUDES   
/* Marcus Begin Edit */
include("marcus-extras.php");
/* Marcus End Edit */     
include("dbem_events.php");
include("dbem_calendar.php");      
include("dbem_widgets.php");
include("dbem_rsvp.php");     
include("dbem_locations.php"); 
include("dbem_people.php");
include("dbem-recurrence.php");    
include("dbem_UI_helpers.php");

require_once("phpmailer/dbem_phpmailer.php") ;
//require_once("phpmailer/language/phpmailer.lang-en.php") ;
  
// Localised date formats as in the jquery UI datepicker plugin
$localised_date_formats = array("am" => "dd.mm.yy","ar" => "dd/mm/yy", "bg" => "dd.mm.yy", "ca" => "mm/dd/yy", "cs" => "dd.mm.yy", "da" => "dd-mm-yy", "de" =>"dd.mm.yy", "es" => "dd/mm/yy", "en" => "mm/dd/yy", "fi" => "dd.mm.yy", "fr" => "dd/mm/yy", "he" => "dd/mm/yy", "hu" => "yy-mm-dd", "hy" => "dd.mm.yy", "id" => "dd/mm/yy", "is" => "dd/mm/yy", "it" => "dd/mm/yy", "ja" => "yy/mm/dd", "ko" => "yy-mm-dd", "lt" => "yy-mm-dd", "lv" => "dd-mm-yy", "nl" => "dd.mm.yy", "no" => "yy-mm-dd", "pl" => "yy-mm-dd", "pt" => "dd/mm/yy", "ro" => "mm/dd/yy", "ru" => "dd.mm.yy", "sk" => "dd.mm.yy", "sv" => "yy-mm-dd", "th" => "dd/mm/yy", "tr" => "dd.mm.yy", "ua" => "dd.mm.yy", "uk" => "dd.mm.yy", "us" => "mm/dd/yy", "CN" => "yy-mm-dd", "TW" => "yy/mm/dd");
//required fields
$required_fields = array('event_name'); 

load_plugin_textdomain('dbem', "/wp-content/plugins/events-manager/langs/");  

// To enable activation through the activate function
register_activation_hook(__FILE__,'events-manager');

// Execute the install script when the plugin is installed
add_action('activate_events-manager/events-manager.php','dbem_install');

// filters for general events field (corresponding to those of  "the _title")
add_filter('dbem_general', 'wptexturize');
add_filter('dbem_general', 'convert_chars');
add_filter('dbem_general', 'trim');
// filters for the notes field  (corresponding to those of  "the _content")   
add_filter('dbem_notes', 'wptexturize');
add_filter('dbem_notes', 'convert_smilies');
add_filter('dbem_notes', 'convert_chars');
add_filter('dbem_notes', 'wpautop');
add_filter('dbem_notes', 'prepend_attachment');
// RSS general filters
add_filter('dbem_general_rss', 'strip_tags');
add_filter('dbem_general_rss', 'ent2ncr', 8);
add_filter('dbem_general_rss', 'wp_specialchars');
// RSS content filter
add_filter('dbem_notes_rss', 'convert_chars', 8);    
add_filter('dbem_notes_rss', 'ent2ncr', 8);

add_filter('dbem_notes_map', 'convert_chars', 8);
add_filter('dbem_notes_map', 'js_escape');
      


/* Creating the wp_events table to store event data*/
function dbem_install() {
 	// Creates the events table if necessary
	dbem_create_events_table();
	dbem_create_recurrence_table();  
	dbem_create_locations_table();
  dbem_create_bookings_table();
  dbem_create_people_table();
	dbem_add_options();
	/* Marcus Begin Edit */
		dbem_create_categories_table();
	/* Marcus End Edit */
  // if ANY 1.0 option is there  AND the version options hasn't been set yet THEN launch the updat script 
	if (get_option('dbem_events_page') && !get_option('dbem_version')) 
		dbem_migrate_old_events();
  
  update_option('dbem_version', 2); 
	// Create events page if necessary
 	$events_page_id = get_option('dbem_events_page')  ;
	if ($events_page_id != "" ) {
		query_posts("page_id=$events_page_id");
		$count = 0;
		while(have_posts()) { the_post();
	 		$count++;
		}
		if ($count == 0)
			dbem_create_events_page(); 
  } else {
	  dbem_create_events_page(); 
  }
    // wp-content must be chmodded 777. Maybe just wp-content.
   if(!file_exists("../".IMAGE_UPLOAD_DIR))
			mkdir("../".IMAGE_UPLOAD_DIR, 0777);
	
}

function dbem_create_events_table() {
	
	global  $wpdb, $user_level;
	require_once(ABSPATH . 'wp-admin/includes/upgrade.php'); 
	
	$old_table_name = $wpdb->prefix."events";
	$table_name = $wpdb->prefix.EVENTS_TBNAME;
	
	if(!($wpdb->get_var("SHOW TABLES LIKE '$old_table_name'") != $old_table_name)) { 
		// upgrading from previous versions             
		    
		$sql = "ALTER TABLE $old_table_name RENAME $table_name;";
		$wpdb->query($sql); 
		  
	}
	 
 
	if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
		// check the user is allowed to make changes
		// get_currentuserinfo();
		// if ($user_level < 8) { return; }
	
		// Creating the events table
		/* Marcus Begin Edit*/
		//Added Category FK Field
		$sql = "CREATE TABLE ".$table_name." (
			event_id mediumint(9) NOT NULL AUTO_INCREMENT,
			event_author mediumint(9) DEFAULT NULL,
			event_name tinytext NOT NULL,
			event_start_time time NOT NULL,
			event_end_time time NOT NULL,
			event_start_date date NOT NULL,
			event_end_date date NULL, 
			event_notes text DEFAULT NULL,
			event_rsvp bool NOT NULL DEFAULT 0,
			event_seats tinyint,
			event_contactperson_id mediumint(9) NULL,  
			location_id mediumint(9) NOT NULL,
			recurrence_id mediumint(9) NULL,
  			event_category_id int(11) default NULL,
			UNIQUE KEY (event_id)
			);";
		/* Marcus End Edit */
		
		dbDelta($sql);
		//--------------  DEBUG CODE to insert a few events n the new table
		// get the current timestamp into an array
		$timestamp = time();
		$date_time_array = getdate($timestamp);

		$hours = $date_time_array['hours'];
		$minutes = $date_time_array['minutes'];
		$seconds = $date_time_array['seconds'];
		$month = $date_time_array['mon'];
		$day = $date_time_array['mday'];
		$year = $date_time_array['year'];

		// use mktime to recreate the unix timestamp
		// adding 19 hours to $hours
		$in_one_week = strftime('%Y-%m-%d', mktime($hours,$minutes,$seconds,$month,$day+7,$year));
		$in_four_weeks = strftime('%Y-%m-%d',mktime($hours,$minutes,$seconds,$month,$day+28,$year)); 
		$in_one_year = strftime('%Y-%m-%d',mktime($hours,$minutes,$seconds,$month,$day,$year+1)); 
		
		$wpdb->query("INSERT INTO ".$table_name." (event_name, event_start_date, event_start_time, event_end_time, location_id)
				VALUES ('Orality in James Joyce Conference', '$in_one_week', '16:00:00', '18:00:00', 1)");
		$wpdb->query("INSERT INTO ".$table_name." (event_name, event_start_date, event_start_time, event_end_time, location_id)
				VALUES ('Traditional music session', '$in_four_weeks', '20:00:00', '22:00:00', 2)");
	  $wpdb->query("INSERT INTO ".$table_name." (event_name, event_start_date, event_start_time, event_end_time, location_id)
					VALUES ('6 Nations, Italy VS Ireland', '$in_one_year','22:00:00', '24:00:00', 3)");
	} else {  
		// eventual maybe_add_column() for later versions
	  maybe_add_column($table_name, 'event_start_date', "alter table $table_name add event_start_date date NOT NULL;"); 
		maybe_add_column($table_name, 'event_end_date', "alter table $table_name add event_end_date date NULL;");
		maybe_add_column($table_name, 'event_start_time', "alter table $table_name add event_start_time time NOT NULL;"); 
		maybe_add_column($table_name, 'event_end_time', "alter table $table_name add event_end_time time NOT NULL;"); 
		maybe_add_column($table_name, 'event_rsvp', "alter table $table_name add event_rsvp BOOL NOT NULL;");
		maybe_add_column($table_name, 'event_seats', "alter table $table_name add event_seats tinyint NULL;"); 
		maybe_add_column($table_name, 'location_id', "alter table $table_name add location_id mediumint(9) NOT NULL;");    
		maybe_add_column($table_name, 'recurrence_id', "alter table $table_name add recurrence_id mediumint(9) NULL;"); 
		maybe_add_column($table_name, 'event_contactperson_id', "alter table $table_name add event_contactperson_id mediumint(9) NULL;");
		
		// Fix buggy columns
		$wpdb->query("ALTER TABLE $table_name MODIFY event_notes text ;");
		$wpdb->query("ALTER TABLE $table_name MODIFY event_author mediumint(9);");
	}
}

function dbem_create_recurrence_table() {
	
	global  $wpdb, $user_level;
	$table_name = $wpdb->prefix.RECURRENCE_TBNAME;

	if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
		
		$sql = "CREATE TABLE ".$table_name." (
			recurrence_id mediumint(9) NOT NULL AUTO_INCREMENT,
			recurrence_name tinytext NOT NULL,
			recurrence_start_date date NOT NULL,
			recurrence_end_date date NOT NULL,
			recurrence_start_time time NOT NULL,
			recurrence_end_time time NOT NULL,
			recurrence_notes text NOT NULL,
			location_id mediumint(9) NOT NULL,
			recurrence_interval tinyint NOT NULL, 
			recurrence_freq tinytext NOT NULL,
			recurrence_byday tinyint NOT NULL,
			recurrence_byweekno tinyint NOT NULL,
			event_contactperson_id mediumint(9) NULL,
			UNIQUE KEY (recurrence_id)
			);";
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);
		
	}
}

function dbem_create_locations_table() {
	
	global  $wpdb, $user_level;
	$table_name = $wpdb->prefix.LOCATIONS_TBNAME;

	if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
		
		// check the user is allowed to make changes
		// get_currentuserinfo();
		// if ($user_level < 8) { return; }

		// Creating the events table
		$sql = "CREATE TABLE ".$table_name." (
			location_id mediumint(9) NOT NULL AUTO_INCREMENT,
			location_name tinytext NOT NULL,
			location_address tinytext NOT NULL,
			location_town tinytext NOT NULL,
			location_province tinytext,
			location_latitude float DEFAULT NULL,
			location_longitude float DEFAULT NULL,
			location_description text DEFAULT NULL,
			UNIQUE KEY (location_id)
			);";
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);
		
		$wpdb->query("INSERT INTO ".$table_name." (location_name, location_address, location_town, location_latitude, location_longitude)
					VALUES ('Arts Millenium Building', 'Newcastle Road','Galway', 53.275, -9.06532)");
   	$wpdb->query("INSERT INTO ".$table_name." (location_name, location_address, location_town, location_latitude, location_longitude)
					VALUES ('The Crane Bar', '2, Sea Road','Galway', 53.2692, -9.06151)");
		$wpdb->query("INSERT INTO ".$table_name." (location_name, location_address, location_town, location_latitude, location_longitude)
					VALUES ('Taaffes Bar', '19 Shop Street','Galway', 53.2725, -9.05321)");
	}
}

function dbem_create_bookings_table() {
	
	global  $wpdb, $user_level;
	$table_name = $wpdb->prefix.BOOKINGS_TBNAME;

	if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
		
		$sql = "CREATE TABLE ".$table_name." (
			booking_id mediumint(9) NOT NULL AUTO_INCREMENT,
			event_id tinyint NOT NULL,
			person_id tinyint NOT NULL, 
			booking_seats tinyint NOT NULL,
			UNIQUE KEY (booking_id)
			);";
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);
		
	}
}

function dbem_create_people_table() {
	
	global  $wpdb, $user_level;
	$table_name = $wpdb->prefix.PEOPLE_TBNAME;

	if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
		
		$sql = "CREATE TABLE ".$table_name." (
			person_id mediumint(9) NOT NULL AUTO_INCREMENT,
			person_name tinytext NOT NULL, 
			person_email tinytext NOT NULL,
			person_phone tinytext NOT NULL,
			UNIQUE KEY (person_id)
			);";
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);
		
	}
} 

function dbem_migrate_old_events() {         
	

		global $wpdb;  
		
		$events_table = $wpdb->prefix.EVENTS_TBNAME;
		$sql = "SELECT event_id, event_time, event_venue, event_address, event_town FROM $events_table";
		//echo $sql;
		$events = $wpdb->get_results($sql, ARRAY_A);
		foreach($events as $event) {

			// Migrating location data to the location table
			$location = array('location_name' => $event['event_venue'], 'location_address' => $event['event_address'], 'location_town' => $event['event_town']);
			$related_location = dbem_get_identical_location($location); 
				 
				if ($related_location)  {
					$event['location_id'] = $related_location['location_id'];     
				}
				else {
			   	$new_location = dbem_insert_location($location);
				  $event['location_id']= $new_location['location_id'];
				}                                 
		 		// migrating event_time to event_start_date and event_start_time
				$event['event_start_date'] = substr($event['event_time'],0,10); 
		    	$event['event_start_time'] = substr($event['event_time'],11,8);
				$event['event_end_time'] = substr($event['event_time'],11,8);
				
				$where = array('event_id' => $event['event_id']); 
	   			$wpdb->update($events_table, $event, $where); 	
        

		
		
		}
		 

}

function dbem_add_options() {
	$contact_person_email_body_localizable = __("#_RESPNAME (#_RESPEMAIL) will attend #_NAME on #m #d, #Y. He wants to reserve #_SPACES spaces.<br/> Now there are #_RESERVEDSPACES spaces reserved, #_AVAILABLESPACES are still available.<br/>Yours faithfully,<br/>Events Manager",'dbem') ;
	$respondent_email_body_localizable = __("Dear #_RESPNAME, <br/>you have successfully reserved #_SPACES space/spaces for #_NAME.<br/>Yours faithfully,<br/> #_CONTACTPERSON",'dbem');
	
	$dbem_options = array('dbem_event_list_item_format' => DEFAULT_EVENT_LIST_ITEM_FORMAT,
	'dbem_display_calendar_in_events_page' => 0,
	'dbem_single_event_format' => DEFAULT_SINGLE_EVENT_FORMAT,
	'dbem_event_page_title_format' => DEFAULT_EVENT_PAGE_TITLE_FORMAT,
	'dbem_list_events_page' => 0,   
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
	'dbem_gmap_is_active'=>0,
	'dbem_gmap_key' => '',
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
	'dbem_hello_to_user' => 1);
	
	foreach($dbem_options as $key => $value)
		dbem_add_option($key, $value);
		
}
function dbem_add_option($key, $value) {
	$option = get_option($key);
	if (empty($option))
		update_option($key, $value);
}      

function dbem_create_events_page(){
	echo "inserimento pagina";
	global $wpdb,$current_user;
	$page_name= DEFAULT_EVENT_PAGE_NAME;
	$sql= "INSERT INTO $wpdb->posts (post_author, post_date, post_date_gmt, post_type, post_content, post_title, post_name, post_modified, post_modified_gmt, comment_count) VALUES ($current_user->ID, '$now', '$now_gmt', 'page','CONTENTS', '$page_name', '".$wpdb->escape(__('events','dbem'))."', '$now', '$now_gmt', '0')";
  // echo($sql);
	$wpdb->query($sql);
    
   update_option('dbem_events_page', mysql_insert_id());
}   

// Create the Manage Events and the Options submenus 
add_action('admin_menu','dbem_create_events_submenu');     
function dbem_create_events_submenu () {
	  if(function_exists('add_submenu_page')) {
	  	add_object_page(__('Events', 'dbem'),__('Events', 'dbem'),MIN_CAPABILITY,__FILE__,dbem_events_subpanel, '../wp-content/plugins/events-manager/images/calendar-16.png');
	   	// Add a submenu to the custom top-level menu: 
			add_submenu_page(__FILE__, __('Edit'),__('Edit'),MIN_CAPABILITY,__FILE__,dbem_events_subpanel);
			add_submenu_page(__FILE__, __('Add new', 'dbem'), __('Add new','dbem'), MIN_CAPABILITY, 'new_event', "dbem_new_event_page"); 
			add_submenu_page(__FILE__, __('Locations', 'dbem'), __('Locations', 'dbem'), MIN_CAPABILITY, 'locations', "dbem_locations_page");
			add_submenu_page(__FILE__, __('People', 'dbem'), __('People', 'dbem'), MIN_CAPABILITY, 'people', "dbem_people_page"); 
			//add_submenu_page(__FILE__, 'Test ', 'Test ', 8, 'test', 'dbem_recurrence_test');
			add_submenu_page(__FILE__, __('Events Manager Settings','dbem'),__('Settings','dbem'), SETTING_CAPABILITY, "events-manager-options", dbem_options_subpanel);
  	}
}

function dbem_replace_placeholders($format, $event, $target="html") {
 	$event_string = $format;
	preg_match_all("/#@?_?[A-Za-z0-9]+/", $format, $placeholders);
	foreach($placeholders[0] as $result) {    
		// echo "RESULT: $result <br>";
		// matches alla fields placeholder  
		//TODO CUSTOM FIX FOR Brian
		// EVENTUALLY REMOVE 
		if (preg_match('/#_JCCSTARTTIME/', $result)) { 
			$time = substr($event['event_start_time'], 0,5);
			$event_string = str_replace($result, $time , $event_string );		
			} 
		// END of REMOVE
		
		if (preg_match('/#_24HSTARTTIME/', $result)) { 
			$time = substr($event['event_start_time'], 0,5);
			$event_string = str_replace($result, $time , $event_string );		
		}
		if (preg_match('/#_24HENDTIME/', $result)) { 
			$time = substr($event['event_end_time'], 0,5);
			$event_string = str_replace($result, $time , $event_string );		
		}
		
		if (preg_match('/#_12HSTARTTIME/', $result)) {
			$AMorPM = "AM"; 
			$hour = substr($event['event_start_time'], 0,2);   
			$minute = substr($event['event_start_time'], 3,2);
			if ($hour > 12) {
				$hour = $hour -12;
				$AMorPM = "PM";
			}
			$time = "$hour:$minute $AMorPM";
			$event_string = str_replace($result, $time , $event_string );		
		}
		if (preg_match('/#_12HENDTIME/', $result)) {
			$AMorPM = "AM"; 
			$hour = substr($event['event_end_time'], 0,2);   
			$minute = substr($event['event_end_time'], 3,2);
			if ($hour > 12) {
				$hour = $hour -12;
				$AMorPM = "PM";
			}
			$time = "$hour:$minute $AMorPM";
			$event_string = str_replace($result, $time , $event_string );		
		}		
		
		if (preg_match('/#_MAP/', $result)) {
			$location = dbem_get_location($event['location_id']);
			$map_div = dbem_single_location_map($location);
		  	$event_string = str_replace($result, $map_div , $event_string ); 
		 
		}
		if (preg_match('/#_ADDBOOKINGFORM/', $result)) {
		 	$rsvp_is_active = get_option('dbem_gmap_is_active'); 
			if ($event['event_rsvp']) {
			   $rsvp_add_module .= dbem_add_booking_form();
			} else {
				$rsvp_add_module .= "";
			}
		 	$event_string = str_replace($result, $rsvp_add_module , $event_string );
		}
		if (preg_match('/#_REMOVEBOOKINGFORM/', $result)) {
		 	$rsvp_is_active = get_option('dbem_gmap_is_active'); 
			if ($event['event_rsvp']) {
			   $rsvp_delete_module .= dbem_delete_booking_form();
			} else {
				$rsvp_delete_module .= "";
			}
		 	$event_string = str_replace($result, $rsvp_delete_module , $event_string );
		}
		if (preg_match('/#_AVAILABLESEATS/', $result)) {
		 	$rsvp_is_active = get_option('dbem_gmap_is_active'); 
			if ($event['event_rsvp']) {
			   $availble_seats .= dbem_get_available_seats($event['event_id']);
			} else {
				$availble_seats .= "";
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
			$event_string = str_replace($result, "<a href='".get_permalink($events_page_id).$joiner."event_id=".$event['event_id']."'   title='".$event['event_name']."'>".$event['event_name']."</a>" , $event_string );
		} 
		if (preg_match('/#_EVENTPAGEURL/', $result)) {
			$events_page_id = get_option('dbem_events_page');
			$event_page_link = get_permalink($events_page_id);
			if (stristr($event_page_link, "?"))
				$joiner = "&amp;";
			else
				$joiner = "?";
			$event_string = str_replace($result, get_permalink($events_page_id).$joiner."event_id=".$event['event_id'] , $event_string );
		}
	 	if (preg_match('/#_(NAME|NOTES|SEATS)/', $result)) {
			$field = "event_".ltrim(strtolower($result), "#_");
		 	$field_value = $event[$field];      
			
			if ($field == "event_notes") {
				if ($target == "html")
					$field_value = apply_filters('dbem_notes', $field_value);
				else
				  if ($target == "map")
					$field_value = apply_filters('dbem_notes_map', $field_value);
				  else
				 	$field_value = apply_filters('dbem_notes_rss', $field_value);
		  	} else {
				if ($target == "html")    
					$field_value = apply_filters('dbem_general', $field_value); 
				else 
					$field_value = apply_filters('dbem_general_rss', $field_value); 
			}
			$event_string = str_replace($result, $field_value , $event_string ); 
	 	}  
	  
		if (preg_match('/#_(ADDRESS|TOWN|PROVINCE)/', $result)) {
			$field = "location_".ltrim(strtolower($result), "#_");
		 	$field_value = $event[$field];      
		
			if ($field == "event_notes") {
				if ($target == "html")
					$field_value = apply_filters('dbem_notes', $field_value);
				else
				  if ($target == "map")
					$field_value = apply_filters('dbem_notes_map', $field_value);
				  else
				 	$field_value = apply_filters('dbem_notes_rss', $field_value);
		  	} else {
				if ($target == "html")    
					$field_value = apply_filters('dbem_general', $field_value); 
				else 
					$field_value = apply_filters('dbem_general_rss', $field_value); 
			}
			$event_string = str_replace($result, $field_value , $event_string ); 
	 	}
	  
		if (preg_match('/#_(LOCATION)$/', $result)) {
			$field = "location_name";
		 	$field_value = $event[$field];     
			if ($target == "html")    
					$field_value = apply_filters('dbem_general', $field_value); 
			else 
				$field_value = apply_filters('dbem_general_rss', $field_value); 
			
			$event_string = str_replace($result, $field_value , $event_string ); 
	 	}
	 	if (preg_match('/#_CONTACTNAME$/', $result)) {
      		$event['event_contactperson_id'] ? $user_id = $event['event_contactperson_id'] : $user_id = get_option('dbem_default_contact_person');
			$name = dbem_get_user_name($user_id);
			$event_string = str_replace($result, $name, $event_string );
		}
		if (preg_match('/#_CONTACTEMAIL$/', $result)) {         
			$event['event_contactperson_id'] ? $user_id = $event['event_contactperson_id'] : $user_id = get_option('dbem_default_contact_person');
      		$email = dbem_get_user_email($user_id);
			$event_string = str_replace($result, dbem_ascii_encode($email), $event_string );
		}
		if (preg_match('/#_CONTACTPHONE$/', $result)) {   
			$event['event_contactperson_id'] ? $user_id = $event['event_contactperson_id'] : $user_id = get_option('dbem_default_contact_person');
      		$phone = dbem_get_user_phone($user_id);
			$event_string = str_replace($result, dbem_ascii_encode($phone), $event_string );
		}	
		if (preg_match('/#_(IMAGE)/', $result)) {
				
        if($event['location_image_url'] != '')
				  $location_image = "<img src='".$event['location_image_url']."' alt='".$event['location_name']."'/>";
				else
					$location_image = "";
				$event_string = str_replace($result, $location_image , $event_string ); 
		 	}
	  
		 if (preg_match('/#_(LOCATIONPAGEURL)/', $result)) { 
			 $events_page_link = dbem_get_events_page(true, false);
			  if (stristr($events_page_link, "?"))
			  	$joiner = "&amp;";
			  else
			  	$joiner = "?";
			$venue_page_link = $events_page_link.$joiner."location_id=".$event['location_id'];
	       	$event_string = str_replace($result, $venue_page_link , $event_string ); 
		}
		// matches all PHP time placeholders for endtime
		if (preg_match('/^#@[dDjlNSwzWFmMntLoYy]$/', $result)) {
			$event_string = str_replace($result, mysql2date(ltrim($result, "#@"), $event['event_end_date']), $event_string ); 
	 	}		    
		
		// matches all PHP date placeholders
		if (preg_match('/^#[dDjlNSwzWFmMntLoYy]$/', $result)) {
			// echo "-inizio-";
			$event_string = str_replace($result, mysql2date(ltrim($result, "#"), $event['event_start_date']),$event_string );  
			// echo $event_string;  
		}

		
		
		// matches all PHP time placeholders
		if (preg_match('/^#@[aABgGhHisueIOPTZcrU]$/', $result)) {
			$event_string = str_replace($result, mysql2date(ltrim($result, "#@"), "0000-00-00 ".$event['event_end_time']),$event_string );  
				// echo $event_string;  
		}
		
		if (preg_match('/^#[aABgGhHisueIOPTZcrU]$/', $result)) {   
			$event_string = str_replace($result, mysql2date(ltrim($result, "#"), "0000-00-00 ".$event['event_start_time']),$event_string );  
			// echo $event_string;  
		}
		
		/* Marcus Begin Edit*/
			//Add a placeholder for categories
		 	if (preg_match('/#_CATEGORY$/', $result)) {
	      		$category = (dbem_get_event_category($event['event_id']));
				$event_string = str_replace($result, $category['category_name'], $event_string );
			}
		/* Marcus End Edit */
		
		     
	}
	/* Marcus Begin Edit */
	preg_match_all("/#@?_\{[A-Za-z0-9 -\/,\.\\\]+\}/", $format, $placeholders);
	foreach($placeholders[0] as $result) {
		if(substr($result, 0, 3 ) == "#@_"){
			$date = 'event_end_date';
			$offset = 4;
		}else{
			$date = 'event_start_date';
			$offset = 3;
		}
		$event_string = str_replace($result, mysql2date(substr($result, $offset, (strlen($result)-($offset+1)) ), $event[$date]),$event_string );
	}
	/* Marcus End Edit */
	
	return $event_string;	
	
}

function dbem_date_to_unix_time($date) {
		$unix_time = mktime(0, 0, 0, substr($date,5,2), substr($date,8,2), substr($date,0,4));
		return $unix_time;   
}
?>