<?php
/*
 * Template Tags
 * These template tags were used up until EM 2.2 they have been modified to use the new OOP structure
 * of EM, but still provide the same values as before for backward compatability.
 * If you'd like to port over to the new template functions, check out the tag you want and see how we did it (or view the new docs)
 */

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
 * Creates an html link to the RSS feed
 * @param string $text
 * @return string
 */
function em_get_rss_link($text = "RSS") {
	$text = ($text == '') ? 'RSS' : $text;
	return "<a href='".EM_RSS_URI."'>$text</a>";
}

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
	$events_page_id = get_option ( 'dbem_events_page' );
	return is_page ( $events_page_id );
}
function dbem_is_events_page(){ em_is_events_page(); }


/**
 * Returns true if this is a single event
 * @return boolean
 */
function em_is_single_event_page() {
	return (em_is_events_page () && (isset ( $_REQUEST ['event_id'] ) && $_REQUEST ['event_id'] != ''));
}
function dbem_is_single_event_page(){ em_is_single_event_page(); }


/**
 * If this is a page is a multiple events page
 * @return boolean
 */
function em_is_multiple_events_page() {
	//FIXME this will also show true if it's not a locations page
	return ( em_is_events_page () && !em_is_single_event_page() );
}
function dbem_is_multiple_events_page(){ em_is_multiple_events_page(); }


/**
 * Returns true if this is a single events page and the event is RSVPable
 * @return boolean
 */
function em_is_event_rsvpable() {
	//We assume that we're on a single event (or recurring event) page here, so $EM_Event must be loaded
	global $EM_Event;
	return ( em_is_single_event_page() && is_numeric($EM_Event->id) && $EM_Event->rsvp );
}
function dbem_is_event_rsvpable(){ em_is_event_rsvpable(); }

?>