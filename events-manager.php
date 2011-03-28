<?php
/*
Plugin Name: Events Manager
Version: 4.0b
Plugin URI: http://wp-events-plugin.com
Description: A complete event management solution for wordpress. Recurring events, locations, google maps, rss, bookings and more!
Author: Davide Benini, Marcus Sykes
Author URI: http://wp-events-plugin.com
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
//DEBUG MODE
if( !defined('WP_DEBUG') && get_option('dbem_wp_debug') ){
	define('WP_DEBUG',true);
}
function dbem_debug_mode(){
	if( !empty($_REQUEST['dbem_debug_off']) ){
		update_option('dbem_debug',0);
		wp_redirect($_SERVER['HTTP_REFERER']);
	}
	if( current_user_can('activate_plugins') ){
		include_once('em-debug.php');
	}
}
add_action('plugins_loaded', 'dbem_debug_mode');

// INCLUDES 
include_once('classes/em-object.php'); //Base object, any files below may depend on this 
//Template Tags & Template Logic
include_once("em-actions.php");
include_once("em-bookings.php");
include_once("em-events.php");
include_once("em-functions.php");
include_once("em-rss.php");
include_once("em-shortcode.php");
include_once("em-template-tags.php");
include_once("em-template-tags-depreciated.php"); //To depreciate
//Widgets
include_once("widgets/em-events.php");
include_once("widgets/em-locations.php");
include_once("widgets/em-calendar.php");
//Classes
include_once('classes/em-booking.php');
include_once('classes/em-bookings.php');
include_once('classes/em-calendar.php');
include_once('classes/em-category.php');
include_once('classes/em-categories.php');
include_once('classes/em-event.php');
include_once('classes/em-events.php');
include_once('classes/em-location.php');
include_once('classes/em-locations.php');
include_once("classes/em-mailer.php") ;
include_once('classes/em-map.php');
include_once('classes/em-notices.php');
include_once('classes/em-people.php');
include_once('classes/em-person.php');
include_once('classes/em-permalinks.php');
include_once('classes/em-ticket-booking.php');
include_once('classes/em-ticket.php');
include_once('classes/em-tickets-bookings.php');
include_once('classes/em-tickets.php');
//Admin Files
if( is_admin() ){
	include_once('admin/em-admin.php');
	include_once('admin/em-bookings.php');
	include_once('admin/em-categories.php');
	include_once('admin/em-docs.php');
	include_once('admin/em-event.php');
	include_once('admin/em-events.php');
	include_once('admin/em-help.php');
	include_once('admin/em-locations.php');
	include_once('admin/em-options.php');
	include_once('admin/em-people.php');
	//bookings folder
		include_once('admin/bookings/em-cancelled.php');
		include_once('admin/bookings/em-confirmed.php');
		include_once('admin/bookings/em-events.php');
		include_once('admin/bookings/em-rejected.php');
		include_once('admin/bookings/em-pending.php');
		include_once('admin/bookings/em-person.php');
}
/* Only load the component if BuddyPress is loaded and initialized. */
function bp_em_init() {
	require( dirname( __FILE__ ) . '/buddypress/bp-em-core.php' );
}
add_action( 'bp_init', 'bp_em_init' );


// Setting constants
define('EM_VERSION', 4.0012); //self expanatory
define('EM_DIR', dirname( __FILE__ )); //an absolute path to this directory 
define('EM_CATEGORIES_TABLE', 'em_categories'); //TABLE NAME
define('EM_EVENTS_TABLE','em_events'); //TABLE NAME
define('EM_TICKETS_TABLE', 'em_tickets'); //TABLE NAME
define('EM_TICKETS_BOOKINGS_TABLE', 'em_tickets_bookings'); //TABLE NAME
define('EM_META_TABLE','em_meta'); //TABLE NAME
define('EM_RECURRENCE_TABLE','dbem_recurrence'); //TABLE NAME   
define('EM_LOCATIONS_TABLE','em_locations'); //TABLE NAME  
define('EM_BOOKINGS_TABLE','em_bookings'); //TABLE NAME
define('EM_PEOPLE_TABLE','em_people'); //TABLE NAME
define('EM_MIN_CAPABILITY', 'edit_events');	// Minimum user level to add events
define('EM_EDITOR_CAPABILITY', 'publish_events');	// Minimum user level to access calendars
define('EM_SETTING_CAPABILITY', 'activate_plugins'); // Minimum user level to edit settings in EM
define("EM_IMAGE_UPLOAD_DIR", "wp-content/uploads/locations-pics");
// Localised date formats as in the jquery UI datepicker plugin but for php date
$localised_date_formats = array("am" => "d.m.Y","ar" => "d/m/Y", "bg" => "d.m.Y", "ca" => "m/d/Y", "cs" => "d.m.Y", "da" => "d-m-Y", "de" =>"d.m.Y", "es" => "d/m/Y", "en" => "m/d/Y", "fi" => "d.m.Y", "fr" => "d/m/Y", "he" => "d/m/Y", "hu" => "Y-m-d", "hy" => "d.m.Y", "id" => "d/m/Y", "is" => "d/m/Y", "it" => "d/m/Y", "ja" => "Y/m/d", "ko" => "Y-m-d", "lt" => "Y-m-d", "lv" => "d-m-Y", "nl" => "d.m.Y", "no" => "Y-m-d", "pl" => "Y-m-d", "pt" => "d/m/Y", "ro" => "m/d/Y", "ru" => "d.m.Y", "sk" => "d.m.Y", "sv" => "Y-m-d", "th" => "d/m/Y", "tr" => "d.m.Y", "ua" => "d.m.Y", "uk" => "d.m.Y", "us" => "m/d/Y", "CN" => "Y-m-d", "TW" => "Y/m/d");
//TODO reorganize how defaults are created, e.g. is it necessary to create false entries? They are false by default... less code, but maybe not verbose enough...
       

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
add_filter('dbem_general_rss', 'esc_html');
// RSS content filter
add_filter('dbem_notes_rss', 'convert_chars', 8);    
add_filter('dbem_notes_rss', 'ent2ncr', 8);
// Notes map filters
add_filter('dbem_notes_map', 'convert_chars', 8);
add_filter('dbem_notes_map', 'js_escape');

/**
 * Perform plugins_loaded actions 
 */
function em_plugins_loaded(){
	//Capabilities
	global $em_capabilities_array;
	$em_capabilities_array = apply_filters('em_capabilities_array', array(
		'publish_events' => sprintf(__('You do not have permission to publish %s','dbem'),__('events','dbem')),
		'edit_categories' => sprintf(__('You do not have permission to edit %s','dbem'),__('categories','dbem')),
		'delete_others_events' => sprintf(__('You do not have permission to delete others %s','dbem'),__('events','dbem')),
		'delete_others_locations' => sprintf(__('You do not have permission to delete others %s','dbem'),__('locations','dbem')),
		'edit_others_locations' => sprintf(__('You do not have permission to edit others %s','dbem'),__('locations','dbem')),
		'manage_others_bookings' => sprintf(__('You do not have permission to manage others %s','dbem'),__('bookings','dbem')),
		'edit_others_events' => sprintf(__('You do not have permission to edit others %s','dbem'),__('events','dbem')),
		'delete_locations' => sprintf(__('You do not have permission to delete %s','dbem'),__('locations','dbem')),
		'delete_events' => sprintf(__('You do not have permission to delete %s','dbem'),__('events','dbem')),
		'edit_locations' => sprintf(__('You do not have permission to edit %s','dbem'),__('locations','dbem')),
		'manage_bookings' => sprintf(__('You do not have permission to manage %s','dbem'),__('bookings','dbem')),
		'read_others_locations' => sprintf(__('You cannot to view others %s','dbem'),__('locations','dbem')),
		'edit_recurrences' => sprintf(__('You do not have permission to edit %s','dbem'),__('recurrences','dbem')),
		'edit_events' => sprintf(__('You do not have permission to edit %s','dbem'),__('events','dbem'))
	));
	// LOCALIZATION  
	load_plugin_textdomain('dbem', false, dirname( plugin_basename( __FILE__ ) ).'/includes/langs');
}
add_filter('plugins_loaded','em_plugins_loaded');

/**
 * Perform init actions
 */
function em_init(){
	//Hard Links
	define('EM_URI', get_permalink(get_option("dbem_events_page"))); //PAGE URI OF EM 
	define('EM_RSS_URI', trailingslashit(EM_URI)."rss/"); //RSS PAGE URI
}
add_filter('init','em_init');

/**
 * This function will load an event into the global $EM_Event variable during page initialization, provided an event_id is given in the url via GET or POST.
 * global $EM_Recurrences also holds global array of recurrence objects when loaded in this instance for performance
 * All functions (admin and public) can now work off this object rather than it around via arguments.
 * @return null
 */
function em_load_event(){
	global $EM_Event, $EM_Recurrences, $EM_Location, $EM_Mailer, $EM_Person, $EM_Booking, $EM_Category, $EM_Ticket, $current_user;
	if( !defined('EM_LOADED') ){
		$EM_Recurrences = array();
		if( isset( $_REQUEST['event_id'] ) && is_numeric($_REQUEST['event_id']) && !is_object($EM_Event) ){
			$EM_Event = new EM_Event($_REQUEST['event_id']);
		}elseif( isset($_REQUEST['event_slug']) && !is_object($EM_Event) ){
			$EM_Event = new EM_Event( $_REQUEST['event_slug'] );
		}
		if( isset($_REQUEST['location_id']) && is_numeric($_REQUEST['location_id']) && !is_object($EM_Location) ){
			$EM_Location = new EM_Location($_REQUEST['location_id']);
		}elseif( isset($_REQUEST['location_slug']) && !is_object($EM_Location) ){
			$EM_Location = new EM_Location($_REQUEST['location_slug']);
		}
		if( is_user_logged_in() || (!empty($_REQUEST['person_id']) && is_numeric($_REQUEST['person_id'])) ){
			//make the request id take priority, this shouldn't make it into unwanted objects if they use theobj::get_person().
			if( !empty($_REQUEST['person_id']) ){
				$EM_Person = new EM_Person( $_REQUEST['person_id'] );
			}else{
				$EM_Person = new EM_Person( get_current_user_id() );
			}
		}
		if( isset($_REQUEST['booking_id']) && is_numeric($_REQUEST['booking_id']) && !is_object($_REQUEST['booking_id']) ){
			$EM_Booking = new EM_Booking($_REQUEST['booking_id']);
		}
		if( isset($_REQUEST['category_id']) && is_numeric($_REQUEST['category_id']) && !is_object($_REQUEST['category_id']) ){
			$EM_Category = new EM_Category($_REQUEST['category_id']);
		}
		if( isset($_REQUEST['ticket_id']) && is_numeric($_REQUEST['ticket_id']) && !is_object($_REQUEST['ticket_id']) ){
			$EM_Ticket = new EM_Ticket($_REQUEST['ticket_id']);
		}
		$EM_Mailer = new EM_Mailer();
		define('EM_LOADED',true);
	}
}
add_action('template_redirect', 'em_load_event');
if(is_admin()){ add_action('init', 'em_load_event', 2); }

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
		//Count pending bookings
		$num = '';
		if( get_option('dbem_bookings_approval') == 1){ 
			$bookings_pending_count = count(EM_Bookings::get(array('status'=>0))->bookings);
			//TODO Add flexible permissions
			if($bookings_pending_count > 0){
				$num = '<span class="update-plugins count-'.$bookings_pending_count.'"><span class="plugin-count">'.$bookings_pending_count.'</span></span>';
			}
		}
	  	add_object_page(__('Events', 'dbem'),__('Events', 'dbem').$num,EM_MIN_CAPABILITY,'events-manager','em_admin_events_page', '../wp-content/plugins/events-manager/includes/images/calendar-16.png');
	   	// Add a submenu to the custom top-level menu:
	   		$plugin_pages = array(); 
			$plugin_pages[] = add_submenu_page('events-manager', __('Edit'),__('Edit'),'edit_events','events-manager','em_admin_events_page');
			$plugin_pages[] = add_submenu_page('events-manager', __('Add new', 'dbem'), __('Add new','dbem'), 'edit_events', 'events-manager-event', "em_admin_event_page");
			$plugin_pages[] = add_submenu_page('events-manager', __('Locations', 'dbem'), __('Locations', 'dbem'), 'edit_locations', 'events-manager-locations', "em_admin_locations_page");
			$plugin_pages[] = add_submenu_page('events-manager', __('Bookings', 'dbem'), __('Bookings', 'dbem').$num, 'manage_bookings', 'events-manager-bookings', "em_bookings_page");
			$plugin_pages[] = add_submenu_page('events-manager', __('Event Categories','dbem'),__('Categories','dbem'), 'edit_categories', "events-manager-categories", 'em_admin_categories_page');
			$plugin_pages[] = add_submenu_page('events-manager', __('Events Manager Settings','dbem'),__('Settings','dbem'), 'activate_plugins', "events-manager-options", 'em_admin_options_page');
			$plugin_pages[] = add_submenu_page('events-manager', __('Getting Help for Events Manager','dbem'),__('Help','dbem'), 'activate_plugins', "events-manager-help", 'em_admin_help_page');
			$plugin_pages = apply_filters('em_create_events_submenu',$plugin_pages);
			foreach($plugin_pages as $plugin_page){
				add_action( 'admin_print_scripts-'. $plugin_page, 'em_admin_load_scripts' );
				add_action( 'admin_head-'. $plugin_page, 'em_admin_general_script' );
				add_action( 'admin_print_styles-'. $plugin_page, 'em_admin_load_styles' );
			}
  	}
}
add_action('admin_menu','em_create_events_submenu');

/**
 * Works much like <a href="http://codex.wordpress.org/Function_Reference/locate_template" target="_blank">locate_template</a>, except it takes a string instead of an array of templates, we only need to load one.  
 * @param string $template_name
 * @param boolean $load
 * @uses locate_template()
 * @return string
 */
function em_locate_template( $template_name, $load=false ) {
	//First we check if there are overriding tempates in the child or parent theme
	$located = locate_template(array('plugins/events-manager/'.$template_name));
	if( !$located ){
		if ( file_exists(EM_DIR.'/templates/'.$template_name) ) {
			$located = EM_DIR.'/templates/'.$template_name;
		}
	}
	if( $load ){
		include($located);
	}
	return $located;
}

/**
 * Enqueing public scripts and styles 
 */
function em_enqueue_public() {
	//Scripts
	wp_enqueue_script('events-manager', WP_PLUGIN_URL.'/events-manager/includes/js/em_maps.js', array('jquery', 'jquery-form')); //jQuery will load as dependency
	wp_localize_script('events-manager','EM', array(
		'ajaxurl' => admin_url( 'admin-ajax.php' )
	));
	//Styles
	wp_enqueue_style('events-manager', WP_PLUGIN_URL.'/events-manager/includes/css/events_manager.css'); //main css
}
add_action ( 'init', 'em_enqueue_public' );

/**
 * Add a link to the favourites menu
 * @param array $actions
 * @return multitype:string 
 */
function em_favorite_menu($actions) {
	// add quick link to our favorite plugin
	$actions ['admin.php?page=events-manager-event'] = array (__ ( 'Add an event', 'dbem' ), EM_MIN_CAPABILITY );
	return $actions;
}
add_filter ( 'favorite_actions', 'em_favorite_menu' );

/* Creating the wp_events table to store event data*/
function em_activate() {
	global $wp_rewrite;
   	$wp_rewrite->flush_rules();
	require_once(WP_PLUGIN_DIR.'/events-manager/em-install.php');
	em_install();
}
register_activation_hook( __FILE__,'em_activate');

if( !empty($_GET['em_reimport']) || get_option('dbem_import_fail') == '1' ){
	require_once(WP_PLUGIN_DIR.'/events-manager/em-install.php');
}

/**
 * reset capabilities for testing purposes 
 */
function em_set_capabilities2(){
	//Get default roles
	global $wp_roles;
	//if( get_option('dbem_version') == '' && get_option('dbem_version') < 4 ){
		//Assign caps in groups, as we go down, permissions are "looser"
		$func = 'remove_cap';
		//Delete
		$wp_roles->$func('administrator', 'delete_events');
		$wp_roles->$func('editor', 'delete_events');
		//Publish Events
		$wp_roles->$func('administrator', 'publish_events');
		$wp_roles->$func('editor', 'publish_events');
		//Edit Others Events
		$wp_roles->$func('administrator', 'edit_others_events');
		$wp_roles->$func('editor', 'edit_others_events');
		//Add/Edit Events
		$wp_roles->$func('administrator', 'edit_events');
		$wp_roles->$func('editor', 'edit_events');
		//if(get_option('dbem_version') == ''){
			$wp_roles->$func('contributor', 'edit_events');
			$wp_roles->$func('author', 'edit_events');
			$wp_roles->$func('subscriber', 'edit_events');
		//}
	//}
}
//add_action('admin_init', 'em_set_capabilities2');
?>