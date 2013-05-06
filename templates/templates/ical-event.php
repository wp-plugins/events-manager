<?php
/* @var $EM_Event EM_Event */
global $EM_Event;
		
$description_format = str_replace ( ">", "&gt;", str_replace ( "<", "&lt;", get_option ( 'dbem_ical_description_format' ) ) );
			
$output = "BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//wp-events-plugin.com//".EM_VERSION."//EN"."\n";
echo preg_replace("/([^\r])\n/", "$1\r\n", $output);

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
	
	//Formats
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
	
$output = "
BEGIN:VEVENT
DTSTART{$dateStart}
DTEND{$dateEnd}
DTSTAMP:{$dateModified}
SUMMARY:{$description}
LOCATION:{$location}
URL:{$EM_Event->output('#_EVENTURL')}
END:VEVENT
END:VCALENDAR";
echo preg_replace("/([^\r])\n/", "$1\r\n", $output);