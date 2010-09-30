<?php
/*
Plugin Name: Events Manager
Version: 2.3
Plugin URI: http://davidebenini.it/wordpress-plugins/events-manager/
Description: Manage events specifying precise spatial data (Location, Town, Province, etc).
Author: Davide Benini, Marcus Sykes
Author URI: http://www.davidebenini.it/blog
*/

/*
Copyright (c) 2010, Davide Benini and Marcus Sykes

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

/*
 * Some random notes
 * 
 * - for consistency and easier modifications I propose we use a common coding practice Davide... ieally, $event and $EM_Event should always be only variables used to reference an EM_Event object, no others. 
 * $EM_Event is a global variable, $event has a local scope. same for $events and $EM_Events, they should be an array of EM_Event objects. what do u think?
 * - Would be cool for functions where we pass a reference for something (event, location, category, etc) it could be either the ID, or the object itself. Makes things flexible, not hard to implement, am doing it already (see EM_Events::get) 
 */
//Features
//TODO Better data validation, both into database and outputting info (http://codex.wordpress.org/Data_Validation)
//Known Bugs
//FIXME admin panel showing future events shows event of day before
//FIXME when saving event, select screen has reversed order of events


// INCLUDES 
include_once('classes/object.php'); //Base object, any files below may depend on this 
//Template Tags & Template Logic
include_once("ajax.php");
include_once("events.php");
include_once("locations.php");
include_once("bookings.php");
include_once("functions.php");
include_once("shortcode.php");
include_once("template-tags.php");
include_once("template-tags-depreciated.php"); //To depreciate
include_once("includes/phpmailer/dbem_phpmailer.php") ;
//Widgets
include_once("widgets/events.php");
include_once("widgets/calendar.php");
//Classes
include_once('classes/booking.php');
include_once('classes/bookings.php');
include_once('classes/calendar.php');
include_once('classes/category.php');
include_once('classes/event.php');
include_once('classes/events.php');
include_once('classes/location.php');
include_once('classes/locations.php');
include_once('classes/map.php');
include_once('classes/people.php');
include_once('classes/person.php');
//Admin Files
if( is_admin() ){
	include_once('admin/admin.php');
	include_once('admin/bookings.php');
	include_once('admin/categories.php');
	include_once('admin/event.php');
	include_once('admin/events.php');
	include_once('admin/locations.php');
	include_once('admin/functions.php');
	include_once('admin/options.php');
	include_once('admin/people.php');
}


// Setting constants
define('EM_VERSION', 2.3); //self expanatory
define('DBEM_CATEGORIES_TBNAME', 'em_categories'); //TABLE NAME
define('EVENTS_TBNAME','em_events'); //TABLE NAME
define('RECURRENCE_TBNAME','dbem_recurrence'); //TABLE NAME   
define('LOCATIONS_TBNAME','em_locations'); //TABLE NAME  
define('BOOKINGS_TBNAME','em_bookings'); //TABLE NAME
define('PEOPLE_TBNAME','em_people'); //TABLE NAME  
define('BOOKING_PEOPLE_TBNAME','em_bookings_people'); //TABLE NAME  
define('DEFAULT_EVENT_PAGE_NAME', 'Events');   
define('DBEM_PAGE','<!--DBEM_EVENTS_PAGE-->'); //EVENTS PAGE
define('MIN_CAPABILITY', 'edit_posts');	// Minimum user level to access calendars
define('SETTING_CAPABILITY', 'activate_plugins');	// Minimum user level to access calendars
define('DEFAULT_LIST_DATE_TITLE', __('Events', 'dbem').' - #j #M #y');
define('DEFAULT_EVENT_LIST_ITEM_FORMAT', '<li>#j #M #Y - #H:#i<br/> #_LINKEDNAME<br/>#_TOWN </li>');
define('DEFAULT_SINGLE_EVENT_FORMAT', '<p>#j #M #Y - #H:#i</p><p>#_TOWN</p>'); 
define('DEFAULT_EVENTS_PAGE_TITLE',__('Events','dbem') ) ;
define('DEFAULT_EVENT_PAGE_TITLE_FORMAT', '#_NAME'); 
define('DEFAULT_RSS_DESCRIPTION_FORMAT',"#j #M #y - #H:#i <br/>#_LOCATION <br/>#_ADDRESS <br/>#_TOWN");
define('DEFAULT_RSS_TITLE_FORMAT',"#_NAME");
define('DEFAULT_MAP_TEXT_FORMAT', '<strong>#_LOCATION</strong><p>#_ADDRESS</p><p>#_TOWN</p>');     
define('DEFAULT_WIDGET_EVENT_LIST_ITEM_FORMAT','<li>#_LINKEDNAME<ul><li>#j #M #y</li><li>#_TOWN</li></ul></li>');
define('DEFAULT_NO_EVENTS_MESSAGE', __('No events', 'dbem'));  
define('DEFAULT_SINGLE_LOCATION_FORMAT', '<p>#_ADDRESS</p><p>#_TOWN</p>'); 
define('DEFAULT_LOCATION_PAGE_TITLE_FORMAT', '#_NAME'); 
define('DEFAULT_LOCATION_BALOON_FORMAT', "<strong>#_NAME</strong><br/>#_ADDRESS - #_TOWN<br/><a href='#_LOCATIONPAGEURL'>Details</a>");
define('DEFAULT_LOCATION_EVENT_LIST_ITEM_FORMAT', "<li>#_NAME - #j #M #Y - #H:#i</li>");
define('DEFAULT_LOCATION_NO_EVENTS_MESSAGE', __('<li>No events in this location</li>', 'dbem'));
define("IMAGE_UPLOAD_DIR", "wp-content/uploads/locations-pics");
define('DEFAULT_IMAGE_MAX_WIDTH', 700);  
define('DEFAULT_IMAGE_MAX_HEIGHT', 700);  
define('DEFAULT_IMAGE_MAX_SIZE', 204800); 
define('DEFAULT_FULL_CALENDAR_EVENT_FORMAT', '<li>#_LINKEDNAME</li>');    
define('DEFAULT_SMALL_CALENDAR_EVENT_TITLE_FORMAT', "#_NAME" );
define('DEFAULT_SMALL_CALENDAR_EVENT_TITLE_SEPARATOR', ", ");  
define('DEFAULT_USE_SELECT_FOR_LOCATIONS', false);      
define('DEFAULT_ATTRIBUTES_ENABLED', false);
define('DEFAULT_RECURRENCE_ENABLED', false);
define('DEFAULT_RSVP_ENABLED', false);
define('DEFAULT_CATEGORIES_ENABLED', false);
       
// obsolete tables
define('OLD_EVENTS_TBNAME','dbem_events') ; 
define('OLD_LOCATIONS_TBNAME','dbem_locations'); //TABLE NAME  
define('OLD_BOOKINGS_TBNAME','dbem_bookings'); //TABLE NAME
define('OLD_PEOPLE_TBNAME','dbem_people'); //TABLE NAME  
define('OLD_BOOKING_PEOPLE_TBNAME','dbem_bookings_people'); //TABLE NAME   
define('OLD_DBEM_CATEGORIES_TBNAME', 'dbem_categories'); //TABLE NAME


// DEBUG constant for developing
// if you are hacking this plugin, set to TRUE, a log will show in admin pages
define('DEBUG', false);

// FILTERS
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
// Notes map filters
add_filter('dbem_notes_map', 'convert_chars', 8);
add_filter('dbem_notes_map', 'js_escape');

// LOCALIZATION  
// Localised date formats as in the jquery UI datepicker plugin
//TODO Sort out dates, (ref: output idea) 
load_plugin_textdomain('dbem', false, dirname( plugin_basename( __FILE__ ) ).'/includes/langs');

/**
 * This function will load an event into the global $EM_Event variable during page initialization, provided an event_id is given in the url via GET or POST.
 * global $EM_Recurrences also holds global array of recurrence objects when loaded in this instance for performance
 * All functions (admin and public) can now work off this object rather than it around via arguments.
 * @return null
 */
function dbem_load_event(){
	global $EM_Event, $EM_Recurrences, $EM_Location;
	$EM_Recurrences = array();
	if( isset( $_REQUEST['event_id'] ) && is_numeric($_REQUEST['event_id']) ){
		$EM_Event = new EM_Event($_REQUEST['event_id']);
	}elseif( $_REQUEST['recurrence_id'] && is_numeric($_REQUEST['recurrence_id']) ){
		//Eventually we can just remove this.... each event has an event_id regardless of what it is.
		$EM_Event = new EM_Event($_REQUEST['recurrence_id']);
	}elseif( $_REQUEST['location_id'] && is_numeric($_REQUEST['location_id']) ){
		$EM_Location = new EM_Location($_REQUEST['location_id']);
	}
	define('EM_URI', get_permalink(get_option("dbem_events_page"))); //PAGE URI OF EM 
	define('EM_RSS_URI', get_bloginfo('wpurl')."/?dbem_rss=main"); //RSS PAGE URI
}
add_action('init', 'dbem_load_event', 1);
                   
// Settings link in the plugins page menu
function em_set_plugin_meta($links, $file) {
	$plugin = plugin_basename(__FILE__);
	// create link
	if ($file == $plugin) {
		return array_merge(
			$links,
			array( sprintf( '<a href="admin.php?page=events-manager-options">%s</a>', __('Settings') ) )
		);
	}
	return $links;
}
add_filter( 'plugin_row_meta', 'em_set_plugin_meta', 10, 2 );


// Create the Manage Events and the Options submenus  
function dbem_create_events_submenu () {
	if(function_exists('add_submenu_page')) {
		//TODO Add flexible permissions
	  	add_object_page(__('Events', 'dbem'),__('Events', 'dbem'),MIN_CAPABILITY,__FILE__,'dbem_events_subpanel', '../wp-content/plugins/events-manager/includes/images/calendar-16.png');
	   	// Add a submenu to the custom top-level menu:
	   		$plugin_pages = array(); 
			$plugin_pages[] = add_submenu_page(__FILE__, __('Edit'),__('Edit'),MIN_CAPABILITY,__FILE__,'dbem_events_subpanel');
			$plugin_pages[] = add_submenu_page(__FILE__, __('Add new', 'dbem'), __('Add new','dbem'), MIN_CAPABILITY, 'new_event', "dbem_new_event_page");
			$plugin_pages[] = add_submenu_page(__FILE__, __('Locations', 'dbem'), __('Locations', 'dbem'), MIN_CAPABILITY, 'locations', "dbem_locations_page");
			$plugin_pages[] = add_submenu_page(__FILE__, __('People', 'dbem'), __('People', 'dbem'), MIN_CAPABILITY, 'people', "dbem_people_page");
			$plugin_pages[] = add_submenu_page(__FILE__, __('Events Manager Settings','dbem'),__('Settings','dbem'), SETTING_CAPABILITY, "events-manager-options", 'dbem_options_subpanel');
			$plugin_pages[] = add_submenu_page(__FILE__, __('Event Categories','dbem'),__('Categories','dbem'), SETTING_CAPABILITY, "events-manager-categories", 'dbem_categories_subpanel');
			foreach($plugin_pages as $plugin_page){
				add_action( 'admin_print_scripts-'. $plugin_page, 'em_admin_load_scripts' );
				add_action( 'admin_head-'. $plugin_page, 'em_admin_general_script' );
				add_action( 'admin_print_styles-'. $plugin_page, 'em_admin_load_styles' );
			}
  	}
}
add_action('admin_menu','dbem_create_events_submenu');

// Enqueing jQuery script to make sure it's loaded
function dbem_enqueue_scripts() {
	wp_enqueue_script ( 'jquery' );
	// wp_enqueue_script('datepicker','/wp-content/plugins/events-manager/jquery-ui-datepicker/jquery-ui-personalized-1.6b.js', array('jquery') );
}
add_action ( 'template_redirect', 'dbem_enqueue_scripts' );

function dbem_general_css() {
	echo "<link rel='stylesheet' href='". get_bloginfo('wpurl') ."/wp-content/plugins/events-manager/includes/css/events_manager.css' type='text/css'/>";

}
add_action ( 'wp_head', 'dbem_general_css' );

function dbem_favorite_menu($actions) {
	// add quick link to our favorite plugin
	$actions ['admin.php?page=new_event'] = array (__ ( 'Add an event', 'dbem' ), MIN_CAPABILITY );
	return $actions;
}
add_filter ( 'favorite_actions', 'dbem_favorite_menu' );
      

////////////////////////////////////
// WP 2.7 options registration
function dbem_options_register() {
	$options = array ('dbem_events_page', 'dbem_display_calendar_in_events_page', 'dbem_use_event_end', 'dbem_event_list_item_format_header', 'dbem_event_list_item_format', 'dbem_event_list_item_format_footer', 'dbem_event_page_title_format', 'dbem_single_event_format', 'dbem_list_events_page', 'dbem_events_page_title', 'dbem_no_events_message', 'dbem_location_page_title_format', 'dbem_location_baloon_format', 'dbem_single_location_format', 'dbem_location_event_list_item_format', 'dbem_location_no_events_message', 'dbem_gmap_is_active', 'dbem_rss_main_title', 'dbem_rss_main_description', 'dbem_rss_title_format', 'dbem_rss_description_format', 'dbem_gmap_key', 'dbem_map_text_format', 'dbem_rsvp_mail_notify_is_active', 'dbem_contactperson_email_body', 'dbem_respondent_email_body', 'dbem_mail_sender_name', 'dbem_smtp_username', 'dbem_smtp_password', 'dbem_default_contact_person', 'dbem_mail_sender_address', 'dbem_mail_receiver_address', 'dbem_smtp_host', 'dbem_rsvp_mail_send_method', 'dbem_rsvp_mail_port', 'dbem_rsvp_mail_SMTPAuth', 'dbem_image_max_width', 'dbem_image_max_height', 'dbem_image_max_size', 'dbem_full_calendar_event_format', 'dbem_use_select_for_locations', 'dbem_attributes_enabled', 'dbem_recurrence_enabled','dbem_rsvp_enabled','dbem_categories_enabled');
	foreach ( $options as $opt ) {
		register_setting ( 'dbem-options', $opt, '' );
	}

}
add_action ( 'admin_init', 'dbem_options_register' );

function dbem_alert_events_page() {
	$events_page_id = get_option ( 'dbem_events_page' );
	if (strpos ( $_SERVER ['SCRIPT_NAME'], 'page.php' ) && isset ( $_GET ['action'] ) && $_GET ['action'] == 'edit' && isset ( $_GET ['post'] ) && $_GET ['post'] == "$events_page_id") {
		$message = sprintf ( __ ( "This page corresponds to <strong>Events Manager</strong> events page. Its content will be overriden by <strong>Events Manager</strong>. If you want to display your content, you can can assign another page to <strong>Events Manager</strong> in the the <a href='%s'>Settings</a>. ", 'dbem' ), 'admin.php?page=events-manager-options' );
		$notice = "<div class='error'><p>$message</p></div>";
		echo $notice;
	}

}
add_action ( 'admin_notices', 'dbem_alert_events_page' );


/***********************************************************
 * INSTALLATION / ACTIVATION
 ***********************************************************/

function em_upgrade_stuff(){
	//FIXME create upgrade scripts
	//added option  dbem_date_listing_title - need to do this
}

/* Creating the wp_events table to store event data*/
function em_install() {
	if( EM_VERSION > get_option('dbem_version') ){
	 	// Creates the events table if necessary
		em_create_events_table(); 
		em_create_locations_table();
	  	em_create_bookings_table();
	  	em_create_people_table();
		em_create_categories_table();
		em_add_options();
		
		if( get_option('dbem_version') < 2.3 )  
			em_migrate_to_new_tables();
			
	  	update_option('dbem_version', EM_VERSION); 
	  	
	  	// Create events page if necessary
	 	em_create_events_page();
		// wp-content must be chmodded 777. Maybe just wp-content.
		if(!file_exists("../".IMAGE_UPLOAD_DIR))
			mkdir("../".IMAGE_UPLOAD_DIR, 0777); //do we need to 777 it? it'll be owner apache anyway, like normal uploads
	}
}
register_activation_hook( __FILE__,'em_install');

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
		recurrence_byday int(4) NULL DEFAULT NULL,
		recurrence_byweekno int(4) NULL DEFAULT NULL,  		
		UNIQUE KEY (event_id)
		) DEFAULT CHARSET=utf8 ;";
	
	$old_table_name = $wpdb->prefix.OLD_EVENTS_TBNAME; 

	if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name && $wpdb->get_var("SHOW TABLES LIKE '$old_table_name'") != $old_table_name) {
		dbDelta($sql);		
		//Add default events
		$in_one_week = date('Y-m-d', time()*60*60*24*7);
		$in_four_weeks = date('Y-m-d', time()*60*60*24*7*4); 
		$in_one_year = date('Y-m-d', time()*60*60*24*7*365);
		
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
	dbDelta($sql);
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
function dbem_add_option($key, $value) {
	$option = get_option($key);
	if (empty($option))
		update_option($key, $value);
}      

function em_create_events_page(){
	global $wpdb;
	$events_page_id = get_option('dbem_events_page')  ;
	if ( $events_page_id != "" ) {
		$events_page = get_page($events_page_id);
		if( !is_object($events_page) ){
			//TODO Use WP functions to create event page
			global $wpdb,$current_user;
			$page_name= DEFAULT_EVENT_PAGE_NAME;
			$sql= "INSERT INTO $wpdb->posts (post_author, post_date, post_date_gmt, post_type, post_content, post_excerpt, post_title, post_name, post_modified, post_modified_gmt, comment_count) VALUES ($current_user->ID, '', '', 'page','CONTENTS', '', '$page_name', '".$wpdb->escape(__('events','dbem'))."', '', '', '0')";
			$wpdb->query($sql);
		}
	}
	add_option('dbem_events_page', $wpdb->insert_id);
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
	if( count($events_values) > 1 ){
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
	if( count($locations_values) > 1 ){
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
	if( count($people_values) > 1 ){
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
	if( count($bookings_values) > 1 ){
		$bookings_sql = "INSERT INTO " . $wpdb->prefix.BOOKINGS_TBNAME . 
			"(`" . implode('` ,`', $bookings_keys) . "`) VALUES".
			implode(', ', $bookings_values);
		$wpdb->query($bookings_sql);
	}
	 
	// migrating categories
	$categories = $wpdb->get_results('SELECT * FROM '.$wpdb->prefix.OLD_DBEM_CATEGORIES_TBNAME,ARRAY_A)  ;
	foreach($categories as $c) {                
		$wpdb->insert($wpdb->prefix.DBEM_CATEGORIES_TBNAME, $c);
	} 
	 
}
?>