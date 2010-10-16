<?php
/*
Plugin Name: Events Manager
Version: 3.0.3
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
include_once("bookings.php");
include_once("events.php");
include_once("functions.php");
include_once("locations.php");
include_once("rss.php");
include_once("shortcode.php");
include_once("template-tags.php");
include_once("template-tags-depreciated.php"); //To depreciate
//Widgets
include_once("widgets/events.php");
include_once("widgets/locations.php");
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
include_once("classes/mailer.php") ;
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
	include_once('admin/support.php');
}


// Setting constants
define('EM_VERSION', 3.02); //self expanatory
define('DBEM_CATEGORIES_TBNAME', 'em_categories'); //TABLE NAME
define('EVENTS_TBNAME','em_events'); //TABLE NAME
define('RECURRENCE_TBNAME','dbem_recurrence'); //TABLE NAME   
define('LOCATIONS_TBNAME','em_locations'); //TABLE NAME  
define('BOOKINGS_TBNAME','em_bookings'); //TABLE NAME
define('PEOPLE_TBNAME','em_people'); //TABLE NAME  
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
define('DEFAULT_WIDGET_EVENT_LIST_ITEM_FORMAT','#_LINKEDNAME<ul><li>#j #M #y</li><li>#_TOWN</li></ul>');
define('DEFAULT_WIDGET_LOCATION_LIST_ITEM_FORMAT','#_LOCATIONLINK<ul><li>#_ADDRESS</li><li>#_TOWN</li></ul>');
define('DEFAULT_NO_EVENTS_MESSAGE', __('No events', 'dbem'));  
define('DEFAULT_SINGLE_LOCATION_FORMAT', '<p>#_ADDRESS</p><p>#_TOWN</p>'); 
define('DEFAULT_LOCATION_PAGE_TITLE_FORMAT', '#_NAME'); 
define('DEFAULT_LOCATION_BALOON_FORMAT', "<strong>#_NAME</strong><br/>#_ADDRESS - #_TOWN<br/><a href='#_LOCATIONPAGEURL'>Details</a>");
define('DEFAULT_LOCATION_EVENT_LIST_ITEM_FORMAT', "<li>#_NAME - #j #M #Y - #H:#i</li>");
define('DEFAULT_LOCATION_LIST_ITEM_FORMAT','#_LOCATIONLINK<ul><li>#_ADDRESS</li><li>#_TOWN</li></ul>');
define('DEFAULT_LOCATION_NO_EVENTS_MESSAGE', __('<li>No events in this location</li>', 'dbem'));
define("IMAGE_UPLOAD_DIR", "wp-content/uploads/locations-pics");
define('DEFAULT_IMAGE_MAX_WIDTH', 700);  
define('DEFAULT_IMAGE_MAX_HEIGHT', 700);  
define('DEFAULT_IMAGE_MAX_SIZE', 204800); 
define('DEFAULT_FULL_CALENDAR_EVENT_FORMAT', '<li>#_LINKEDNAME</li>');    
define('DEFAULT_SMALL_CALENDAR_EVENT_TITLE_FORMAT', "#_NAME" );
define('DEFAULT_SMALL_CALENDAR_EVENT_TITLE_SEPARATOR', ", ");  
define('DEFAULT_USE_SELECT_FOR_LOCATIONS', false);      
define('DEFAULT_ATTRIBUTES_ENABLED', true);
define('DEFAULT_RECURRENCE_ENABLED', true);
define('DEFAULT_RSVP_ENABLED', true);
define('DEFAULT_CATEGORIES_ENABLED', true);
       
// obsolete tables
define('OLD_EVENTS_TBNAME','dbem_events') ; 
define('OLD_RECURRENCE_TBNAME','dbem_recurrence'); //TABLE NAME   
define('OLD_LOCATIONS_TBNAME','dbem_locations'); //TABLE NAME  
define('OLD_BOOKINGS_TBNAME','dbem_bookings'); //TABLE NAME
define('OLD_PEOPLE_TBNAME','dbem_people'); //TABLE NAME  
define('OLD_BOOKING_PEOPLE_TBNAME','dbem_bookings_people'); //TABLE NAME   
define('OLD_CATEGORIES_TBNAME', 'dbem_categories'); //TABLE NAME


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
function em_load_event(){
	global $EM_Event, $EM_Recurrences, $EM_Location, $EM_Mailer;
	$EM_Recurrences = array();
	if( isset( $_REQUEST['event_id'] ) && is_numeric($_REQUEST['event_id']) ){
		$EM_Event = new EM_Event($_REQUEST['event_id']);
	}elseif( isset($_REQUEST['recurrence_id']) && is_numeric($_REQUEST['recurrence_id']) ){
		//Eventually we can just remove this.... each event has an event_id regardless of what it is.
		$EM_Event = new EM_Event($_REQUEST['recurrence_id']);
	}elseif( isset($_REQUEST['location_id']) && is_numeric($_REQUEST['location_id']) ){
		$EM_Location = new EM_Location($_REQUEST['location_id']);
	}
	$EM_Mailer = new EM_Mailer();
	define('EM_URI', get_permalink(get_option("dbem_events_page"))); //PAGE URI OF EM 
	define('EM_RSS_URI', get_bloginfo('wpurl')."/?dbem_rss=main"); //RSS PAGE URI
}
add_action('init', 'em_load_event', 1);
                   
/**
 * Settings link in the plugins page menu
 * @param array $links
 * @param string $file
 * @return array
 */
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
function em_create_events_submenu () {
	if(function_exists('add_submenu_page')) {
		//TODO Add flexible permissions
	  	add_object_page(__('Events', 'dbem'),__('Events', 'dbem'),MIN_CAPABILITY,__FILE__,'dbem_events_subpanel', '../wp-content/plugins/events-manager/includes/images/calendar-16.png');
	   	// Add a submenu to the custom top-level menu:
	   		$plugin_pages = array(); 
			$plugin_pages[] = add_submenu_page(__FILE__, __('Edit'),__('Edit'),MIN_CAPABILITY,__FILE__,'dbem_events_subpanel');
			$plugin_pages[] = add_submenu_page(__FILE__, __('Add new', 'dbem'), __('Add new','dbem'), MIN_CAPABILITY, 'new_event', "dbem_new_event_page");
			$plugin_pages[] = add_submenu_page(__FILE__, __('Locations', 'dbem'), __('Locations', 'dbem'), MIN_CAPABILITY, 'locations', "dbem_locations_page");
			$plugin_pages[] = add_submenu_page(__FILE__, __('People', 'dbem'), __('People', 'dbem'), MIN_CAPABILITY, 'people', "em_people_page");
			$plugin_pages[] = add_submenu_page(__FILE__, __('Event Categories','dbem'),__('Categories','dbem'), SETTING_CAPABILITY, "events-manager-categories", 'dbem_categories_subpanel');
			$plugin_pages[] = add_submenu_page(__FILE__, __('Events Manager Settings','dbem'),__('Settings','dbem'), SETTING_CAPABILITY, "events-manager-options", 'dbem_options_subpanel');
			$plugin_pages[] = add_submenu_page(__FILE__, __('Getting Help for Events Manager','dbem'),__('Help','dbem'), SETTING_CAPABILITY, "events-manager-support", 'em_admin_support');
			foreach($plugin_pages as $plugin_page){
				add_action( 'admin_print_scripts-'. $plugin_page, 'em_admin_load_scripts' );
				add_action( 'admin_head-'. $plugin_page, 'em_admin_general_script' );
				add_action( 'admin_print_styles-'. $plugin_page, 'em_admin_load_styles' );
			}
  	}
}
add_action('admin_menu','em_create_events_submenu');


/**
 * Enqueing public scripts and styles 
 */
function em_enqueue_public() {
	wp_enqueue_script ( 'jquery' ); //make sure we have jquery loaded
	wp_enqueue_style('events-manager', WP_PLUGIN_URL.'/events-manager/includes/css/events_manager.css'); //main css
}
add_action ( 'template_redirect', 'em_enqueue_public' );

/**
 * Add a link to the favourites menu
 * @param array $actions
 * @return multitype:string 
 */
function em_favorite_menu($actions) {
	// add quick link to our favorite plugin
	$actions ['admin.php?page=new_event'] = array (__ ( 'Add an event', 'dbem' ), MIN_CAPABILITY );
	return $actions;
}
add_filter ( 'favorite_actions', 'em_favorite_menu' );
      
/**
 * Generate warnings and notices in the admin area
 */
function em_admin_warnings() {
	//If we're editing the events page show hello to new user
	$events_page_id = get_option ( 'dbem_events_page' );
	if (isset ( $_GET ['disable_hello_to_user'] ) && $_GET ['disable_hello_to_user'] == 'true'){
		// Disable Hello to new user if requested
		update_option ( 'dbem_hello_to_user', 0 );
	}else{
		if ( preg_match( '/(post|page).php/', $_SERVER ['SCRIPT_NAME']) && isset ( $_GET ['action'] ) && $_GET ['action'] == 'edit' && isset ( $_GET ['post'] ) && $_GET ['post'] == "$events_page_id") {
			$message = sprintf ( __ ( "This page corresponds to <strong>Events Manager</strong> events page. Its content will be overriden by <strong>Events Manager</strong>. If you want to display your content, you can can assign another page to <strong>Events Manager</strong> in the the <a href='%s'>Settings</a>. ", 'dbem' ), 'admin.php?page=events-manager-options' );
			$notice = "<div class='error'><p>$message</p></div>";
			echo $notice;
		}
	}
	//If events page couldn't be created
	if( !empty($_GET['em_dismiss_events_page']) ){
		update_option('dbem_dismiss_events_page',1);
	}else{
		if ( !get_page($events_page_id) && !get_option('dbem_dismiss_events_page') ){
			$dismiss_link_joiner = ( count($_GET) > 0 ) ? '&amp;':'?';
			$advice = sprintf ( __( 'Uh Oh! For some reason wordpress could not create an events page for you (or you just deleted it). Not to worry though, all you have to do is create an empty page, name it whatever you want, and select it as your events page in your <a href="%s">options page</a>. Sorry for the extra step! If you know what you are doing, you may have done this on purpose, if so <a href="%s">ignore this message</a>', 'dbem'), get_bloginfo ( 'url' ) . '/wp-admin/admin.php?page=events-manager-options', $_SERVER['REQUEST_URI'].$dismiss_link_joiner.'em_dismiss_events_page=1' );
			?>
			<div id="em_page_error" class="updated">
				<p><?php echo $advice; ?></p>
			</div>
			<?php		
		}
	}
}
add_action ( 'admin_notices', 'em_admin_warnings' );

/* Creating the wp_events table to store event data*/
function em_activate() {
	require_once(WP_PLUGIN_DIR.'/events-manager/install.php');
	em_install();
}
register_activation_hook( __FILE__,'em_activate');

if( !empty($_GET['em_reimport']) || get_option('dbem_import_fail') == '1' ){
	require_once(WP_PLUGIN_DIR.'/events-manager/install.php');
}
?>