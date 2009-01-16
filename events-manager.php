<?php
/*
Plugin Name: Events Manager
Version: 1.5a2
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

// DEBUG constant for developing
// if you are hacking this plugin, set to TRUE, alog will show in admin pages
define('USE_FIREPHP', true);
define('DEBUG', true);     

if (DEBUG)  { 
	if (USE_FIREPHP) {
 		require('FirePHPCore/fb.php');  
		ob_start();
		fb('FirePHP activated');   
}
} 

dbem_log('Dbem-log working');
// INCLUDES
include("dbem_calendar.php");      
include("dbem_widgets.php");
  
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
      
// Custom log function
function dbem_log($text) {
	if (DEBUG)
  	fb($text,FirePHP::LOG);
}


/* Creating the wp_events table to store event data*/
function dbem_install() {
 	// Creates the events table if necessary
	dbem_create_events_table();  
	dbem_create_venues_table();
  dbem_create_bookings_table();
  dbem_create_people_table();
 	dbem_add_options();
   
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

	
}

function dbem_create_events_table() {
	
	global  $wpdb, $user_level;
	require_once(ABSPATH . 'wp-admin/includes/upgrade.php'); 
	
	$old_table_name = $wpdb->prefix."events";
	$table_name = $wpdb->prefix.EVENTS_TBNAME;
	
	if(!($wpdb->get_var("SHOW TABLES LIKE '$old_table_name'") != $old_table_name)) { 
		// upgrading from previous versions             
		    
		$sql = "ALTER TABLE $old_table_name RENAME $table_name;";
		dbem_log("sql rename: $sql");
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
			event_time datetime NOT NULL,
			event_end_time datetime NOT NULL, 
			event_venue tinytext NOT NULL,
			event_address tinytext NOT NULL,
			event_town tinytext NOT NULL,
			event_province tinytext,
			event_notes text NOT NULL,
			event_latitude float DEFAULT NULL,
			event_longitude float DEFAULT NULL,
			event_rsvp bool NOT NULL DEFAULT 0,
			event_seats tinyint,
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
		$in_one_week = strftime('%Y-%m-%d 21:30:00', mktime($hours,$minutes,$seconds,$month,$day+7,$year));
		$in_four_weeks = strftime('%Y-%m-%d 16:00:00',mktime($hours,$minutes,$seconds,$month,$day+28,$year)); 
		$in_one_year = strftime('%Y-%m-%d 22:00:00',mktime($hours,$minutes,$seconds,$month,$day,$year+1)); 
		
		$wpdb->query("INSERT INTO ".$table_name." (event_name, event_venue, event_address, event_time, event_end_time, event_town)
				VALUES ('Monster gig','Wembley stadium', 'Wembley', '$in_one_week', '$in_one_week', 'London')");
		$wpdb->query("INSERT INTO ".$table_name." (event_name, event_venue,event_address, event_time, event_end_time, event_town)
				VALUES ('Fiesta Mexicana','Hard Rock Cafe', '1501 Broadway', '$in_four_weeks','$in_four_weeks','New York')");
	  $wpdb->query("INSERT INTO ".$table_name." (event_name, event_venue,event_address, event_time,event_end_time, event_town)
					VALUES ('Gladiators fight','Arena', 'Piazza Bra', '$in_one_year','$in_one_year','Verona')");
	} else {  
		// eventual maybe_add_column() for later versions
	  maybe_add_column($table_name, 'event_end_time', "alter table $table_name add event_end_time datetime NOT NULL;"); 
		maybe_add_column($table_name, 'event_rsvp', "alter table $table_name add event_rsvp BOOL NOT NULL;");
		maybe_add_column($table_name, 'event_seats', "alter table $table_name add event_seats tinyint NULL;");
	}
}


function dbem_create_venues_table() {
	
	global  $wpdb, $user_level;
	$table_name = $wpdb->prefix.VENUES_TBNAME;

	if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
		
		dbem_log('Creating the venues table'); 
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
		
		dbem_log('Creating the bookings table'); 

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
		
		dbem_log('Creating the people table'); 


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


 
function dbem_add_options() {
	dbem_log('Adding options, if necessary'); 
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
	  	add_menu_page(__('Events', 'dbem'),__('Events', 'dbem'),MIN_CAPABILITY,__FILE__,dbem_events_subpanel);
	   	// Add a submenu to the custom top-level menu:                
			add_submenu_page(__FILE__, "Venues", "Venues", MIN_CAPABILITY, 'venues', "dbem_venues_page");
			add_submenu_page(__FILE__, "People", "People", MIN_CAPABILITY, 'people', "dbem_people_page"); 
		 // add_submenu_page(__FILE__, 'Test ', 'Test Sublevel', 8, 'venues', );
	//   add_options_page('Events Manager','Events Manager',MIN_LEVEL,'eventmanager.php',dbem_options_subpanel);
		 	add_options_page('Events manager', 'Events Manager', SETTING_CAPABILITY, __FILE__, dbem_options_subpanel);
		     
		
		
		
		}
}
add_action('init', 'dbem_intercept_url');
function dbem_intercept_url() {  
	// TODO move here most actions
	
	global $wpdb;
	$action=$_GET['action'];
	$event_id=$_GET['event_id'];
	$scope=$_GET['scope']; 
	$offset=$_GET['offset'];
	$order=$_GET['order'];
	if ($order == "")
	 $order = "ASC";
	if ($offset=="")
	 $offset = "0";
	$event_table_name = $wpdb->prefix.EVENTS_TBNAME;
	
	if ($action == 'dbem_delete') {   
		if ($event_id)
			$sql="DELETE FROM ".$event_table_name." WHERE event_id='"."$event_id"."'";
    
		// TODO eventual error if ID in non-existant
		$wpdb->query($sql);

		//$events = dbem_get_events("", "future");   
	}  
	if ($_GET['secondaryAction'] == "delete_bookings") {
		$bookings = $_GET['bookings'];
		if ($bookings) {
			foreach($bookings as $booking_id) {
				dbem_log($booking_id);    
				dbem_delete_booking($booking_id);
			}
		}
	}
}

// Events manager page
function dbem_events_subpanel() {
  global $wpdb;
	$action=$_GET['action'];
	$element=$_GET['event_id'];
	$scope=$_GET['scope']; 
	$offset=$_GET['offset'];
	$order=$_GET['order'];
	if ($order == "")
	 $order = "ASC";
	if ($offset=="")
	 $offset = "0";
	$event_table_name = $wpdb->prefix.EVENTS_TBNAME;
	// Debug code, to make sure I get the correct page


		// DELETE action
	if ($action == 'dbem_delete') {
	  //  $sql="DELETE FROM ".$event_table_name." WHERE event_id='"."$element"."'";
    
		// TODO eventual error if ID in non-existant
		//$wpdb->query($sql);

		$events = dbem_get_events("", "future");
		dbem_events_table($events, 10,"Future events");
	}
	// UPDATE or CREATE action
	if ($action == 'update_event') {

		$event = array();
		$event['event_date']= $_POST[event_date];
		$event['event_end_date'] = $_POST[event_end_date]; 
		// Set event end time to event time if not valid
		if (!_dbem_is_date_valid($event['event_end_date']))
			$event['event_end_date'] = $event['event-date'];
	   
		$event['event_name']=$_POST[event_name];
		$event['event_hh'] = $_POST[event_hh];
		$event['event_mm'] = $_POST[event_mm];
		$event_time = $event['event_hh'].":".$event['event_mm'];
				
		$event['event_end_hh']=$_POST[event_end_hh];
		$event['event_end_mm']=$_POST[event_end_mm];  
		$event_end_time = $event['event_end_hh'].":".$event['event_end_mm'];        
		// Set event end time to event time if not valid

		if(!_dbem_is_time_valid($event_end_time))
	 		$event_end_time = $event_time;
		
		$event['event_venue']=$_POST[event_venue];
		$event['event_address']=$_POST[event_address];
		$event['event_town']=$_POST[event_town];
		$event['event_province']=$_POST[event_province];
		$event['event_latitude']=$_POST[event_latitude];
		$event['event_longitude']=$_POST[event_longitude];
		$event['event_notes']=$_POST[event_notes];
		$datetime="'{$event['event_date']} $event_time:00'";
		$end_datetime="'{$event['event_end_date']} $event_end_time:00'"; 
		// $datetime="$event_day-$event_month-$event_year $event_hh:$event_mm:00";
		$validation_result = dbem_validate_event($event);
		if ( $validation_result == "OK") {  
		  	// validation successful
		  	if(!$element) {
				// INSERT new event
				$sql="INSERT INTO ".$event_table_name." (event_name, event_venue, event_town, event_address, event_province, event_time, event_end_time, event_latitude, event_longitude, event_notes)
						VALUES ('".$event['event_name']."','".$event['event_venue']."','".$event['event_town']."','".$event['event_address']."','".$event['event_province']."',".$datetime.",".$end_datetime.",'".$event['event_latitude']."','".$event['event_longitude']."','".$event['event_notes']."');";
			$feedback_message = __('New event successfully inserted!','dbem');  
			
			} else {
				// UPDATE old event
				$sql="UPDATE ".$event_table_name. 
				" SET event_name='".$event['event_name']."', ".
					"event_venue='".$event['event_venue']."',".
					"event_town='".$event['event_town']."',".
					"event_address='".$event['event_address']."',".
					"event_province='".$event['event_province']."',".
					"event_latitude='".$event['event_latitude']."',".
					"event_longitude='".$event['event_longitude']."',".
					"event_notes='".$event['event_notes']."',".
					"event_time=$datetime ".",".
					"event_end_time=$end_datetime ".  
					"WHERE event_id="."$element";
				$feedback_message = __('Event','dbem')." $element ".__('updated','dbem')."!";
				}
			  dbem_cache_venue($event);  

					
				$wpdb->query($sql); 
				echo "<div id='message' class='updated fade'>
						<p>$feedback_message</p>
					  </div>";
				$events = dbem_get_events("", "future");  
				dbem_events_table($events, 10, "Future events"); 
			} else {
			  	// validation unsuccessful
			
				echo $event['name'];

				echo "<div id='message' class='error '>
						<p>".__("Ach, there's a problem here:","dbem")." $validation_result</p>
					  </div>";	
				dbem_event_form($event,"Edit event $element" ,$element);
				 
			}
		}  
		if ($action == 'edit_event') {
				if (!$element) {
				$title=__("Insert New Event", 'dbem');
				} else {
				$title=__("Edit Event", 'dbem')." $element";
				}
				// If a edit operation was requested queries the event table
				$sql='SELECT event_id, 
							event_name, 
							event_venue, 
							event_address,
							event_town, 
							event_province, 
							DATE_FORMAT(event_time, "%Y-%m-%e") AS "event_date",
							DATE_FORMAT(event_time, "%e") AS "event_day",
							DATE_FORMAT(event_time, "%m") AS "event_month",
							DATE_FORMAT(event_time, "%Y") AS "event_year",
							DATE_FORMAT(event_time, "%k") AS "event_hh",
							DATE_FORMAT(event_time, "%i") AS "event_mm", 
							DATE_FORMAT(event_end_time, "%Y-%m-%e") AS "event_end_date",
						  	DATE_FORMAT(event_end_time, "%e") AS "event_end_day",
						  	DATE_FORMAT(event_end_time, "%m") AS "event_end_month",
						  	DATE_FORMAT(event_end_time, "%Y") AS "event_end_year",
						  	DATE_FORMAT(event_end_time, "%k") AS "event_end_hh",
						  	DATE_FORMAT(event_end_time, "%i") AS "event_end_mm",
							event_latitude,
							event_longitude,
							event_notes
					FROM '.$event_table_name.' WHERE event_id="'.$element.'";';
					$event=$wpdb->get_row($sql, ARRAY_A);
					// Enter new events and updates old ones
					// DEBUG: echo"Nome: $event->event_name";

					
					dbem_event_form($event, $title, $element);
				    
	  } 
	 if ($action == ""){
			// No action, only showing the events list

				
			 
			switch ($scope) {
				case "past":
					$title = __('Past Events','dbem');
					break;
				case "all":
					$title = __('All Events','dbem'); 
					break;
			  default:
					$title = __('Future Events','dbem'); 
					$scope = "future";   
			}
		  $limit = 10;
		  $events = dbem_get_events($limit, $scope, $order,$offset);  
			
		  dbem_events_table($events, $limit, $title);
			
	}
	
                                                
}         

// Function composing the options subpanel
function dbem_options_subpanel() {
	
   ?>
	<div class="wrap">
		<h2><?php _e('Event Manager Options','dbem'); ?></h2>
			<form id="dbem_options_form" method="post" action="options.php">
				<?php wp_nonce_field('update-options'); ?>
            <table class="form-table">
					<?php $use_event_end = get_option('dbem_use_event_end'); ?>   
				 
					<tr valign="top">
						<th scope="row"><?php _e('Use events end?','dbem'); ?></th>
					  	<td>  
							<input id="dbem_use_event_end_yes" name="dbem_use_event_end" type="radio" value="1" <?php if($use_event_end) echo "checked='checked'"; ?> /><?php _e('Yes'); ?> <br />
							<input name="dbem_use_event_end" type="radio" value="0" <?php if(!$use_event_end) echo "checked='checked'"; ?> /> <?php _e('No'); ?>  <br />
							<?php _e('Check this option if you want to set and end date/time for your events.','dbem')?>
						</td>
					</tr>     
					
				    
					<tr valign="top">
						<th scope="row"><?php _e('Default event list format','dbem')?></th>
						<td><textarea name="dbem_event_list_item_format" id="dbem_event_list_item_format" rows="6" cols="60"><?php echo (get_option('dbem_event_list_item_format'));?></textarea><br/>
							<?php _e('The format of any events in a list.','dbem')?><br/>
							<?php _e('Insert one or more of the following placeholders: <code>#_NAME</code>, <code>#_VENUE</code>, <code>#_ADDRESS</code>, <code>#_TOWN</code>, <code>#_NOTES</code>. Use <code>#_LINKEDNAME</code> for the event name with a link to the given event page. Use #_URL to print the event URL and make your own customised links.','dbem')?>
							<?php _e('To insert date and time values, use <a href="http://www.php.net/manual/en/function.date.php">PHP time format characters</a>  with a # symbol before them, i.e. #m. #M, #j, etc. ','dbem')?><br/>  
							<?php _e('Use HTML tags as <code>li</code>, <code>br</code>, etc.','dbem')?></td>
					</tr>
					<tr valign="top">
						<th scope="row"><?php _e('Single event page title format','dbem');?></th>
						<td>
							<input name="dbem_event_page_title_format" type="text" id="dbem_event_page_title_format" style="width: 95%" value="<?php echo get_option('dbem_event_page_title_format'); ?>" size="45" /><br />
							<?php _e('The format of a single event page title.','dbem')?><br/>
							<?php _e('Follow the previous formatting instructions.','dbem')?>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row"><?php _e('Default single event format','dbem')?></th>
						<td>
							<textarea name="dbem_single_event_format" id="dbem_single_event_format" rows="6" cols="60"><?php echo (get_option('dbem_single_event_format'));?></textarea><br/>
							<?php _e('The format of a single eventy page.','dbem')?><br/>
							<?php _e('Follow the previous formatting instructions.','dbem')?><br/>
							<?php _e('Use <code>#_MAP</code> to insert a map.','dbem')?>
					   	</td>
					</tr>
					
					
					<?php $list_events_page = get_option('dbem_list_events_page'); ?>
					 
				   	<tr valign="top">
				   		<th scope="row"><?php _e('Show events page in lists?','dbem'); ?></th>
				   		<td>   
							<input id="dbem_list_events_page" name="dbem_list_events_page" type="radio" value="1" <?php if($list_events_page) echo "checked='checked'"; ?> /><?php _e('Yes'); ?> <br />
							<input name="dbem_list_events_page" type="radio" value="0" <?php if(!$list_events_page) echo "checked='checked'"; ?> /><?php _e('No'); ?> <br />
							<?php _e('Check this option if you want the events page to appear together with other pages in pages lists.','dbem')?>
						</td>
				   	</tr>
					
					  				
					
					
					<tr valign="top">
						<th scope="row"><?php _e('Events page title','dbem'); ?></th>
						<td>
							<input name="dbem_events_page_title" type="text" id="dbem_events_page_title" style="width: 95%" value="<?php echo get_option('dbem_events_page_title'); ?>" size="45" /><br />
							<?php _e('The title on the multiple events page.','dbem')?>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row"><?php _e('No events message','dbem');?></th>
						<td>
							<input name="dbem_no_events_message" type="text" id="dbem_no_events_message" style="width: 95%" value="<?php echo get_option('dbem_no_events_message'); ?>" size="45" /><br />
							<?php _e('The message displayed when no events are available.','dbem')?><br/>
						 </td>
					</tr>
					
					
					
					
					
					
					
					<tr valign="top">
						<th scope="row"><?php _e('RSS main title','dbem'); ?></th>
						<td>
							<input name="dbem_rss_main_title" type="text" id="dbem_rss_main_title" style="width: 95%" value="<?php echo get_option('dbem_rss_main_title'); ?>" size="45" /><br />
							<?php _e('The main title of your RSS events feed.','dbem')?>
							
						</td>
					</tr>	<tr valign="top">
							<th scope="row"><?php _e('RSS main description','dbem'); ?></th>
							<td>
								<input name="dbem_rss_main_description" type="text" id="dbem_rss_main_description" style="width: 95%" value="<?php echo get_option('dbem_rss_main_description'); ?>" size="45" /><br />
								<?php _e('The main description of your RSS events feed.','dbem')?>

							</td>
						</tr>
					<tr valign="top">
						<th scope="row"><?php _e('RSS title format','dbem'); ?></th>
						<td>
							<input name="dbem_rss_title_format" type="text" id="dbem_rss_title_format" style="width: 95%" value="<?php echo get_option('dbem_rss_title_format'); ?>" size="45" /><br />
							<?php _e('The format of the title of each item in the events RSS feed.','dbem')?>
							
						</td>
					</tr>
					<tr valign="top">
						<th scope="row"><?php _e('RSS description format','dbem'); ?></th>
						<td>
							<input name="dbem_rss_description_format" type="text" id="dbem_rss_description_format" style="width: 95%" value="<?php echo get_option('dbem_rss_description_format'); ?>" size="45" /><br />
							<?php _e('The format of the description of each item in the events RSS feed.','dbem')?>
							<?php _e('Follow the previous formatting instructions.','dbem')?><br/> 
						</td>
					</tr>
					
					
					
					
					
					
					<?php $gmap_is_active = get_option('dbem_gmap_is_active'); ?>
					 
				   	<tr valign="top">
				   		<th scope="row"><?php _e('Enable Google Maps integration?','dbem'); ?></th>
				   		<td>
							<input id="dbem_gmap_is_active_yes" name="dbem_gmap_is_active" type="radio" value="1" <?php if($gmap_is_active) echo "checked='checked'"; ?> /><?php _e('Yes'); ?> <br />
							<input name="dbem_gmap_is_active" type="radio" value="0" <?php if(!$gmap_is_active) echo "checked='checked'"; ?> /> <?php _e('No'); ?>  <br />
							<?php _e('Check this option to enable Goggle Map integration.','dbem')?>
						</td>
				   	</tr>
						
				   	<tr valign="top">
						<th scope="row"><?php _e('Google Maps API Key','dbem'); ?></th>
						<td>
							<input name="dbem_gmap_key" type="text" id="dbem_gmap_key" style="width: 95%" value="<?php echo get_option('dbem_gmap_key'); ?>" size="45" /><br />
									<?php _e("To display Google Maps you need a Google Maps API key. Don't worry, it's free, you can get one", "dbem");?> <a href="http://code.google.com/apis/maps/signup.html"><?php _e("here",'dbem')?></a>.
						</td>
					</tr>
					<tr valign="top">
						<th scope="row"><?php _e('Map text format','dbem')?></th>
						<td><textarea name="dbem_map_text_format" id="dbem_map_text_format" rows="6" cols="60"><?php echo (get_option('dbem_map_text_format'));?></textarea><br/>
							<?php _e('The format the text appearing in the map cloud.','dbem')?><br/>
							<?php _e('Follow the previous formatting instructions.','dbem')?></td>
					</tr>
				</table>
				<p class="submit">
					<input type="submit" id="dbem_options_submit" name="Submit" value="<?php _e('Save Changes') ?>" />
				</p>
				<input type="hidden" name="action" value="update" />
				<input type="hidden" name="page_options" value="dbem_use_event_end, dbem_event_list_item_format,dbem_event_page_title_format,dbem_single_event_format,dbem_list_events_page,dbem_events_page_title, dbem_no_events_message,  dbem_gmap_is_active, dbem_rss_main_title, dbem_rss_main_description, dbem_rss_title_format, dbem_rss_description_format, dbem_gmap_key, dbem_map_text_format" />
				
				
			</form>
		</div> 
	<?php

    
}




//This is the content of the event page
function dbem_events_page_content() {
    
	global $wpdb;
	if (isset($_REQUEST['event_id']) && $_REQUEST['event_id'] != '') { 
		// single event page
		$event_ID=$_REQUEST['event_id'];
		$event= dbem_get_event($event_ID);
		$single_event_format = get_option('dbem_single_event_format');  
		$page_body = dbem_replace_placeholders($single_event_format."seats: "."#_SEATS"."<br/>Available seats: "."#_AVAILABLESEATS"."<br/>"."#_RSVP", $event);
	  return $page_body;
	} else { 
		// Multiple events page
		$stored_format = get_option('dbem_event_list_item_format');
		$events_body  =  dbem_get_events_list(10, "future", "ASC", $stored_format, $false);  
		return $events_body;       
		
	}
}                                                         

// filter function to call the event page when appropriate
function dbem_filter_events_page($data) {
	
	// $table_name = $wpdb->prefix .EVENTS_TBNAME;
	// 	$start = strpos($data, DBEM_PAGE);
	
	$is_events_post = (get_the_ID() == get_option('dbem_events_page'));
 	$events_page_id = get_option('dbem_events_page');      
    if (is_page($events_page_id) && $is_events_post) { 
	    return dbem_events_page_content();
	} else {
		return $data;
	} 
}
add_filter('the_content','dbem_filter_events_page');

function dbem_events_page_title($data) {
	$events_page_id = get_option('dbem_events_page');
	$events_page = get_page($events_page_id);
	$events_page_title = $events_page->post_title;      
    if (($data == $events_page_title) && (is_page($events_page_id))) {
	   	if (isset($_REQUEST['event_id']) && $_REQUEST['event_id'] != '') { 
			// single event page
			$event_ID=$_REQUEST['event_id'];
			$event= dbem_get_event($event_ID);
			$stored_page_title_format = get_option('dbem_event_page_title_format');
			$page_title = dbem_replace_placeholders($stored_page_title_format, $event);
			return $page_title;
			} else { 
			// Multiple events page
		  $page_title = get_option('dbem_events_page_title');  
			return $page_title;       

		}
	
	} else {
		return $data;
	}                
	
} 
// to make sure that in pages lists the title is dbem_events_page_title, and not overwritten by the previous filter
add_filter('the_title','dbem_events_page_title');  
add_filter('single_post_title','dbem_events_page_title');   

function dbem_filter_get_pages($data) {
	$output = array();
    $events_page_id = get_option('dbem_events_page');
	for ($i = 0; $i < count($data); ++$i) {  
		if ($data[$i]->ID == $events_page_id) {   
			$list_events_page = get_option('dbem_list_events_page');
			if($list_events_page) {
				$data[$i]->post_title = get_option('dbem_events_page_title')."&nbsp;";  
		    	$output[] = $data[$i];
			} 
		} else {
	    	$output[] = $data[$i];
	   }
   } 
   return $output; 
}                                  
add_filter('get_pages', 'dbem_filter_get_pages');
            


//
// TODO: ROBA NUOVA DA RIORDINARE
// ADMIN CSS for debug
function dbem_admin_css() {
	$css = "
	<style type='text/css'>
	.debug{
		color: green;
		background: #B7F98C;
		margin: 15px;
		padding: 10px;
		border: 1px solid #629948;
	}
	.switch-tab {
		background: #aaa;
		width: 100px;
		float: right;
		text-align: center;
		margin: 3px 1px 0 5px;
		padding: 2px;
	}
	.switch-tab a {
		color: #fff;
		text-decoration: none;
	}
	.switch-tab a:hover {
		color: #D54E21;
		
	} 
	#events-pagination {
		text-align: center; 
		
	}
	#events-pagination a {
		margin: 0 20px 0 20px;
		text-decoration: none;
		width: 80px;
		padding: 3px 0; 
		background: #FAF4B7;
		border: 1px solid #ccc;
		border-top: none;
	} 
	#new-event {
		float: left;
	 
	}
	</style>";
	echo $css;
}

add_action('admin_print_scripts','dbem_admin_css');

// exposed function, for theme  makers
function dbem_get_events_list($limit="3", $scope="future", $order="ASC", $format='', $display=true) {
	if ($scope == "") 
		$scope = "future";
	if ($order != "DESC") 
		$order = "ASC";	
	if ($format == '')
		$format = get_option('dbem_event_list_item_format');
	$events = dbem_get_events($limit, $scope, $order);
	$output = "";
	if (!empty($events)) {
	foreach ($events as $event){
	  //  $localised_date = mysql2date("j M Y", $event->event_time);

	 	
			
		$output .= dbem_replace_placeholders($format, $event);
	}
	} else {
		$output = "<li class='dbem-no-events'>".get_option('dbem_no_events_message')."</li>";
	}
	if ($display)
		echo $output;
	else
		return $output;
}  

function dbem_get_events_page($justurl=false) {
	$page_link = get_permalink(get_option("dbem_events_page")) ;
	if($justurl) {  
		echo $page_link;
	} else {
		$page_title = get_option("dbem_events_page_title") ;
		echo "<a href='$page_link' title='$page_title'>$page_title</a>";
	}
	
}       

// TEMPLATE TAGS

function dbem_are_events_available($scope="future") {
	if ($scope == "") 
		$scope = "future";
	$events = dbem_get_events(1, $scope);    
	
  	if (empty($events))
		return FALSE;
  	else 
		return TRUE  ;
}
	
// Returns true if the page in question is the events page
function dbem_is_events_page() {
	$events_page_id = get_option('dbem_events_page');
	return is_page($events_page_id);
}    

function dbem_is_single_event_page() {
	return  (dbem_is_events_page() && (isset($_REQUEST['event_id']) && $_REQUEST['event_id'] != '')) ;
}

function dbem_is_multiple_events_page() {
	return  (dbem_is_events_page() && !(isset($_REQUEST['event_id']) && $_REQUEST['event_id'] != '')) ;
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
			   $map_div = "<div id='event-map' style=' background: green;'></div>" ;
			} else {
				$map_div = "";
			}
		 	$event_string = str_replace($result, $map_div , $event_string );
		}
		if (preg_match('/#_RSVP/', $result)) {
		 	$rsvp_is_active = get_option('dbem_gmap_is_active'); 
			if (true) {
			   $rsvp_module .= dbem_rsvp_form();
			} else {
				$rsvp_module .= "";
			}
		 	$event_string = str_replace($result, $rsvp_module , $event_string );
		}
		if (preg_match('/#_AVAILABLESEATS/', $result)) {
		 	$rsvp_is_active = get_option('dbem_gmap_is_active'); 
			if (true) {
			   $availble_seats .= dbem_get_available_seats($event->event_id);
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
			$event_string = str_replace($result, "<a href='".get_permalink($events_page_id).$joiner."event_id=$event->event_id'  title='$event->event_name'>$event->event_name</a>" , $event_string );
		} 
		if (preg_match('/#_URL/', $result)) {
			$events_page_id = get_option('dbem_events_page');
			$event_page_link = get_permalink($events_page_id);
			if (stristr($event_page_link, "?"))
				$joiner = "&amp;";
			else
				$joiner = "?";
			$event_string = str_replace($result, get_permalink($events_page_id).$joiner."event_id=$event->event_id" , $event_string );
		}
	 	if (preg_match('/#_(NAME|VENUE|ADDRESS|TOWN|PROVINCE|NOTES|SEATS)/', $result)) {
		 	$field = "event_".ltrim(strtolower($result), "#_");
		 	$field_value = $event->{$field};
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
		// matches all PHP time placeholders for endtime
		if (preg_match('/^#@[dDjlNSwzWFmMntLoYyaABgGhHisueIOPTZcrU]$/', $result)) {
			$event_string = str_replace($result, mysql2date(ltrim($result, "#@"), $event->event_end_time), $event_string ); 
			
			// echo "_fine_, infatti result = $result e ".mysql2date(ltrim($result, "#"), $event->event_end_time);
			// 			echo "STRINGA: $event_string";
			// 		               
	 
		} 
		    
		
		// matches all PHP time placeholders
		if (preg_match('/^#[dDjlNSwzWFmMntLoYyaABgGhHisueIOPTZcrU]$/', $result)) {
			// echo "-inizio-";
			$event_string = str_replace($result, mysql2date(ltrim($result, "#"), $event->event_time),$event_string );  
			// echo $event_string;  
		}       
	}
	return $event_string;	
	
}
// main function querying the database event table
function dbem_get_events($limit="",$scope="future",$order="ASC", $offset="") {
	global $wpdb;
 	$events_table = $wpdb->prefix.EVENTS_TBNAME;
	if ($limit != "")
		$limit = "LIMIT $limit";
	if ($offset != "")
		$offset = "OFFSET $offset";  
		
	if (($scope != "past") && ($scope !="all"))
		$scope = "future";
	
	$timestamp = time();
	$date_time_array = getdate($timestamp);
   $hours = $date_time_array['hours'];
	$minutes = $date_time_array['minutes'];
	$seconds = $date_time_array['seconds'];
	$month = $date_time_array['mon'];
	$day = $date_time_array['mday'];
	$year = $date_time_array['year'];
 	$today = strftime('%Y-%m-%d 00:00:00', mktime($hours,$minutes,$seconds,$month,$day,$year));
	
	
 
	
	if ($scope == "future") 
		$temporal_condition = "WHERE event_time >= '$today'" ;
	if ($scope == "past") 
		$temporal_condition = "WHERE event_time < '$today'" ;
	if ($scope == "all")
		$temporal_condition = "";
	
	$sql = "SELECT event_id, 
			    event_name, 
			  	event_venue, 
			  	event_address,
			  	event_town, 
			  	event_province, 
			  	DATE_FORMAT(event_time, '%e') AS 'event_day',
			  	DATE_FORMAT(event_time, '%Y') AS 'event_year',
			  	DATE_FORMAT(event_time, '%k') AS 'event_hh',
			  	DATE_FORMAT(event_time, '%i') AS 'event_mm',
					DATE_FORMAT(event_end_time, '%e') AS 'event_end_day',
			  	DATE_FORMAT(event_end_time, '%Y') AS 'event_end_year',
			  	DATE_FORMAT(event_end_time, '%k') AS 'event_end_hh',
			  	DATE_FORMAT(event_end_time, '%i') AS 'event_end_mm',
			  	event_time,
					event_end_time,
			  	event_latitude,
			  	event_longitude,
					event_notes, 
					event_rsvp 
					FROM $events_table   
					$temporal_condition
					ORDER BY event_time $order
					$limit 
					$offset";   
	     
 
	$events = $wpdb->get_results($sql);	
	   
	return $events;
}    
function dbem_get_event($event_id) {
	global $wpdb;
	$events_table = $wpdb->prefix.EVENTS_TBNAME; 	
	$sql = "SELECT event_id, 
			   	event_name, 
			  	event_venue, 
			  	event_address,
			  	event_town, 
			  	event_province, 
			  	DATE_FORMAT(event_time, '%e') AS 'event_day',
			  	DATE_FORMAT(event_time, '%Y') AS 'event_year',
			  	DATE_FORMAT(event_time, '%k') AS 'event_hh',
			  	DATE_FORMAT(event_time, '%i') AS 'event_mm', 
			    DATE_FORMAT(event_end_time, '%e') AS 'event_end_day',
		  		DATE_FORMAT(event_end_time, '%Y') AS 'event_end_year',
		  		DATE_FORMAT(event_end_time, '%k') AS 'event_end_hh',
		  		DATE_FORMAT(event_end_time, '%i') AS 'event_end_mm',
			  	event_time,
			  	event_end_time,
					event_latitude,
			  	event_longitude,
					event_notes,
					event_rsvp,
					event_seats
				FROM $events_table   
			    WHERE event_id = $event_id";   
	     
	
	$event = $wpdb->get_row($sql);	
	   
	return $event;
}        


function dbem_events_table($events, $limit, $title) {
 
		
	if (isset($_GET['scope'])) 
		$scope = $_GET['scope'];
 	else
		$scope = "future";
	if (($scope  != "past") && ($scope != "all"))
		$scope = "future";
	$events_count = count(dbem_get_events("",$scope));
	
	if (isset($_GET['offset'])) 
		$offset = $_GET['offset'];
	
	$use_events_end = get_option('dbem_use_event_end');
		
	?>
	 
	<div class="wrap">
		  
		 <h2><?php echo $title; ?></h2>   
  	<div id='new-event' class='switch-tab'><a href="<?php bloginfo('wpurl')?>/wp-admin/edit.php?page=eventmanager.php&amp;action=edit_event"><?php _e('New Event ...', 'dbem');?></a></div>  
		<?php
			
			$link = array();
			$link['past'] = "<a href='".get_bloginfo('url')."/wp-admin/edit.php?page=eventmanager.php&amp;scope=past&amp;order=desc'>".__('Past events','dbem')."</a>"; 
			$link['all'] = " <a href='".get_bloginfo('url')."/wp-admin/edit.php?page=eventmanager.php&amp;scope=all&amp;order=desc'>".__('All events','dbem')."</a>";   
			$link['future'] = "  <a href='".get_bloginfo('url')."/wp-admin/edit.php?page=eventmanager.php&amp;scope=future'>".__('Future events','dbem')."</a>";
			foreach ($link as $key => $value) {
				if ($key != $scope) 
					echo "<div class='switch-tab'>".$link[$key]."</div>"; 
			} ?> 
		
  	
	<table class="widefat">
  		<thead>
			<tr>
  				<th><?php _e('ID', 'dbem');?></th>
  	  			<th><?php _e('Name', 'dbem');?></th>
  	   		<th><?php _e('Venue', 'dbem');?></th>
  	   		<th><?php _e('Town', 'dbem');?></th>
	   		<th><?php _e('Address', 'dbem');?></th>
  	    <?php if (!$use_events_end) {?>
				<th colspan="2"><?php _e('Date and time', 'dbem');?></th>
  	    <?php } else {?>
				<th colspan="2"><?php _e('Beginning', 'dbem');?></th>
				<th colspan="2"><?php _e('End', 'dbem');?></th>
		 <?php } ?>
				<th><?php _e('Time', 'dbem');?></th>
	   		 	<?php if (false) { ?>
						<th><?php _e('Latitude', 'dbem');?></th>
	   				<th><?php _e('Longitude', 'dbem');?></th> 
					<?php } ?>
  	   		<th colspan="2"><?php _e('Actions', 'dbem');?></th>
  	  </tr>
			</thead>
			<tbody>
  	  <?php
  		$i =1;
  		foreach ($events as $event){
  			$class = ($i % 2) ? ' class="alternate"' : '';
				$month = mysql2date('M', $event->event_time);
				$weekday = mysql2date('D', $event->event_time); 
				$end_month = mysql2date('M', $event->event_end_time);
				$end_weekday = mysql2date('D', $event->event_end_time);
				$style = "";
			   $timestamp = time();
				$date_time_array = getdate($timestamp);
			  	$this_hours = $date_time_array['hours'];
				$this_minutes = $date_time_array['minutes'];
				$this_seconds = $date_time_array['seconds'];
				$this_month = $date_time_array['mon'];
				$this_day = $date_time_array['mday'];
				$this_year = $date_time_array['year'];
			 	$today = strftime('%Y-%m-%d 00:00:00', mktime($this_hours,$this_minutes,$this_seconds,$this_month,$this_day,$this_year));
			
			
				if ($event->event_time < $today )
				$style= "style ='background-color: #FADDB7;'";
			?>
	  <tr <?php echo"$class $style"; ?> >
  	   <td>
 	    <strong><?php echo "$event->event_id"; ?></strong>
  	   </td>
  	   <td>
  	    <?php echo "$event->event_name"; ?>
  	   </td>
  	   <td>
  	    <?php echo "$event->event_venue"; ?>
  	   </td>
  	   <td>
  	    <?php 
  	     	echo "$event->event_town"; 
  		if (isset($event->event_province)) {
  		echo " ($event->event_province)";
  		}
  	      ?>
  	    </td>
  	    <td>
	  	    <?php echo "$event->event_address"; ?>
	  	</td>
		<td>
  	     <?php echo "$weekday $event->event_day  $month $event->event_year"; ?>
  	    </td>
  	    <td>
  	     <?php echo "$event->event_hh : $event->event_mm"; ?>
  	    </td>
		 <?php if ($use_events_end) { ?>
		  <td>
			<?php echo "$end_weekday $event->event_end_day  $end_month $event->event_end_year"; ?> 
		  </td>
		  <td>   
			<?php echo "$event->event_end_hh : $event->event_end_mm"; ?>   
			</td>
		 <?php } ?>
		  <?php if (false) { ?> 
			<td>
	  	    <?php echo "$event->event_latitude"; ?>
	  	</td>
		 <td>
	  	    <?php echo "$event->event_longitude"; ?>
	  	</td>
			<?php } ?> 
	    <td><a class="edit" href="<?php bloginfo('wpurl')?>/wp-admin/edit.php?page=events-manager/events-manager.php&amp;action=edit_event&amp;event_id=<?php echo "$event->event_id"?>"><?php _e('Edit'); ?></a></td>
	    <td><a class="delete" href="<?php bloginfo('wpurl')?>/wp-admin/edit.php?page=eventmanager.php&amp;action=dbem_delete&amp;event_id=<?php echo "$event->event_id" ?>" onclick="return confirm('<?php _e('Are you sure?','dbem'); ?>');"><?php _e('Delete'); ?></a></td>
	<?php
  	    	   echo'</tr>'; 
  	   $i++;
		}
	    ?>
	   
			</tbody>
  	  </table>
 			<?php
 			if ($events_count >  $limit) {
				$backward = $offset + $limit;
				$forward = $offset - $limit; 
				if (DEBUG)
			 		echo "COUNT = $count BACKWARD = $backward  FORWARD = $forward<br> -- OFFSET = $offset" ; 
				echo "<div id='events-pagination'> ";
				if ($backward < $events_count)
					echo "<a style='float: left' href='".get_bloginfo('url')."/wp-admin/edit.php?page=eventmanager.php&amp;scope=$scope&offset=$backward'>&lt;&lt;</a>" ;
				if ($forward >= 0)
					echo "<a style='float: right' href='".get_bloginfo('url')."/wp-admin/edit.php?page=eventmanager.php&amp;scope=$scope&offset=$forward'>&gt;&gt;</a>";
		    echo "</div>" ;
		}
			?>
			
	</div>
<?php
}
function dbem_event_form($event, $title, $element) { 
	global $localised_date_formats;
	$locale_code = substr(get_locale(), 0, 2); 
	$localised_date_format = $localised_date_formats[$locale_code];
	$localised_example = str_replace("yy", "2008", str_replace("mm", "11", str_replace("dd", "27", $localised_date_format))); 
	$localised_end_example = str_replace("yy", "2008", str_replace("mm", "11", str_replace("dd", "28", $localised_date_format)));
	
	if ($event['event_date'] != "") {
		preg_match("/(\d{4})-(\d{2})-(\d{2})/",$event['event_date'], $matches );
		$year = $matches[1];
		$month = $matches[2];
		$day = $matches[3]; 
		$localised_date = str_replace("yy", $year, str_replace("mm", $month, str_replace("dd", $day, $localised_date_format)));  
	} else {
		$localised_date = "";
	}
	if ($event['event_end_date'] != "") {  
		preg_match("/(\d{4})-(\d{2})-(\d{2})/",$event['event_end_date'], $matches );
		$end_year = $matches[1];
		$end_month = $matches[2];
		$end_day = $matches[3];
	   $localised_end_date = str_replace("yy", $end_year, str_replace("mm", $end_month, str_replace("dd", $end_day, $localised_date_format)));
	} else {
		$localised_end_date = "";
	}    
	echo (dbem_bookings_table($event['event_id']));   
	?> 
<form id="eventForm" method="post" action="edit.php?page=eventmanager.php&amp;action=update_event&amp;event_id=<?php echo "$element"?>">
    <div class="wrap">
		<h2><?php echo $title; ?></h2>
   		<div id="poststuff">
        	<div id="postbody">
				<div id="event_name" class="stuffbox">
					<h3><?php _e('Name','dbem'); ?></h3>
					<div class="inside">
						<input type="text" name="event_name" value="<?php echo $event['event_name'] ?>" /><br/>
						<?php _e('The event name. Example: Birthday party', 'dbem') ?>
					</div>
				</div>
				
				
				<div id="event_day" class="stuffbox">
					<h3><?php _e('Day and Time','dbem'); ?></h3>  
					<div class="inside">
						<input id="localised-date" type="text" name="localised_event_date" value="<?php echo $localised_date ?>" style ="display: none;" />
			  			<input id="date-to-submit" type="text" name="event_date" value="<?php echo $event['event_date'] ?>" style="background: #FCFFAA" /> - <input type="text" size="3" maxlength="2" name="event_hh" value="<?php echo $event['event_hh'] ?>" /> : <input type="text" size="3" maxlength="2" name="event_mm" value="<?php echo $event['event_mm'] ?>" /><br/>
						<?php _e('The event day and time', 'dbem') ?>. <span id="localised_example" style ="display: none;"><?php echo __("Example:", "dbem")." $localised_end_example"; ?></span><span id="no-javascript-example"><?php _e("Example: 2008-11-28", "dbem")?></span> - 20:30
						
					</div>
				</div>   
				
				<?php $use_events_end = get_option('dbem_use_event_end'); ?>
				<?php if($use_events_end) { ?>
					 <div id="event_end_day" class="stuffbox">
							<h3><?php _e('End day and Time','dbem'); ?></h3>  
							<div class="inside">
								<input id="localised-end-date" type="text" name="localised_event_end_date" value="<?php echo $localised_end_date ?>" style ="display: none; " /><input id="end-date-to-submit" type="text" name="event_end_date" value="<?php echo $event['event_end_date'] ?>" style="background: #FCFFAA" /> - <input type="text" size="3" maxlength="2" name="event_end_hh" value="<?php echo $event['event_end_hh'] ?>" /> : <input type="text" size="3" maxlength="2" name="event_end_mm" value="<?php echo $event['event_end_mm'] ?>" /><br/>
								<?php _e('The day and time of the event end', 'dbem') ?>. <span id="localised_end_example" style ="display: none;"><?php echo __("Example:", "dbem")." $localised_example"; ?></span><span id="no-javascript-example"><?php _e("Example: 2008-11-27", "dbem")?></span> - 20:30

							</div>
					 </div>
				
				
				
				<?php } ?>
						
			    <?php
				$gmap_is_active = get_option('dbem_gmap_is_active'); 
				if ($gmap_is_active) {
			 		echo "<div id='map-not-found' style='width: 450px; float: right; font-size: 140%; text-align: center; margin-top: 100px; display: hide'><p>".__('Map not found')."</p></div>";
				echo "<div id='event-map' style='width: 450px; height: 300px; background: green; float: right; display: hide; margin-right:8px'></div>";   
			}
				?>
				<div id="event_venue" class="stuffbox">
					<h3><?php _e('Venue','dbem'); ?></h3>
					<div class="inside">
						<input id="venue-input" type="text" name="event_venue" value="<?php echo $event['event_venue']?>" /><br/>
						<?php _e('The venue where the event takes place. Example: Arena', 'dbem') ?>
					</div>
				</div>
				<div id="event_address" class="stuffbox">
					<h3><?php _e('Address','dbem'); ?></h3>
					<div class="inside">
						<input id="address-input" type="text" name="event_address" value="<?php echo $event['event_address']; ?>" /><br/>
						<?php _e('The address of the venue. Example: Via Mazzini 22', 'dbem') ?>
					</div>
				</div>
				<div id="event_town" class="stuffbox">
					<h3><?php _e('Town','dbem'); ?></h3>
					<div class="inside">
						<input id="town-input" type="text" name="event_town" value="<?php echo $event['event_town']?>" /><br/>
						<?php _e('The event town. Example: Verona. If you\'re using the Google Map integration and want to avoid geotagging ambiguities include the country as well. Example: Verona, Italy', 'dbem') ?>
					</div>
				</div>
				<div id="event_notes" class="postbox closed">
					<h3><?php _e('Notes','dbem'); ?></h3>
					<div class="inside">
						<textarea name="event_notes" rows="8" cols="60"><?php echo $event['event_notes']; ?></textarea><br/>
						<?php _e('Notes about the event', 'dbem') ?>
					</div>
				</div>
			</div>
		</div>
	   <p class="submit"><input type="submit" name="events_update" value="<?php _e('Submit Event','dbem'); ?> &raquo;" /></p>
	</div>
</form>
 <?php
}            

function dbem_validate_event($event) {
	// TODO decide which fields are required
	// Implement type check for dates, etc
	global $required_fields;
	
	foreach ($required_fields as $field) {
		if ($event[$field] == "" ) {
		return $field.__(" is missing!", "dbem");
		}       
	}
	
	$event_year = substr($event['event_date'],0,4);
	$event_month = substr($event['event_date'],5,2);
	$event_day = substr($event['event_date'],8,2);
	
	if (!_dbem_is_date_valid($event['event_date'])) {
		return __("invalid date!", "dbem");
	}                                                         
	$time = $event['event_hh'].":".$event['event_mm'];
	$end_time = $event['event_end_hh'].":".$event['event_end_mm']; 
	if ( !_dbem_is_time_valid($time) ) {
		return _("invalid time","dbem");
	}
	$use_event_end = get_option('dbem_use_event_end'); 
	if ($use_event_end && $event['event_date']." ".$time  > $event['event_end_date']." ".$end_time)
		return __("end date before begin date", "dbem");
	return "OK";
	
}

function _dbem_is_date_valid($date) {
	$year = substr($date,0,4);
	$month = substr($date,5,2);
	$day = substr($date,8,2);
	return (checkdate($month, $day, $year));
}  
function _dbem_is_time_valid($time) {  
	echo "Time validation: _".$time."_<br/>";
  	$result = preg_match ("/([01]\d|2[0-3])(:[0-5]\d)/", $time);
	echo "$result<br/>";
	return ($result);
}
// Enqueing jQuery script to make sure it's loaded
function dbem_enque_scripts(){ 
	wp_enqueue_script( 'jquery' );     
	// wp_enqueue_script('datepicker','/wp-content/plugins/events-manager/jquery-ui-datepicker/jquery-ui-personalized-1.6b.js', array('jquery') );
}
add_action ('template_redirect', 'dbem_enque_scripts');

function url_exists($url) {
     if ((strpos($url, "http")) === false) $url = "http://" . $url;
     if (is_array(@get_headers($url)))
          return true;
     else
          return false;
}

// General script to make sure hidden fields are shown when containing data
function dbem_admin_general_script(){  ?>
	<script src="<?php bloginfo('url');?>/wp-content/plugins/events-manager/js/ui.datepicker.js" type="text/javascript"></script>
   
	<?php
	// Check if the locale is there and loads it
	$locale_code = substr(get_locale(), 0, 2);   
	$locale_file = get_bloginfo('url')."/wp-content/plugins/events-manager/js/i18n/ui.datepicker-$locale_code.js";
	// echo $locale_file;
	if(url_exists($locale_file)) {   ?>
	  <script src="<?php bloginfo('url');?>/wp-content/plugins/events-manager/js/i18n/ui.datepicker-<?php echo $locale_code;?>.js" type="text/javascript"></script>   
	<?php } ?>                 
	
	
	<style type='text/css' media='all'>
		@import "<?php bloginfo('url');?>/wp-content/plugins/events-manager/js/ui.datepicker.css";
	</style>
	<script type="text/javascript">
 	//<![CDATA[        
   // TODO: make more general, to support also latitude and longitude (when added)
		$j=jQuery.noConflict();   
		$j(document).ready( function() {
			locale_format = "ciao";
			$j("#localised_example").show();
			$j("#no-javascript-example").hide();
			$j("#localised-date").show();
			$j("#localised-end-date").show(); 
			$j("#date-to-submit").hide();
			$j("#end-date-to-submit").hide(); 
			$j("#localised-date").datepicker($j.extend({},
		  		($j.datepicker.regional["it"], 
				{altField: "#date-to-submit", 
				altFormat: "yy-mm-dd"})));
	 	  $j("#localised-end-date").datepicker($j.extend({},
		  		($j.datepicker.regional["it"], 
		  		{altField: "#end-date-to-submit", 
		  		altFormat: "yy-mm-dd"})));
			
			
			
			
			
			
	    	jQuery('.postbox h3').prepend('<a class="togbox">+</a> ');
	    	if(jQuery("textarea[@name=event_notes]").val()!="") {
			   jQuery("textarea[@name=event_notes]").parent().parent().removeClass('closed');
			}
			jQuery('#event_notes h3').click( function() {
	        	jQuery(jQuery(this).parent().get(0)).toggleClass('closed');
	    	});  
	
		
		});
		//]]>
	</script>
	  
<?php	
}
add_action ('admin_head', 'dbem_admin_general_script');                


// Google maps implementation
function dbem_map_script() {
	$gmap_is_active = get_option('dbem_gmap_is_active'); 
	if ($gmap_is_active) {
		if (strpos(get_option('dbem_single_event_format'), "#_MAP")) { // loading the script is useless unless #_MAP is in the format
			if (isset($_REQUEST['event_id']) && $_REQUEST['event_id'] != '') { 
				// single event page
				$event_ID=$_REQUEST['event_id'];
			    $event = dbem_get_event($event_ID);
				if ($event->event_town != '') {
					$gmap_key = get_option('dbem_gmap_key'); 
					if($event->event_address != "") {
				    	$search_key = "$event->event_address, $event->event_town";
					} else {
						$search_key = "$event->event_venue, $event->event_town";
					}
				$map_text_format = get_option('dbem_map_text_format');
		    	$map_text = dbem_replace_placeholders($map_text_format, $event, "map");   
                
			?> 
   
	
			<script src="http://maps.google.com/maps?file=api&amp;v=2&amp;key=<?php echo $gmap_key;?>" type="text/javascript"></script>         
			<script type="text/javascript">
			    //<![CDATA[
				$j=jQuery.noConflict();
			    function loadMap() {
		      		if (GBrowserIsCompatible()) {
		        		var map = new GMap2(document.getElementById("event-map"));
		        		var mapTypeControl = new GLargeMapControl();
								var topRight = new GControlPosition(G_ANCHOR_TOP_RIGHT, new GSize(10,10));
							 	map.addControl(mapTypeControl, topRight);
								
							 // map.addControl(new GLargeMapControl()); 
								//map.setCenter(new GLatLng(37.4419, -122.1419), 13);   
						var geocoder = new GClientGeocoder();
						var address = "<?php echo $search_key ?>" ;
						geocoder.getLatLng(
						    address,
						    function(point) {
						      if (!point) {
						       	$j("#event-map").hide();
						      } else {
								mapCenter= new GLatLng(point.lat()+0.01, point.lng()+0.005);
						        map.setCenter(mapCenter, 13);
						        var marker = new GMarker(point);
						        map.addOverlay(marker);
						        marker.openInfoWindowHtml('<?php echo $map_text;?>');
						      }
						    }
						  );   
		      		}
		    	}
   
				$j(document).ready(function() {
		  		if ($j("#event-map").length > 0 ) {	
						loadMap();  
			      }
		 
			   }); 
			   $j(document).unload(function() {
						if ($j("#event-map").length > 0 ) {	 
							GUnload();
						}
			   });
			//]]>
			</script> 
			<style type="text/css">
			#event-map {
				width: 450px; 
				height: 300px;
			}
			#event-map img {
				background: transparent;
			}
			</style>
		<?php
		 		}
			} 
		}
	}
}        
add_action ('wp_head', 'dbem_map_script');   

function dbem_admin_map_script() { 
	if (isset($_REQUEST['event_id']) && $_REQUEST['event_id'] != '') { 
		if (!(isset($_REQUEST['action']) && $_REQUEST['action'] == 'dbem_delete')) {    
			// single event page
			$event_ID=$_REQUEST['event_id'];
		    $event = dbem_get_event($event_ID);
			if ($event->event_town != '') {
				$gmap_key = get_option('dbem_gmap_key');
		        if($event->event_address != "") {
			    	$search_key = "$event->event_address, $event->event_town";
				} else {
					$search_key = "$event->event_venue, $event->event_town";
				}
	
		?>
		<style type="text/css">
		   div#event_town, div#event_address, div#event_venue {
			width: 480px;
		}
		</style>
		<script src="http://maps.google.com/maps?file=api&amp;v=2&amp;key=<?php echo $gmap_key;?>" type="text/javascript"></script>         
		<script type="text/javascript">
			//<![CDATA[
		   	$j=jQuery.noConflict();
		
			function loadMap(venue, town, address) {
	      		if (GBrowserIsCompatible()) {
	        		var map = new GMap2(document.getElementById("event-map"));
	        	//	map.addControl(new GScaleControl()); 
							//map.setCenter(new GLatLng(37.4419, -122.1419), 13);   
					var geocoder = new GClientGeocoder();
					if (address !="") {
						searchKey = address + ", " + town;
					} else {
						searchKey =  venue + ", " * town;
					}
					
					var search = "<?php echo $search_key ?>" ;
					geocoder.getLatLng(
					    searchKey,
					    function(point) {
					      if (!point) {
					       	$j("#event-map").hide();
							$j('#map-not-found').show();
					      } else {
							mapCenter= new GLatLng(point.lat()+0.01, point.lng()+0.005);
						    map.setCenter(mapCenter, 13);
					        var marker = new GMarker(point);
					        map.addOverlay(marker);
					        marker.openInfoWindowHtml('<strong>' + venue +'</strong><p>' + address + '</p><p>' + town + '</p>');
					        $j("#event-map").show();
							$j('#map-not-found').hide();
							}
					    }
					  );   
	      	 //	map.addControl(new GSmallMapControl());
					 // map.addControl(new GMapTypeControl());
					
					 }
	    	}
   
			$j(document).ready(function() {
	  			eventVenue = $j("#venue-input").val(); 
			
	 			eventTown = $j("#town-input").val(); 
				eventAddress = $j("#address-input").val();
		   
				loadMap(eventVenue, eventTown, eventAddress);
			
				$j("#venue-input").blur(function(){
						newEventVenue = $j("#venue-input").val();  
						if (newEventVenue !=eventVenue) {                
							loadMap(newEventVenue, eventTown, eventAddress); 
							eventVenue = newEventVenue;
					   
						}
				});
				$j("#town-input").blur(function(){
						newEventTown = $j("#town-input").val(); 
						if (newEventTown !=eventTown) {  
							loadMap(eventVenue, newEventTown, eventAddress); 
							eventTown = newEventTown;
							} 
				});
				$j("#address-input").blur(function(){
						newEventAddress = $j("#address-input").val(); 
						if (newEventAddress != eventAddress) {
							loadMap(eventVenue, eventTown, newEventAddress);
						 	eventAddress = newEventAddress; 
						}
				});
			  
			
		 
		   }); 
		   $j(document).unload(function() {
				GUnload();
		   });
		    //]]>
		</script>
	<?php
	 		}
		}
}    
}
$gmap_is_active = get_option('dbem_gmap_is_active'); 
if ($gmap_is_active)        
	add_action ('admin_head', 'dbem_admin_map_script');

// Script to validate map options
function dbem_admin_options_script() { 
	if (isset($_REQUEST['page']) && $_REQUEST['page'] == 'events-manager/events-manager.php') {  
	   ?>
	<script type="text/javascript">
	//<![CDATA[
		$j=jQuery.noConflict();
		
		 $j(document).ready(function() {
	  		    // users cannot enable Googlr Maps without an api key
				function verifyOptionsForm(){
				   	var gmap_is_active = $j("input[@name=dbem_gmap_is_active]:checked").val();
						var gmap_key = $j("input[@name=dbem_gmap_key]").val();
				  	if(gmap_is_active == '1' && (gmap_key == '')){
					    alert("<?php _e('You cannot enable Google Maps integration without setting an appropriate API key.');?>");
							$j("input[@name='dbem_gmap_is_active']:nth(1)").attr("checked","checked");
					
						return false;
					} else {
						return true;
					}
				}
				
        $j('#dbem_options_form').bind("submit", verifyOptionsForm);


		   });
	
		
		//]]>
	</script>
	
	<?php
		
	}
	
}
add_action ('admin_head', 'dbem_admin_options_script');   

function dbem_rss_link($justurl=false) {
	$rss_title = get_option('dbem_events_page_title');
	$url = get_bloginfo('url')."/?dbem_rss=main";  
	$link = "<a href='$url'>RSS</a>";
	if ($justurl)
		echo $url;
	else
		echo $link;
}  

function dbem_rss() {
	if (isset($_REQUEST['dbem_rss']) && $_REQUEST['dbem_rss'] == 'main') {	
		header("Content-type: text/xml");
		echo "<?xml version='1.0'?>\n";
		
		$events_page_id = get_option('dbem_events_page');
		$events_page_link = get_permalink($events_page_id);
		if (stristr($events_page_link, "?"))
		  $joiner = "&amp;";
		else
			$joiner = "?";
		
		
		?>
		<rss version="2.0">
		<channel>
		<title><?php echo get_option('dbem_rss_main_title');?></title>
    <link><?php echo $events_page_link; ?></link>
    <description><?php echo get_option('dbem_rss_main_description');?></description>
    <docs>http://blogs.law.harvard.edu/tech/rss</docs>
    <generator>Weblog Editor 2.0</generator>
    	<?php
			$title_format = get_option('dbem_rss_title_format');
			$description_format = str_replace(">","&gt;",str_replace("<","&lt;",get_option('dbem_rss_description_format')));
			$events = dbem_get_events(5);
			foreach ($events as $event) {
				$title = dbem_replace_placeholders($title_format, $event, "rss");
        		$description = dbem_replace_placeholders($description_format, $event, "rss");
				echo "<item>";   
				echo "<title>$title</title>\n";
				echo "<link>$events_page_link".$joiner."event_id=$event->event_id</link>\n ";
				echo "<description>$description </description>\n";
				echo "</item>";
      }
			?>

			</channel>
			</rss>
		</code>
	
	<?php
	die();
	}
}
add_action('init','dbem_rss');
function substitute_rss($data) {
	if (isset($_REQUEST['event_id'])) 
		return get_bloginfo('url')."/?dbem_rss=main";
	else
		return $data;
}           
function dbem_general_css() {  
	$base_url = get_bloginfo('url');
	echo "<link rel='stylesheet' href='$base_url/wp-content/plugins/events-manager/events_manager.css' type='text/css'/>";
	
}
add_action('wp_head','dbem_general_css');
add_action('admin_head','dbem_general_css');
//add_filter('feed_link','substitute_rss')
include("dbem_venues_autocomplete.php"); 
include("dbem_rsvp.php");     
include("dbem_venues.php");     


?>