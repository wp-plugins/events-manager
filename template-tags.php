<?php
/*
 * Template Tags
 * If you know what you're doing, you're probably better off using the EM Objects directly.
 */

/**
 * Returns a html list of events filtered by the array or query-string of arguments supplied. 
 * @param array|string $args
 * @return string
 */
function em_get_events( $args = array() ){
	if (strpos ( $args, "=" )) {
		// allows the use of arguments without breaking the legacy code
		$defaults = EM_Events::get_default_search();		
		$args = wp_parse_args ( $args, $defaults );
	}
	return EM_Events::output( $args );
}
/**
 * Prints out a list of events, takes same arguments as em_get_events.
 * @param array|string $args
 * @uses em_get_events()
 */
function em_events( $args = array() ){ echo em_get_events($args); }

/**
 * Returns a html list of locations filtered by the array or query-string of arguments supplied. 
 * @param array|string $args
 * @return string
 */
function em_get_locations( $args = array() ){
	if (strpos ( $args, "=" )) {
		// allows the use of arguments without breaking the legacy code
		$defaults = EM_Locations::get_default_search();		
		$args = wp_parse_args ( $args, $defaults );
	}
	return EM_Locations::output( $args );
}
/**
 * Prints out a list of locations, takes same arguments as em_get_locations.
 * @param array|string $args
 * @uses em_get_locations()
 */
function em_locations( $args = array() ){ echo em_get_locations($args); }

/**
 * Returns an html calendar of events filtered by the array or query-string of arguments supplied. 
 * @param array|string $args
 * @return string
 */
function em_get_calendar( $args = array() ){
	if (strpos ( $args, "=" )) {
		// allows the use of arguments without breaking the legacy code
		$defaults = EM_Calendar::get_default_search();		
		$args = wp_parse_args ( $args, $defaults );
	}
	return EM_Calendar::output($args);
}
/**
 * Prints out an html calendar, takes same arguments as em_get_calendar.
 * @param array|string $args
 * @uses em_get_calendar()
 */
function em_calendar( $args = array() ){ echo em_get_calendar($args); }

/**
 * Creates an html link to the events page.
 * @param string $text
 * @return string
 */
function em_get_link( $text = '' ) {
	$text = ($text == '') ? get_option ( "dbem_events_page_title" ) : $text;
	$text = ($text == '') ? __('Events','dbem') : $text; //In case options aren't there....
	return "<a href='".EM_URI."' title='$text'>$text</a>";
}
/**
 * Prints the result of em_get_link()
 * @param string $text
 * @uses em_get_link()
 */
function em_link($text = ''){ echo em_get_link($text); }

/**
 * Creates an html link to the RSS feed
 * @param string $text
 * @return string
 */
function em_get_rss_link($text = "RSS") {
	$text = ($text == '') ? 'RSS' : $text;
	return "<a href='".EM_RSS_URI."'>$text</a>";
}
/**
 * Prints the result of em_get_rss_link()
 * @param string $text
 * @uses em_get_rss_link()
 */
function em_rss_link($text = "RSS"){ echo em_get_rss_link($text); }

/**
 * Returns true if there are any events that exist in the given scope (default is future events).
 * @param string $scope
 * @return boolean
 */
function em_are_events_available($scope = "future") {
	$scope = ($scope == "") ? "future":$scope;
	$events = EM_Events::get( array('limit'=>1, 'scope'=>$scope) );	
	return ( count($events) > 0 );
}
function dbem_are_events_available($scope = "future"){ em_are_events_available($scope); } //no biggie, we can remove these later, to avoid extra initial work for our plugin users!


/**
 * Returns true if the page is the events page. This may be a locations page, single event, multiple events, etc. so be careful!
 * @return boolean
 */
function em_is_events_page() {
	global $post;
	return ($post->ID == get_option('dbem_events_page'));
}
function dbem_is_events_page(){ em_is_events_page(); } //Depreciated


/**
 * Returns true if this is a single event
 * @return boolean
 */
function em_is_single_event_page() {
	return (em_is_events_page () && (isset ( $_REQUEST ['event_id'] ) && $_REQUEST ['event_id'] != ''));
}
function dbem_is_single_event_page(){ em_is_single_event_page(); } //Depreciated


/**
 * If this is a page is a multiple events page
 * @return boolean
 */
function em_is_multiple_events_page() {
	//FIXME this will also show true if it's not a locations page
	return ( em_is_events_page () && !em_is_single_event_page() );
}
function dbem_is_multiple_events_page(){ em_is_multiple_events_page(); } //Depreciated


/**
 * Returns true if this is a single events page and the event is RSVPable
 * @return boolean
 */
function em_is_event_rsvpable() {
	//We assume that we're on a single event (or recurring event) page here, so $EM_Event must be loaded
	global $EM_Event;
	return ( em_is_single_event_page() && is_numeric($EM_Event->id) && $EM_Event->rsvp );
}
function dbem_is_event_rsvpable(){ em_is_event_rsvpable(); } //Depreciated

?>