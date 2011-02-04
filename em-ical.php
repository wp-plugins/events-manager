<?php
	/**
	 * generates an ical feed on init if url is correct
	 */
	function em_ical( $regenerate = false ){
		$cal_file_request = preg_match('/calendar.ics/', $_SERVER['REQUEST_URI']); //are we askig for the ics file directly but doesn't exist?
		if ( !empty( $_REQUEST['em_ical']) || $cal_file_request || $regenerate ) {
			
			//send headers
			if( $_REQUEST['em_ical'] != '2' && !$regenerate ){
				header('Content-type: text/calendar; charset=utf-8');
				header('Content-Disposition: inline; filename="calendar.ics"');
			}
			
			ob_start();			
			$description_format = str_replace ( ">", "&gt;", str_replace ( "<", "&lt;", get_option ( 'dbem_ical_description_format' ) ) );
			$events = EM_Events::get( array( get_option('dbem_ical_limit'), 'owner'=>false, 'orderby'=>'event_start_date' ) );
			
			$blog_desc = ent2ncr(convert_chars(strip_tags(get_bloginfo()))) . " - " . __('Calendar','dbem');
			
echo "BEGIN:VCALENDAR
METHOD:PUBLISH
CALSCALE:GREGORIAN
VERSION:2.0
PRODID:-//Events Manager//1.0//EN
X-WR-CALNAME:{$blog_desc}";
			/* @var EM_Event $EM_Event */
			foreach ( $events as $EM_Event ) {
			
				$description = $EM_Event->output($description_format);
				$description = ent2ncr(convert_chars(strip_tags($description)));
				
				$dateStart	= date('Ymd\THis\Z',$EM_Event->start);
				$dateEnd = date('Ymd\THis\Z',$EM_Event->end);	
				$dateModified = date('Ymd\THis\Z', $EM_Event->modified);			
				
				$location		= $EM_Event->output('#_LOCATION');
				$location		= ent2ncr(convert_chars(strip_tags($location)));
				
				$categories = $EM_Event->category->name;
	
//FIXME we need a modified date for events
echo "
BEGIN:VEVENT
UID:{$EM_Event->id}
DTSTART:{$dateStart}
DTEND:{$dateEnd}
DTSTAMP:{$dateModified}
ORGANIZER:MAILTO:{$EM_Event->contact->user_email}
CATEGORIES:{$categories}
LOCATION:{$location}
SUMMARY:{$description}
END:VEVENT";
			}
			echo "\r\n"."END:VCALENDAR";
			
			$calendar = ob_get_clean(); //get the contents to output
			
			//let's create a cache file
			if($regenerate || $cal_file_request){
				$file = fopen( ABSPATH . "/caleadar.ics", 'w');
				if($file){
					fwrite($file, $calendar, strlen($calendar));
					fclose($file); 
				}
			}		
			if($regenerate){
				return ($file == true);
			}
			echo $calendar;		
			die ();
		}
	}
	add_action ( 'init', 'em_ical' );
	
	function em_update_ical($result, $EM_Event){
		em_ical(true);
	}
	add_filter('em_event_save','em_update_ical', 1, 2);
?>