<?php
/*
Plugin Name: Events Manager
Version: 2.0pb
Plugin URI: http://davidebenini.it/wordpress-plugins/events-manager/
Description: Manage events specifying precise spatial data (Venue, Town, Province, etc).
Author: Davide Benini
Author URI: http://www.davidebenini.it/blog
*/

/*
Copyright (c) 2008, Davide Benini.  $Revision: 1 $

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
define('VENUES_TBNAME','dbem_venues'); //TABLE NAME  
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
define('DEFAULT_RSS_DESCRIPTION_FORMAT',"#j #M #y - #H:#i <br/>#_VENUE <br/>#_ADDRESS <br/>#_TOWN");
define('DEFAULT_RSS_TITLE_FORMAT',"#_NAME");
define('DEFAULT_MAP_TEXT_FORMAT', '<strong>#_VENUE</strong><p>#_ADDRESS</p><p>#_TOWN</p>');     
define('DEFAULT_WIDGET_EVENT_LIST_ITEM_FORMAT','<li>#_LINKEDNAME<ul><li>#j #M #y</li><li>#_TOWN</li></ul></li>');
define('DEFAULT_NO_EVENTS_MESSAGE', __('No events', 'dbem'));  

define('DEFAULT_SINGLE_VENUE_FORMAT', '<p>#_ADDRESS</p><p>#_TOWN</p>'); 
define('DEFAULT_VENUE_PAGE_TITLE_FORMAT', '	#_NAME'); 

define("IMAGE_UPLOAD_DIR", "wp-content/uploads/venues-pics");
define('DEFAULT_IMAGE_MAX_WIDTH', 700);  
define('DEFAULT_IMAGE_MAX_HEIGHT', 700);  
define('DEFAULT_IMAGE_MAX_SIZE', 204800);  
// DEBUG constant for developing
// if you are hacking this plugin, set to TRUE, alog will show in admin pages
define('USE_FIREPHP', false);
define('DEBUG', false);     

// if (DEBUG)  { 
// 	if (USE_FIREPHP) {
//  		require('FirePHPCore/fb.php');  
// 		ob_start();
// 		fb('FirePHP activated');   
// 	}
// } 

// INCLUDES        
include("dbem_events.php");
include("dbem_calendar.php");      
include("dbem_widgets.php");
include("dbem_venues_autocomplete.php"); 
include("dbem_rsvp.php");     
include("dbem_venues.php"); 
include("dbem_people.php");
include("dbem-recurrence.php");    
include("dbem_UI_helpers.php");

require_once("phpmailer/dbem_phpmailer.php") ;
//require_once("phpmailer/language/phpmailer.lang-en.php") ;
  
// Localised date formats as in the jquery UI datepicker plugin
$localised_date_formats = array("am" => "dd.mm.yy","ar" => "dd/mm/yy", "bg" => "dd.mm.yy", "ca" => "mm/dd/yy", "cs" => "dd.mm.yy", "da" => "dd-mm-yy", "de" =>"dd.mm.yy", "es" => "dd/mm/yy", "fi" => "dd.mm.yy", "fr" => "dd/mm/yy", "he" => "dd/mm/yy", "hu" => "yy-mm-dd", "hy" => "dd.mm.yy", "id" => "dd/mm/yy", "is" => "dd/mm/yy", "it" => "dd/mm/yy", "ja" => "yy/mm/dd", "ko" => "yy-mm-dd", "lt" => "yy-mm-dd", "lv" => "dd-mm-yy", "nl" => "dd.mm.yy", "no" => "yy-mm-dd", "pl" => "yy-mm-dd", "pt" => "dd/mm/yy", "ro" => "mm/dd/yy", "ru" => "dd.mm.yy", "sk" => "dd.mm.yy", "sv" => "yy-mm-dd", "th" => "dd/mm/yy", "tr" => "dd.mm.yy", "ua" => "dd.mm.yy", "uk" => "dd.mm.yy", "CN" => "yy-mm-dd", "TW" => "yy/mm/dd");
//required fiealds
$required_fields = array('event_name'); 
// DEBUG constant for developing
// if you are hacking this plugin, set to TRUE, alog will show in admin pages


load_plugin_textdomain('dbem', "/wp-content/plugins/events-manager/");
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
	dbem_create_venues_table();
  dbem_create_bookings_table();
  dbem_create_people_table();
	dbem_add_options();
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
    //if (get_option('dbem_events_page'))
			//$event_page_id = get_option('dbem_events_page'); 
		//dbem_create_events_page();
   if(!file_exists(IMAGE_UPLOAD_DIR))
			mkdir(IMAGE_UPLOAD_DIR, 0777);
	
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
		$sql = "CREATE TABLE ".$table_name." (
			event_id mediumint(9) NOT NULL AUTO_INCREMENT,
			event_author mediumint(9) NOT NULL,
			event_name tinytext NOT NULL,
			event_start_time time NOT NULL,
			event_end_time time NOT NULL,
			event_start_date date NOT NULL,
			event_end_date date NULL, 
			event_notes text NOT NULL,
			event_rsvp bool NOT NULL DEFAULT 0,
			event_seats tinyint,  
			venue_id mediumint(9) NOT NULL,
			recurrence_id mediumint(9) NULL,
			UNIQUE KEY (event_id)
			);";
		
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
		
		$wpdb->query("INSERT INTO ".$table_name." (event_name, event_start_date, event_start_time, event_end_time, venue_id)
				VALUES ('Monster gig', '$in_one_week', '16:00:00', '18:00:00', 1)");
		$wpdb->query("INSERT INTO ".$table_name." (event_name, event_start_date, event_start_time, event_end_time, venue_id)
				VALUES ('Fiesta Mexicana', '$in_four_weeks', '20:00:00', '22:00:00', 2)");
	  $wpdb->query("INSERT INTO ".$table_name." (event_name, event_start_date, event_start_time, event_end_time, venue_id)
					VALUES ('Gladiators fight', '$in_one_year','22:00:00', '24:00:00', 2)");
	} else {  
		// eventual maybe_add_column() for later versions
	  maybe_add_column($table_name, 'event_start_date', "alter table $table_name add event_start_date date NOT NULL;"); 
		maybe_add_column($table_name, 'event_end_date', "alter table $table_name add event_end_date date NULL;");
		maybe_add_column($table_name, 'event_start_time', "alter table $table_name add event_start_time time NOT NULL;"); 
		maybe_add_column($table_name, 'event_end_time', "alter table $table_name add event_end_time time NOT NULL;"); 
		maybe_add_column($table_name, 'event_rsvp', "alter table $table_name add event_rsvp BOOL NOT NULL;");
		maybe_add_column($table_name, 'event_seats', "alter table $table_name add event_seats tinyint NULL;"); 
		maybe_add_column($table_name, 'venue_id', "alter table $table_name add venue_id mediumint(9) NOT NULL;");    
		maybe_add_column($table_name, 'recurrence_id', "alter table $table_name add recurrence_id mediumint(9) NULL;");  
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
			venue_id mediumint(9) NOT NULL,
			recurrence_interval tinyint NOT NULL, 
			recurrence_freq tinytext NOT NULL,
			recurrence_byday tinyint NOT NULL,
			recurrence_byweekno tinyint NOT NULL,
			UNIQUE KEY (recurrence_id)
			);";
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);
		
	}
}

function dbem_create_venues_table() {
	
	global  $wpdb, $user_level;
	$table_name = $wpdb->prefix.VENUES_TBNAME;

	if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
		
		// check the user is allowed to make changes
		// get_currentuserinfo();
		// if ($user_level < 8) { return; }

		// Creating the events table
		$sql = "CREATE TABLE ".$table_name." (
			venue_id mediumint(9) NOT NULL AUTO_INCREMENT,
			venue_name tinytext NOT NULL,
			venue_address tinytext NOT NULL,
			venue_town tinytext NOT NULL,
			venue_province tinytext,
			venue_latitude float DEFAULT NULL,
			venue_longitude float DEFAULT NULL,
			UNIQUE KEY (venue_id)
			);";
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);
		
		$wpdb->query("INSERT INTO ".$table_name." (venue_name, venue_address, venue_town)
					VALUES ('Arena', 'Piazza Bra','Verona')");
      $wpdb->query("INSERT INTO ".$table_name." (venue_name, venue_address, venue_town)
							VALUES ('Hardrock Cafe', '1501 Broadway','New York')");
		$wpdb->query("INSERT INTO ".$table_name." (venue_name, venue_address, venue_town)
				VALUES ('Wembley Stadium', 'Wembley','London')");
		$wpdb->query("INSERT INTO ".$table_name." (venue_name, venue_address, venue_town)
					VALUES ('Harp Pub', 'Via Cantarane','Verona')");
      $wpdb->query("INSERT INTO ".$table_name." (venue_name, venue_address, venue_town)
							VALUES ('Silverstar Pub', 'Via Bentegodi','Verona')");
		$wpdb->query("INSERT INTO ".$table_name." (venue_name, venue_address, venue_town)
				VALUES ('Hartigan pub', 'Vicolo cieco disciplina','Verona')");		
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
	$version = get_option('dbem_version');  
	
	if ($version < 2)  {
		global $wpdb;  
		
		$events_table = $wpdb->prefix.EVENTS_TBNAME;
		$sql = "SELECT event_id, event_time, event_venue, event_address, event_town FROM $events_table";
		echo $sql;
		$events = $wpdb->get_results($sql, ARRAY_A);
		foreach($events as $event) {

			// Migrating venue data to the venue table
			$venue = array('venue_name' => $event['event_venue'], 'venue_address' => $event['event_address'], 'venue_town' => $event['event_town']);
			$related_venue = dbem_get_identical_venue($venue); 
				 
				if ($related_venue)  {
					$event['venue_id'] = $related_venue['venue_id'];     
				}
				else {
			   	$new_venue = dbem_insert_venue($venue);
				  $event['venue_id']= $new_venue['venue_id'];
				}                                 
		 		// migrating event_time to event_start_date and event_start_time
				$event['event_start_date'] = substr($event['event_time'],0,10); 
		    $event['event_start_time'] = substr($event['event_time'],11,8);
				$event['event_end_time'] = substr($event['event_time'],11,8);
				
				$where = array('event_id' => $event['event_id']); 
	   		$wpdb->update($events_table, $event, $where); 	
        

		
		
		}
		 
	}
}

function dbem_add_options() {
	// Adding plugin options
	$event_list_item_format = get_option('dbem_event_list_item_format');
	if (empty($event_list_item_format))
		update_option('dbem_event_list_item_format', DEFAULT_EVENT_LIST_ITEM_FORMAT); 
	
	$single_event_format = get_option('dbem_single_event_format');
	if (empty($single_event_format)) 
		update_option('dbem_single_event_format', DEFAULT_SINGLE_EVENT_FORMAT);     
	
	$event_page_title_format = get_option('dbem_event_page_title_format');
	if (empty($event_page_title_format)) 
		update_option('dbem_event_page_title_format', DEFAULT_EVENT_PAGE_TITLE_FORMAT);     
	
	$list_events_page = get_option('dbem_list_events_page');
	if (empty($list_events_page)) 
		update_option('dbem_list_events_page', 0);   
	
	$events_page_title = get_option('dbem_events_page_title'); 
	if (empty($events_page_title))
		update_option('dbem_events_page_title', DEFAULT_EVENTS_PAGE_TITLE);
	
	$no_events_message = get_option('dbem_no_events_message'); 
	if (empty($no_events_message))
		update_option('dbem_no_events_message', __('No events','dbem'));
	
	$venue_page_title_format = get_option('dbem_venue_page_title_format');
	if (empty($venue_page_title_format)) 
		update_option('dbem_venue_page_title_format', DEFAULT_VENUE_PAGE_TITLE_FORMAT);
	
	$single_venue_format = get_option('dbem_single_venue_format');
	if (empty($single_venue_format)) 
		update_option('dbem_single_venue_format', DEFAULT_SINGLE_VENUE_FORMAT);
	
	$map_text_format = get_option('dbem_map_text_format');
	if (empty($map_text_format)) 
		update_option('dbem_map_text_format', DEFAULT_MAP_TEXT_FORMAT);   
	
	$rss_main_title = get_option('dbem_rss_main_title');
	if (empty($rss_main_title)) {
		$default_rss_main_title = get_bloginfo('title')." - ".__('Events');
		update_option('dbem_rss_main_title', $default_rss_main_title);
	}
	
	$rss_main_description = get_option('dbem_rss_main_description');
	if (empty($rss_main_description)) { 
		$default_rss_main_description = get_bloginfo('description')." - ".__('Events');
		update_option('dbem_rss_main_description', $default_rss_main_description);
	}
	
	$rss_description_format = get_option('dbem_rss_description_format');
	if (empty($rss_description_format)) 
		update_option('dbem_rss_description_format', DEFAULT_RSS_DESCRIPTION_FORMAT);   
		
	$rss_title_format = get_option('dbem_rss_title_format');
	if (empty($rss_title_format)) 
		update_option('dbem_rss_title_format', DEFAULT_RSS_TITLE_FORMAT);
	
	$gmap_is_active = get_option('dbem_gmap_is_active');
	if(empty($gmap_is_active))
		update_option('dbem_gmap_is_active', 0);       
	
	$gmap_key = get_option('dbem_gmap_key');
	if (empty($gmap_key))
		update_option('dbem_gmap_key', ''); 
	
	$rsvp_is_active = get_option('dbem_rsvp_is_active');
	if (empty($rsvp_is_active))
		update_option('dbem_rsvp_is_active', 0);
	
	$rsvp_mail_notify_is_active = get_option('dbem_rsvp_mail_notify_is_active');
	if (empty($rsvp_mail_notify_is_active))
		update_option('dbem_rsvp_mail_notify_is_active', 0);
	
	$image_max_width = get_option('dbem_image_max_width');
	if (empty($image_max_width))
		update_option('dbem_image_max_width', DEFAULT_IMAGE_MAX_WIDTH);
	
	$image_max_height = get_option('dbem_image_max_height');
	if (empty($image_max_height))
		update_option('dbem_image_max_height', DEFAULT_IMAGE_MAX_HEIGHT);
		
	$image_max_size = get_option('dbem_image_max_size');
	if (empty($image_max_size))
		update_option('dbem_image_max_size', DEFAULT_IMAGE_MAX_SIZE);
	
	$version = get_option('dbem_version');
	if (empty($version))
		update_option('dbem_version', 1);	
		
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
	$sql= "INSERT INTO $wpdb->posts (post_author, post_date, post_date_gmt, post_type, post_content, post_title, post_name, post_modified, post_modified_gmt, comment_count) VALUES ($current_user->ID, '$now', '$now_gmt', 'page','CONTENTS', '$page_name', '".$wpdb->escape(__('Events','dbem'))."', '$now', '$now_gmt', '0')";
  // echo($sql);
	$wpdb->query($sql);
    
   update_option('dbem_events_page', mysql_insert_id());
}   

// Create the Manage Events and the Options submenus 
add_action('admin_menu','dbem_create_events_submenu');     
function dbem_create_events_submenu () {
	  if(function_exists('add_submenu_page')) {
	  	add_object_page(__('Events', 'dbem'),__('Events', 'dbem'),MIN_CAPABILITY,__FILE__,dbem_events_subpanel, '../wp-content/plugins/events-manager/images/calendar-16.png');
	   	add_submenu_page(__FILE__, __('Add new'), __('Add new'), MIN_CAPABILITY, 'new_event', "dbem_new_event_page"); 
	// Add a submenu to the custom top-level menu:                
			add_submenu_page(__FILE__, "Venues", "Venues", MIN_CAPABILITY, 'venues', "dbem_venues_page");
			add_submenu_page(__FILE__, "People", "People", MIN_CAPABILITY, 'people', "dbem_people_page"); 
		 // add_submenu_page(__FILE__, 'Test ', 'Test Sublevel', 8, 'venues', );
	//   add_options_page('Events Manager','Events Manager',MIN_LEVEL,'eventmanager.php',dbem_options_subpanel);
		 	//add_submenu_page(__FILE__, "TEST", "test", MIN_CAPABILITY, 'recurrence', "dbem_recurrence_test");
			add_submenu_page(__FILE__, 'Events Manager Settings','Settings', SETTING_CAPABILITY, "events-manager-options", dbem_options_subpanel);
		     
		
		
		
		}
}

function dbem_replace_placeholders($format, $event, $target="html") {
	
	$event_string = $format;
	preg_match_all("/#@?_?[A-Za-z]+/", $format, $placeholders);
	foreach($placeholders[0] as $result) {    
		// echo "RESULT: $result <br>";
		// matches alla fields placeholder
		if (preg_match('/#_MAP/', $result)) {
		
		 	$gmap_is_active = get_option('dbem_gmap_is_active'); 
			if ($gmap_is_active) {  
		 
			   $map_div = "<div id='event-map' style=' background: green; width: 200px; height: 100px'></div>" ;
			} else {
				$map_div = "";
			}
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
		if (preg_match('/#_URL/', $result)) {
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
			$field = "venue_".ltrim(strtolower($result), "#_");
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
	  
		if (preg_match('/#_(VENUE)/', $result)) {
			$field = "venue_name";
		 	$field_value = $event[$field];     
			if ($target == "html")    
					$field_value = apply_filters('dbem_general', $field_value); 
			else 
				$field_value = apply_filters('dbem_general_rss', $field_value); 
			
			$event_string = str_replace($result, $field_value , $event_string ); 
	 	}
	  
			
		if (preg_match('/#_(IMAGE)/', $result)) {
				
        if($event['venue_image_url'] != '')
				  $venue_image = "<img src='".$event['venue_image_url']."' alt='".$event['venue_name']."'/>";
				else
					$venue_image = "";
				$event_string = str_replace($result, $venue_image , $event_string ); 
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
		
		     
	}
	return $event_string;	
	
}

?>