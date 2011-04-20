<?php
/*
 * This file contains the event related hooks in the front end, as well as some event template tags
 */

/**
 * Filters for page content and if an event replaces it with the relevant event data.
 * @param $data
 * @return string
 */
function em_content($page_content) {
	$events_page_id = get_option ( 'dbem_events_page' );
	if ( get_the_ID() == $events_page_id && $events_page_id != 0 ) {
		/*
		echo "<h2>WP_REWRITE</h2>";
		echo "<pre>";
		global $wp_rewrite;
		print_r($wp_rewrite);
		echo "</pre>";
		echo "<h2>WP_QUERY</h2>";
		echo "<pre>";
		global $wp_query;
		print_r($wp_query->query_vars);
		echo "</pre>";
		die();
		*/
		global $wpdb, $wp_query, $EM_Event, $EM_Location, $EM_Category;
		//general defaults
		$args = array(				
			'orderby' => get_option('dbem_events_default_orderby'),
			'order' => get_option('dbem_events_default_order'),
			'owner' => false,
			'pagination' => 1
		);
		$content = apply_filters('em_content_pre', '', $page_content);
		if( empty($content) ){
			if ( !empty($_REQUEST['calendar_day']) ) {
				//Events for a specific day
				$args['scope'] = $_REQUEST['calendar_day'];
				$page = ( !empty($_REQUEST['page']) && is_numeric($_REQUEST['page']) )? $_REQUEST['page'] : 1;
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
			} elseif ( is_object($EM_Location) ) {
				//Just a single location
				$content =  $EM_Location->output_single();
			}elseif ( is_object($EM_Category) ) {
				//Just a single location
				$content =  $EM_Category->output_single();
			} elseif ( $wp_query->get('bookings_page') ) {
				//Bookings Page
				ob_start();
				em_locate_template('templates/my-bookings.php',true);
				$content = ob_get_clean();
			} elseif ( is_object($EM_Event) && !empty($_REQUEST['book']) ) {
				//bookings page
				$content = $EM_Event->output( get_option('dbem_bookings_page') );
			} elseif ( is_object($EM_Event) ) {
				// single event page
				if( $EM_Event->status == 1 ){
					$content =  $EM_Event->output_single();
				}else{
					$content = get_option('dbem_no_events_message');
				}
			}elseif ( !empty($_REQUEST['event_locations']) ){
				$args['limit'] = get_option('dbem_events_default_limit');
				$args['page'] = (!empty($_REQUEST['page']) && is_numeric($_REQUEST['page']) )? $_REQUEST['page'] : 1;				
				$locations = EM_Locations::get( apply_filters('em_content_locations_args', $args) );
				$template = em_locate_template('templates/locations-list.php'); //if successful, this template overrides the settings and defaults, including search
				if( $template ){
					ob_start();
					include($template);
					$content = ob_get_clean();					
				}else{
					if( count($locations) > 0 ){
						$content = EM_Locations::output( $locations );
					}else{
						$content = get_option ( 'dbem_no_locations_message' );
					}
				}	
			}elseif ( !empty($_REQUEST['event_categories']) ){
				$args['limit'] = get_option('dbem_events_default_limit');
				$args['page'] = (!empty($_REQUEST['page']) && is_numeric($_REQUEST['page']) )? $_REQUEST['page'] : 1;				
				$locations = EM_Categories::get( apply_filters('em_content_categories_args', $args) );
				$template = em_locate_template('templates/categories-list.php'); //if successful, this template overrides the settings and defaults, including search
				if( $template ){
					ob_start();
					include($template);
					$content = ob_get_clean();					
				}else{
					if( count($locations) > 0 ){
						$content = EM_Categories::output( $locations );
					}else{
						$content = get_option ( 'dbem_no_categories_message' );
					}
				}			
			} else {
				// Multiple events page
				$scope = ( !empty($_REQUEST['scope']) ) ? $_REQUEST['scope'] : "future";
				//If we have a $_REQUEST['page'] var, use it to calculate the offset/limit ratios (safer than offset/limit get vars)
				$args['scope'] = $scope;
				if ( !empty($_REQUEST['category_id']) ) $args['category'] = $_REQUEST['category_id'];
				if (get_option ( 'dbem_display_calendar_in_events_page' )){
					$args['full'] = 1;
					$args['long_events'] = get_option('dbem_full_calendar_long_events');
					$content =  EM_Calendar::output( apply_filters('em_content_calendar_args', $args) );
				}else{
					$args['limit'] = get_option('dbem_events_default_limit');
					$args['page'] = (!empty($_REQUEST['page']) && is_numeric($_REQUEST['page']) )? $_REQUEST['page'] : 1;				
					/*calculate event list time range */
					$time_limit = get_option('dbem_events_page_time_limit');
					if ( is_numeric($time_limit) && $time_limit > 0 && $scope == 'future'){
						$args['scope'] = date('Y-m-d').",".date('Y-m-t', strtotime('+'.($time_limit-1).' month'));
					}
					//Intercept search request, if defined
					if( !empty($_POST['_wpnonce']) && wp_verify_nonce($_POST['_wpnonce'], 'search_events') && get_option('dbem_events_page_search') ){
						$args = EM_Events::get_post_search($args);
					}
					$events = EM_Events::get( apply_filters('em_content_events_args', $args) );
					$template = em_locate_template('templates/events-list.php'); //if successful, this template overrides the settings and defaults, including search
					if( $template ){
						ob_start();
						include($template);
						$content = ob_get_clean();					
					}else{
						if( count($events) > 0 ){
							$content = EM_Events::output( $events );
						}else{
							$content = get_option ( 'dbem_no_events_message' );
						}
						if( get_option('dbem_events_page_search') ){
							ob_start();
							em_locate_template('templates/events-search.php',true);
							$content = ob_get_clean() . $content;
						}
					}
				}
			}
		}
		//If disable rewrite flag is on, then we need to add a placeholder here
		if( get_option('dbem_disable_title_rewrites') == 1 ){
			$content = str_replace('#_PAGETITLE', em_content_page_title(''), get_option('dbem_title_html')) . $content;
		}
		//Now, we either replace CONTENTS or just replace the whole page
		if( preg_match('/CONTENTS/', $page_content) ){
			$content = str_replace('CONTENTS',$content,$page_content);
		}
		//TODO FILTER - filter em page content before display
		return apply_filters('em_content', '<div id="em-wrapper">'.$content.'</div>');
	}
	return $page_content;
}
add_filter ( 'the_content', 'em_content' );

/**
 * Filter for titles when on event pages
 * @param $data
 * @return string
 */
function em_content_page_title($content) {
	global $EM_Event, $EM_Location, $EM_Category, $wp_query, $post;
	$events_page_id = get_option ( 'dbem_events_page' );
	
	$content = apply_filters('em_content_page_title_pre', '', $content);
	if( empty($content) ){
		if ( $post->ID == $events_page_id && $events_page_id != 0 ) {
			if (isset ( $_REQUEST['calendar_day'] ) && $_REQUEST['calendar_day'] != '') {
				$events = EM_Events::get(array('limit'=>2,'scope'=>$_REQUEST['calendar_day'],'owner'=>false));
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
			}elseif ( is_object($EM_Location) ) {
				$location = new EM_Location( EM_Object::sanitize($_REQUEST ['location_id']) );
				$content =  $location->output(get_option( 'dbem_location_page_title_format' ));
			}elseif ( is_object($EM_Category) ) {
				//Just a single location
				$content =  $EM_Category->output(get_option( 'dbem_category_page_title_format' ));
			}elseif ( $wp_query->get('bookings_page') ) {
				//Bookings Page
				$content = sprintf(__('My %s','dbem'),__('Bookings','dbem'));
			}elseif ( is_object($EM_Event) && !empty($_REQUEST['book']) ) {
				//bookings page
				$content = $EM_Event->output( get_option('dbem_bookings_page_title') );
			}elseif ( is_object($EM_Event) ) {
				// single event page
				if( $EM_Event->status == 1 ){
					$content =  $EM_Event->output ( get_option ( 'dbem_event_page_title_format' ) );
				}else{
					$content = get_option('dbem_events_page_title');
				}
			} elseif ( !empty($_REQUEST['event_categories']) ){
				$content =  get_option ( 'dbem_categories_page_title' );
			}elseif ( !empty($_REQUEST['event_locations']) ){
				$content =  get_option ( 'dbem_locations_page_title' );
			}else{
				// Multiple events page
				$content =  get_option ( 'dbem_events_page_title' );
			}
			//TODO FILTER - filter titles before em output
		}
	}
	return apply_filters('em_content_page_title', $content);
}
add_filter ( 'single_post_title', 'em_content_page_title' ); //Filter for the wp_title of page, can directly reference page title function

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
			return em_content_page_title($data) ;
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