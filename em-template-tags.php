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
	if ( is_string($args) && strpos ( $args, "=" )) {
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
	if ( !is_array($args) && strpos ( $args, "=" )) {
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
 * Retreives the event submission form for guests and members.
 * @param array $args
 */
function em_get_event_form( $args = array() ){
	/*
	if( !is_user_logged_in() && get_option('dbem_events_anonymous_submissions') ){
		em_locate_template('forms/event-editor-guest.php',true);
	}
	*/
	em_locate_template('forms/event-editor.php',true);
}
/**
 * Echo the em_get_event_form template tag
 * @param array $args
 */
function em_event_form( $args = array() ){ echo em_get_event_form( $args ); }


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
 * Returns true if the page is the events page. this is now only an events page, before v4.0.83 this would be true for any multiple page (e.g. locations) 
 * @return boolean
 */
function em_is_events_page() {
	global $post;
	return em_get_page_type() == 'events';
}
function dbem_is_events_page(){ em_is_events_page(); } //Depreciated
function dbem_is_multiple_events_page(){ em_is_events_page(); } //Depreciated
function em_is_multiple_events_page(){ em_is_events_page(); } //Depreciated

/**
 * Is this a a single event page?
 * @return boolean
 */
function em_is_event_page(){
	return em_get_page_type() == 'event';
}
function dbem_is_single_event_page(){ em_is_single_event_page(); } //Depreciated
function em_is_single_event_page(){ em_is_event_page(); } //Depreciated


/**
 * Is this a a single calendar day page?
 * @return boolean
 */
function em_is_calendar_day_page(){
	return em_get_page_type() == 'calendar_day';
}

/**
 * Is this a a single category page?
 * @return boolean
 */
function em_is_category_page(){
	return em_get_page_type() == 'category';
}
/**
 * Is this a categories list page?
 * @return boolean
 */
function em_is_categories_page(){
	return em_get_page_type() == 'categories';
}

/**
 * Is this a a single location page?
 * @return boolean
 */
function em_is_location_page(){
	return em_get_page_type() == 'location';
}
/**
 * Is this a locations list page?
 * @return boolean
 */
function em_is_locations_page(){
	return em_get_page_type() == 'locations';
}

/**
 * Is this my bookings page?
 * @return boolean
 */
function em_is_my_bookings_page(){
	return em_get_page_type() == 'my_bookings';
}



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

/**
 * Generate a grouped list of events by year, month, week or day.
 * @since 4.213
 * @param array $args
 * @param string $format
 * @return string
 */
function em_get_events_list_grouped($args, $format=''){
	//Reset some args to include pagination for if pagination is requested.
	$args['limit'] = (!empty($args['limit']) && is_numeric($args['limit']) )? $args['limit'] : false;
	$args['page'] = (!empty($args['page']) && is_numeric($args['page']) )? $args['page'] : 1;
	$args['page'] = (!empty($_REQUEST['page']) && is_numeric($_REQUEST['page']) )? $_REQUEST['page'] : $args['page'];
	$args['offset'] = ($args['page']-1) * $args['limit'];
	$args['orderby'] = 'event_start_date,event_start_time,event_name'; // must override this to display events in right cronology.
	if( !empty($format) ){ $args['format'] = html_entity_decode($format); } //accept formats
	//Reset some vars for counting events and displaying set arrays of events
	$atts = (array) $args;
	$atts['pagination'] = false;
	$atts['limit'] = false;
	$atts['page'] = false;
	$atts['offset'] = false;
	//decide what form of dates to show
	$EM_Events = EM_Events::get($args);
	$events_count = EM_Events::get($atts,true);
	ob_start();
	switch ( $args['mode'] ){
		case 'yearly':
			//go through the events and put them into a monthly array
			$events_dates = array();
			foreach($EM_Events as $EM_Event){
				$events_dates[date_i18n('Y',$EM_Event->start)][] = $EM_Event;
			}
			foreach ($events_dates as $year => $events){
				echo '<h2>'.$year.'</h2>';
				echo EM_Events::output($events, $atts);
			}
			break;
		case 'monthly':
			//go through the events and put them into a monthly array
			$events_dates = array();
			foreach($EM_Events as $EM_Event){
				$events_dates[date_i18n('M Y',$EM_Event->start)][] = $EM_Event;
			}
			foreach ($events_dates as $month => $events){
				echo '<h2>'.$month.'</h2>';
				echo EM_Events::output($events, $atts);
			}
			break;
		case 'weekly':
			$events_dates = array();
			foreach($EM_Events as $EM_Event){
	   			$start_of_week = get_option('start_of_week');
				$day_of_week = date('w',$EM_Event->start);
				$day_of_week = date('w',$EM_Event->start);
				$offset = $day_of_week - $start_of_week;
				if($offset<0){ $offset += 7; }
				$offset = $offset * 60*60*24; //days in seconds
				$start_day = strtotime($EM_Event->start_date);
				$events_dates[$start_day - $offset][] = $EM_Event;
			}
			foreach ($events_dates as $event_day_ts => $events){
				echo '<h2>'.date_i18n(get_option('date_format'),$event_day_ts).' - '.date_i18n(get_option('date_format'),$event_day_ts+(60*60*24*6)).'</h2>';
				echo EM_Events::output($events, $atts);
			}
			break;
		default: //daily
			//go through the events and put them into a daily array
			$events_dates = array();
			foreach($EM_Events as $EM_Event){
				$events_dates[strtotime($EM_Event->start_date)][] = $EM_Event;
			}
			foreach ($events_dates as $event_day_ts => $events){
				echo '<h2>'.date_i18n(get_option('date_format'),$event_day_ts).'</h2>';
				echo EM_Events::output($events, $atts);
			}
			break;
	}
	if( !empty($args['limit']) && $events_count > $args['limit'] && (!empty($args['pagination']) || !isset($args['pagination'])) ){
		//Show the pagination links (unless there's less than $limit events)
		$page_link_template = add_query_arg(array('page'=>'%PAGE%'));
		echo em_paginate( $page_link_template, $events_count, $args['limit'], $args['page']);
	}
	return ob_get_clean();
}
?>