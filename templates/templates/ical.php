<?php
$description_format = str_replace ( ">", "&gt;", str_replace ( "<", "&lt;", get_option ( 'dbem_ical_description_format' ) ) );

//get first round of events to show, we'll start adding more via the while loop
if( !empty($_REQUEST['event_id']) ){
	$EM_Events = array(em_get_event($_REQUEST['event_id']));
}else{
	$args = apply_filters('em_calendar_template_args',array('limit'=>'50', 'page'=>'1', 'owner'=>false, 'orderby'=>'event_start_date', 'scope' => get_option('dbem_ical_scope') ));
	$EM_Events = EM_Events::get( $args );
}

//calendar header
$output = "BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//wp-events-plugin.com//".EM_VERSION."//EN"."\n";
echo preg_replace("/([^\r])\n/", "$1\r\n", $output);

//loop through events
$count = 0;
while ( count($EM_Events) > 0 ){
	foreach ( $EM_Events as $EM_Event ) {
		/* @var $EM_Event EM_Event */
	    if( get_option('dbem_ical_limit') != 0 && $count > get_option('dbem_ical_limit') ) break; //we've reached our maximum
	    //calculate the times along with timezone offsets
		if($EM_Event->event_all_day){
			$dateStart	= ';VALUE=DATE:'.date('Ymd',$EM_Event->start); //all day
			$dateEnd	= ';VALUE=DATE:'.date('Ymd',$EM_Event->end + 86400); //add one day
		}else{
			$dateStart	= ':'.get_gmt_from_date(date('Y-m-d H:i:s', $EM_Event->start), 'Ymd\THis\Z');
			$dateEnd = ':'.get_gmt_from_date(date('Y-m-d H:i:s', $EM_Event->end), 'Ymd\THis\Z');
		}
		if( !empty($EM_Event->event_date_modified) && $EM_Event->event_date_modified != '0000-00-00 00:00:00' ){
			$dateModified =  get_gmt_from_date($EM_Event->event_date_modified, 'Ymd\THis\Z');
		}else{
		    $dateModified = get_gmt_from_date($EM_Event->post_modified, 'Ymd\THis\Z');
		}
		
		//formats
		$description = $EM_Event->output($description_format,'ical');
		$description = str_replace("\\","\\\\",strip_tags($description));
		$description = str_replace(';','\;',$description);
		$description = str_replace(',','\,',$description);
		
		$location = $EM_Event->output('#_LOCATION', 'ical');
		$location = str_replace("\\","\\\\",strip_tags($location));
		$location = str_replace(';','\;',$location);
		$location = str_replace(',','\,',$location);
		
		$locations = array();
		foreach($EM_Event->get_categories() as $EM_Category){
			$locations[] = $EM_Category->name;
		}
		$UID = sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
	        // 32 bits for "time_low"
	        mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),
	        // 16 bits for "time_mid"
	        mt_rand( 0, 0xffff ),
	        // 16 bits for "time_hi_and_version",
	        // four most significant bits holds version number 4
	        mt_rand( 0, 0x0fff ) | 0x4000,
	        // 16 bits, 8 bits for "clk_seq_hi_res",
	        // 8 bits for "clk_seq_low",
	        // two most significant bits holds zero and one for variant DCE1.1
	        mt_rand( 0, 0x3fff ) | 0x8000,
	        // 48 bits for "node"
	        mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff )
	    );
$output = "
BEGIN:VEVENT
UID:{$UID}
DTSTART{$dateStart}
DTEND{$dateEnd}
DTSTAMP:{$dateModified}
SUMMARY:{$description}
LOCATION:{$location}
URL:{$EM_Event->output('#_EVENTURL')}
END:VEVENT";
		echo preg_replace("/([^\r])\n/", "$1\r\n", $output);
		$count++;
	}
	if( !empty($_REQUEST['event_id']) || (get_option('dbem_ical_limit') != 0 && $count > get_option('dbem_ical_limit')) ){ 
	    //we've reached our limit, or showing one event only
	    break;
	}else{
	    //get next page of results
	    $args['page']++;
		$EM_Events = EM_Events::get( $args );
	}	
}

//calendar footer
$output = "
END:VCALENDAR";
echo preg_replace("/([^\r])\n/", "$1\r\n", $output);