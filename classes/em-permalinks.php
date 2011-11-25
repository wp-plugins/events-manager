<?php

if( !class_exists('EM_Permalinks') ){
	class EM_Permalinks {
		static $em_queryvars = array(
			'event_id',
			'location_id',
			'person_id',
			'booking_id',
			'category_id', 'category_slug',
			'ticket_id', 'scope',
			'calendar_day',
			'rss', 'page', 'bookings_page', 'payment_gateway','event_categories','event_locations'
		);
		
		function init(){
			add_filter('pre_update_option_dbem_events_page', array('EM_Permalinks','option_update'));
			add_filter('init', array('EM_Permalinks','flush'));
			add_filter('rewrite_rules_array',array('EM_Permalinks','rewrite_rules_array'));
			add_filter('query_vars',array('EM_Permalinks','query_vars'));
			add_action('template_redirect',array('EM_Permalinks','init_objects'), 1);
			add_action('template_redirect',array('EM_Permalinks','redirection'), 1);
			if( !defined('EM_LOCATIONS_SLUG') ){ define('EM_LOCATIONS_SLUG','locations'); }
			if( !defined('EM_CATEGORY_SLUG') ){ define('EM_CATEGORY_SLUG','category'); }
			if( !defined('EM_CATEGORIES_SLUG') ){ define('EM_CATEGORIES_SLUG','categories'); }
		}
		
		function flush(){
			global $wp_rewrite;
			if( get_option('dbem_flush_needed') ){
			   	$wp_rewrite->flush_rules();
				delete_option('dbem_flush_needed');
			}
		}
		
		/**
		 * will redirect old links to new link structures.
		 * @return mixed
		 */
		function redirection(){
			global $wp_rewrite, $post, $wp_query;
			if( $wp_rewrite->using_permalinks() && !is_admin() && !defined('EM_DISABLE_PERMALINKS') ){
				//is this a querystring url?
				$events_page_id = get_option ( 'dbem_events_page' );
				if ( is_object($post) && $post->ID == $events_page_id && $events_page_id != 0 ) {
					$page = ( !empty($_GET['page']) && is_numeric($_GET['page']) )? $_GET['page'] : '';
					if ( !empty($_GET['calendar_day']) ) {
						//Events for a specific day
						wp_redirect( self::url($_GET['calendar_day'],$page), 301);
						exit();
					} elseif ( !empty($_GET['location_id']) && is_numeric($_GET['location_id']) ) {
						//Just a single location
						$EM_Location = new EM_Location($_GET['location_id']);
						wp_redirect( self::url('location', $EM_Location->slug,$page), 301);
						exit();
					} elseif ( !empty($_GET['event_id']) && is_numeric($_GET['event_id']) ) {
						//single event page
						$EM_Event = new EM_Event($_GET['event_id']);
						wp_redirect( self::url(EM_EVENT_SLUG, $EM_Event->slug), 301);
						exit();
					}			
				}
				if( !empty($_GET['dbem_rss']) ){
					//RSS page
					wp_redirect( self::url('rss'), 301);
					exit();
				}
			}
		}		
		// Adding a new rule
		function rewrite_rules_array($rules){
			//get the slug of the event page
			$events_page_id = get_option ( 'dbem_events_page' );
			$events_page = get_post($events_page_id);
			$em_rules = array();
			if( is_object($events_page) ){
				$events_slug = $events_page->post_name;
				$em_rules[$events_slug.'/(\d{4}-\d{2}-\d{2})$'] = 'index.php?pagename='.$events_slug.'&calendar_day=$matches[1]'; //event calendar date search
				$em_rules[$events_slug.'/my\-bookings$'] = 'index.php?pagename='.$events_slug.'&bookings_page=1'; //page for users to manage bookings
				$em_rules[$events_slug.'/rss$'] = 'index.php?pagename='.$events_slug.'&rss=1'; //rss page
				$em_rules[$events_slug.'/feed$'] = 'index.php?pagename='.$events_slug.'&rss=1'; //compatible rss page
				$em_rules[$events_slug.'/payments/(.+)$'] = 'index.php?pagename='.$events_slug.'&payment_gateway=$matches[1]'; //single event booking form with slug
				if( EM_POST_TYPE_EVENT_SLUG == $events_slug ){
					//make sure we hard-code rewrites for child pages of events
					$child_posts = get_posts(array('post_type'=>'page', 'post_parent'=>$events_page->ID));
					foreach($child_posts as $child_post){
						$em_rules[$events_slug.'/'.$child_post->post_name.'/?$'] = 'index.php?page_id='.$child_post->ID; //single event booking form with slug
					}		
				}
			}else{
				$events_slug = EM_POST_TYPE_EVENT_SLUG;
				$em_rules[$events_slug.'/(\d{4}-\d{2}-\d{2})$'] = 'index.php?post_type='.EM_POST_TYPE_EVENT.'&scope=$matches[1]'; //event calendar date search
				$em_rules[$events_slug.'/my\-bookings$'] = 'index.php?post_type='.EM_POST_TYPE_EVENT.'&bookings_page=1'; //page for users to manage bookings
				$em_rules[$events_slug.'/rss$'] = 'index.php?post_type='.EM_POST_TYPE_EVENT.'&rss=1'; //rss page
				$em_rules[$events_slug.'/payments/(.+)$'] = 'index.php?post_type='.EM_POST_TYPE_EVENT.'&payment_gateway=$matches[1]'; //single event booking form with slug
			}
			return $em_rules + $rules;
		}
		
		/**
		 * Depreciated, use get_post_permalink() from now on or the output function with a placeholder
		 * Generate a URL. Pass each section of a link as a parameter, e.g. EM_Permalinks::url('event',$event_id); will create an event link.
		 * @param mixed 
		 */
		function url(){
			global $wp_rewrite;
			$args = func_get_args();
			$em_uri = get_permalink(get_option("dbem_events_page")); //PAGE URI OF EM
			if ( $wp_rewrite->using_permalinks() /*&& !defined('EM_DISABLE_PERMALINKS')*/ ) {
				$event_link = trailingslashit(trailingslashit($em_uri). implode('/',$args));
			}
			return $event_link;
		}
		
		/**
		 * checks if the events page has changed, and sets a flag to flush wp_rewrite.
		 * @param mixed $val
		 * @return mixed
		 */
		function option_update( $val ){
			if( get_option('dbem_events_page') != $val ){
				update_option('dbem_flush_needed',1);
			}
		   	return $val;
		}
		
		// Adding the id var so that WP recognizes it
		function query_vars($vars){
			foreach(self::$em_queryvars as $em_queryvar){
				array_push($vars, $em_queryvar);
			}
		    return $vars;
		}
		
		/**
		 * Not the "WP way" but for now this'll do! 
		 */
		function init_objects(){
			//Build permalinks here
			global $wp_query, $wp_rewrite;
			if ( $wp_rewrite->using_permalinks() ) {
				foreach(self::$em_queryvars as $em_queryvar){
					if( $wp_query->get($em_queryvar) ) {
						$_REQUEST[$em_queryvar] = $wp_query->get($em_queryvar);
					}
				}
		    }
			//dirty rss condition
			if( !empty($_REQUEST['rss']) ){
				$_REQUEST['rss_main'] = 'main';
			}
		}
	}
	EM_Permalinks::init();
}

//Specific links that aren't generated by objects

/**
 * returns the url of the my bookings page, depending on the settings page and if BP is installed.
 * @return string
 */
function em_get_my_bookings_url(){
	global $bp, $wp_rewrite;
	if( is_object($bp) ){
		//get member url
		return $bp->events->link.'attending/';
	}elseif( get_option('dbem_bookings_my_page') ){
		return get_permalink(get_option('dbem_bookings_my_page'));
	}else{
		if( $wp_rewrite->using_permalinks() && !defined('EM_DISABLE_PERMALINKS') ){
			return trailingslashit(EM_URI)."my-bookings/";
		}else{
			return preg_match('/\?/',EM_URI) ? EM_URI.'&bookings_page=1':EM_URI.'?bookings_page=1';
		}
	}
}
