<?php
/*
 * This file contains the event related hooks in the front end, as well as some event template tags
 */

/**
 * Filters for page content and if an event replaces it with the relevant event data.
 * @param $data
 * @return string
 */
function em_content($content) {
	$events_page_id = get_option ( 'dbem_events_page' );
	if ( get_the_ID() == $events_page_id && $events_page_id != 0 ) {
		global $wpdb, $EM_Event;
		//TODO FILTER - filter em page content before placeholder replacing
		//TODO any loop should put the current $EM_Event etc. into the global variable
		if ( !empty($_REQUEST['calendar_day']) ) {
			//Events for a specific day
			$args = array(					
				'orderby' => get_option('dbem_events_default_orderby'),
				'order' => get_option('dbem_events_default_order'),
				'scope'=> $_REQUEST['calendar_day'],
				'pagination' => 1
			);
			$page = ( !empty($_GET['page']) && is_numeric($_GET['page']) )? $_GET['page'] : 1;
			$events = EM_Events::get( apply_filters('em_content_calendar_day_args', $args) ); //Get events first, so we know how many there are in advance
			if ( count($events) > 1 || $page > 1 || get_option('dbem_display_calendar_day_single') == 1 ) {
				$args['limit'] = get_option('dbem_events_default_limit');
				$args['offset'] = $args['limit'] * ($page-1);
				$content =  EM_Events::output($events, apply_filters('em_content_calendar_day_output_args', $args) );
			} elseif( count($events) == 1 ) {
				$EM_Event = $events[0];
				$content =  $EM_Event->output_single();
			} else {
				$content = get_option('dbem_no_events_message');
			}
		} elseif ( !empty($_REQUEST['location_id']) && is_numeric($_REQUEST['location_id']) ) {
			//Just a single location
			$location = new EM_Location($_REQUEST['location_id']);
			$content =  $location->output_single();
		} elseif ( !empty($_REQUEST['event_id']) && is_numeric($_REQUEST['event_id']) ) {
			// single event page
			$event = new EM_Event( $_REQUEST['event_id'] );
			$content =  $event->output_single();
		} else {
			// Multiple events page
			$scope = ( !empty($_REQUEST['scope']) ) ? EM_Object::sanitize($_REQUEST['scope']) : "future";
			//If we have a $_GET['page'] var, use it to calculate the offset/limit ratios (safer than offset/limit get vars)
			$args = array(				
				'orderby' => get_option('dbem_events_default_orderby'),
				'order' => get_option('dbem_events_default_order'),
				'scope' => $scope
			);
			if ( !empty($_REQUEST['category_id']) ) $args['category'] = $_REQUEST['category_id'];
			if (get_option ( 'dbem_display_calendar_in_events_page' )){
				$args['full'] = 1;
				$args['long_events'] = get_option('dbem_full_calendar_long_events');
				$content =  EM_Calendar::output( apply_filters('em_content_calendar_args', $args) );
			}else{
				$args['limit'] = get_option('dbem_events_default_limit');
				$args['pagination'] = 1;	
				$args['page'] = ( !empty($_GET['page']) && is_numeric($_GET['page']) )? $_GET['page'] : 1;
				$content =  EM_Events::output( apply_filters('em_content_events_args', $args) );
			}
		}
		//If disable rewrite flag is on, then we need to add a placeholder here
		if( get_option('dbem_disable_title_rewrites') == 1 ){
			$content = str_replace('#_PAGETITLE', em_events_page_title(''), get_option('dbem_title_html')) . $content;
		}
		//TODO FILTER - filter em page content before display
		return apply_filters('em_content', '<div id="em-wrapper">'.$content.'</div>');
	}
	return $content;
}
add_filter ( 'the_content', 'em_content' );

/**
 * Filter for titles when on event pages
 * @param $data
 * @return string
 */
function em_events_page_title($content) {
	global $EM_Event;
	global $post;
	$events_page_id = get_option ( 'dbem_events_page' );
	
	if ( $post->ID == $events_page_id && $events_page_id != 0 ) {
		if (isset ( $_REQUEST['calendar_day'] ) && $_REQUEST['calendar_day'] != '') {
			$events = EM_Events::get(array('limit'=>2,'scope'=>$_REQUEST['calendar_day']));
			if ( count($events) != 1 || get_option('dbem_display_calendar_day_single') == 1 ) {
				//We only support dates for the calendar day list title, so we do a simple filter for the supplied calendar_day
				$content = get_option ('dbem_list_date_title');
				preg_match_all("/#[A-Za-z0-9]+/", $content, $placeholders);
				foreach($placeholders[0] as $placeholder) {
					// matches all PHP date and time placeholders
					if (preg_match('/^#[dDjlNSwzWFmMntLoYyaABgGhHisueIOPTZcrU]$/', $placeholder)) {
						$content = str_replace($placeholder, mysql2date(ltrim($placeholder, "#"), $_REQUEST['calendar_day']),$content );
					}
				}
			}else{
				$event = array_shift($events);
				$content =  $event->output( get_option('dbem_event_page_title_format') );
			}
		}elseif (isset ( $_REQUEST ['location_id'] ) && $_REQUEST ['location_id'] |= '') {
			$location = new EM_Location( EM_Object::sanitize($_REQUEST ['location_id']) );
			$content =  $location->output(get_option( 'dbem_location_page_title_format' ));;
		}elseif (isset ( $_REQUEST ['event_id'] ) && $_REQUEST ['event_id'] != '') {
			// single event page
			$content =  $EM_Event->output ( get_option ( 'dbem_event_page_title_format' ) );
		}else{
			// Multiple events page
			$content =  get_option ( 'dbem_events_page_title' );
		}
		//TODO FILTER - filter titles before em output
	}
	return apply_filters('em_events_page_title', $content);
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
	if( get_option('dbem_disable_title_rewrites') != 1 ){
		if ( $wp_query->in_the_loop ) {
			return em_events_page_title($data) ;
		}
	}
	return apply_filters('em_wp_the_title', $data) ;
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
		return apply_filters('em_filter_get_pages', $output);
	}
	return apply_filters('em_filter_get_pages', $data);
}
add_filter ( 'get_pages', 'em_filter_get_pages' );

?>