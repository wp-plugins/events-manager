<?php
/*
 * This file contains the event related hooks in the front end, as well as some event template tags
 */

/**
 * Filters for page content and if an event replaces it with the relevant event data.
 * @param $data
 * @return string
 */
function em_content($data) {
	$events_page_id = get_option ( 'dbem_events_page' );
	if ( get_the_ID() == $events_page_id ) {
		global $wpdb, $EM_Event;
		//TODO any loop should put the current $EM_Event etc. into the global variable
		if ( isset( $_REQUEST['calendar_day'] ) && $_REQUEST['calendar_day'] != '' ) {
			//Events for a specific day
			$events = EM_Events::get( array('limit'=>10,'scope'=>$_REQUEST['calendar_day'],'order'=>"ASC") );
			if ( count($events) > 1) {
				return EM_Events::output($events);
			} else {
				$EM_Event = $events[0];
				return $EM_Event->output_single();
			}
		} elseif ( is_numeric($_REQUEST['location_id']) ) {
			//Just a single location
			$location = new EM_Location($_REQUEST['location_id']);
			return $location->output_single();
		} elseif ( is_numeric($_REQUEST['event_id']) ) {
			// single event page
			$event = new EM_Event( $_REQUEST['event_id'] );
			return $event->output_single();
		} else {
			// Multiple events page
			$scope = ($_REQUEST['scope']) ? EM_Object::sanitize($_REQUEST['scope']) : "future";
			if (get_option ( 'dbem_display_calendar_in_events_page' )){
				return EM_Calendar::get('full=1');
			}else{
				return EM_Events::output ( array('limit'=>10,'scope'=>$scope, 'order'=>"ASC") );
			}
		}
	} else {
		return $data;
	}
}
add_filter ( 'the_content', 'em_content' );

/**
 * Filter for titles when on event pages
 * @param $data
 * @return string
 */
function em_events_page_title($data) {
	global $EM_Event;
	global $post;
	$events_page_id = get_option ( 'dbem_events_page' );
	
	if ( $post->ID == $events_page_id ) {
		if (isset ( $_REQUEST['calendar_day'] ) && $_REQUEST['calendar_day'] != '') {
			$events = EM_Events::get(array('limit'=>2,'scope'=>$_REQUEST['calendar_day']));
			$event = $events[0];
			if ( count($events) > 1 ) {
				return $event->output( get_option ('dbem_list_date_title') );
			}else{
				return $event->output( get_option('dbem_event_page_title_format') );
			}
		}
		if (isset ( $_REQUEST ['location_id'] ) && $_REQUEST ['location_id'] |= '') {
			$location = new EM_Location( EM_Object::sanitize($_REQUEST ['location_id']) );
			return $location->output(get_option( 'dbem_location_page_title_format' ));;
		}
		if (isset ( $_REQUEST ['event_id'] ) && $_REQUEST ['event_id'] != '') {
			// single event page
			return $EM_Event->output ( get_option ( 'dbem_event_page_title_format' ) );
		} else {
			// Multiple events page
			return get_option ( 'dbem_events_page_title' );
		}
	} else {
		return $data;
	}
}
add_filter ( 'single_post_title', 'em_events_page_title' ); //Filter for the wp_title of page, can directly reference page title function

/**
 * Makes sure we're in "THE Loop", which is determinied by a flag set when the_post() (start) is first called, and when have_posts() (end) returns false.
 * @param string $data
 * @return string
 */
function em_wp_the_title($data){
	//This is set by the loop_start and loop_end actions
	global $wp_query;
	if ( $wp_query->in_the_loop ) {
		return em_events_page_title($data) ;
	}else{
		return $data ;
	}
}
add_filter ( 'the_title', 'em_wp_the_title' );

/**
 * Filters the get_pages functions so it includes the event pages?
 * @param $data
 * @return array
 */
function em_filter_get_pages($data) {
	global $em_disable_filter; //Using a flag here instead
	$show_events_menu = get_option ( 'dbem_list_events_page' );
	if ( !$show_events_menu && $em_disable_filter !== true ) {
		$output = array(); 
		$events_page_id = get_option ( 'dbem_events_page' );
		foreach( $data as $data_id => $page ) {
			if($page->ID != $events_page_id){
				$output[] = $page;
			}
		}
		return $output;
	}
	return $data;
}
add_filter ( 'get_pages', 'em_filter_get_pages' );

?>