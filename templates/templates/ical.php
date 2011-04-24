<?php
			//send headers
			if( !get_option('dbem_regenerate_ical') ){
				header('Content-type: text/calendar; charset=utf-8');
				header('Content-Disposition: inline; filename="events.ics"');
			}
					
			$description_format = str_replace ( ">", "&gt;", str_replace ( "<", "&lt;", get_option ( 'dbem_ical_description_format' ) ) );
			$EM_Events = new EM_Events( array( get_option('dbem_ical_limit'), 'owner'=>false, 'orderby'=>'event_start_date' ) );
			
			$blog_desc = ent2ncr(convert_chars(strip_tags(get_bloginfo()))) . " - " . __('Calendar','dbem');
			
echo "BEGIN:VCALENDAR
METHOD:PUBLISH
CALSCALE:GREGORIAN
VERSION:2.0
PRODID:-//Events Manager//1.0//EN
X-WR-CALNAME:{$blog_desc}";
			/* @var EM_Event $EM_Event */
			foreach ( $EM_Events as $EM_Event ) {
			
				$description = $EM_Event->output($description_format);
				$description = ent2ncr(convert_chars(strip_tags($description)));
				
				$dateStart	= date('Ymd\THis\Z',$EM_Event->start);
				$dateEnd = date('Ymd\THis\Z',$EM_Event->end);	
				$dateModified = date('Ymd\THis\Z', $EM_Event->modified);			
				
				$location		= $EM_Event->output('#_LOCATION');
				$location		= ent2ncr(convert_chars(strip_tags($location)));
				
				$categories = array();
				foreach($EM_Event->get_categories() as $EM_Category){
					$categories[] = $EM_Category->name;
				}
	
//FIXME we need a modified date for events
echo "
BEGIN:VEVENT
UID:{$EM_Event->id}
DTSTART:{$dateStart}
DTEND:{$dateEnd}
DTSTAMP:{$dateModified}
ORGANIZER:MAILTO:{$EM_Event->contact->user_email}
CATEGORIES:".implode(',',$categories)."
LOCATION:{$location}
SUMMARY:{$description}
END:VEVENT";
			}
			echo "\r\n"."END:VCALENDAR";