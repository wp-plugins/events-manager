<?php
	/**
	 * generates an ical feed on init if url is correct
	 */
	function em_ical( ){
		//check if this is a calendar request for all events
		if ( preg_match('/events.ics$/', $_SERVER['REQUEST_URI']) || $_SERVER['REQUEST_URI'] == '/?ical=1' ) {
			header('Content-type: text/calendar; charset=utf-8');
			header('Content-Disposition: inline; filename="events.ics"');
			//send headers
			em_locate_template('templates/ical.php', true);
			die();
		}
	}
	add_action ( 'init', 'em_ical' );
	
	/**
	 * Generates an ics file for a single event 
	 */
	function em_ical_event(){
		global $wpdb, $wp_query;
		//add endpoints to events
		if( !empty($wp_query) && $wp_query->get(EM_POST_TYPE_EVENT) && $wp_query->get('ical') ){
			$event_id = $wpdb->get_var('SELECT event_id FROM '.EM_EVENTS_TABLE." WHERE event_slug='".$wp_query->get(EM_POST_TYPE_EVENT)."' AND event_status=1 LIMIT 1");
			if( !empty($event_id) ){
				global $EM_Event;
				$EM_Event = em_get_event($event_id);
				//send headers
				header('Content-type: text/calendar; charset=utf-8');
				header('Content-Disposition: inline; filename="'.$EM_Event->event_slug.'.ics"');
				em_locate_template('templates/ical-event.php', true);
				exit();
			}
		}
	}
	add_action ( 'parse_query', 'em_ical_event' );
?>