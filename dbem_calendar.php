<?php

function dbem_get_calendar($month="") {
	
	global $wpdb;
//	if (!($month))
	$date = mktime(0,0,0,date('m'), date('d'), date('Y')); 
	$day = date('d', $date); 
	$month = date('m', $date); 
	$year = date('Y', $date); 
	// Get the first day of the month 
	$month_start = mktime(0,0,0,$month, 1, $year);
	// Get friendly month name  
	
	$month_name = mysql2date('M', "$year-$month-$day 00:00:00");
	// Figure out which day of the week 
	// the month starts on. 
	$month_start_day = date('D', $month_start);
	switch($month_start_day){ 
	    case "Sun": $offset = 6; break; 
	    case "Mon": $offset = 0; break; 
	    case "Tue": $offset = 1; break; 
	    case "Wed": $offset = 2; break; 
	    case "Thu": $offset = 3; break; 
	    case "Fri": $offset = 4; break; 
	    case "Sat": $offset = 5; break;
	}
	
	// determine how many days are in the last month. 
	if($month == 1) { 
	   $num_days_last = dbem_days_in_month(12, ($year -1)); 
	} else { 
	  $num_days_last = dbem_days_in_month(($month-1), $year); 
	}
	// determine how many days are in the current month. 
	$num_days_current = dbem_days_in_month($month, $year);
	// Build an array for the current days 
	// in the month 
	for($i = 1; $i <= $num_days_current; $i++){ 
	   $num_days_array[] = mktime(0,0,0,date('m'), $i, date('Y')); 
	}
	// Build an array for the number of days 
	// in last month 
	for($i = 1; $i <= $num_days_last; $i++){ 
	    $num_days_last_array[] = $i; 
	}
	// If the $offset from the starting day of the 
	// week happens to be Sunday, $offset would be 0, 
	// so don't need an offset correction. 

	if($offset > 0){ 
	    $offset_correction = array_slice($num_days_last_array, -$offset, $offset); 
	    $new_count = array_merge($offset_correction, $num_days_array); 
	    $offset_count = count($offset_correction); 
	} 

	// The else statement is to prevent building the $offset array. 
	else { 
	    $offset_count = 0; 
	    $new_count = $num_days_array;
	}
	// count how many days we have with the two 
	// previous arrays merged together 
	$current_num = count($new_count); 

	// Since we will have 5 HTML table rows (TR) 
	// with 7 table data entries (TD) 
	// we need to fill in 35 TDs 
	// so, we will have to figure out 
	// how many days to appened to the end 
	// of the final array to make it 35 days. 


	if($current_num > 35){ 
	   $num_weeks = 6; 
	   $outset = (42 - $current_num); 
	} elseif($current_num < 35){ 
	   $num_weeks = 5; 
	   $outset = (35 - $current_num); 
	} 
	if($current_num == 35){ 
	   $num_weeks = 5; 
	   $outset = 0; 
	} 
	// Outset Correction 
	for($i = 1; $i <= $outset; $i++){ 
	   $new_count[] = $i; 
	}
	// Now let's "chunk" the $all_days array 
	// into weeks. Each week has 7 days 
	// so we will array_chunk it into 7 days. 
	$weeks = array_chunk($new_count, 7); 
	
	

	// Build Previous and Next Links 
	$previous_link = "<a href=\"".$_SERVER['PHP_SELF']."?date="; 
	if($month == 1){ 
	   $previous_link .= mktime(0,0,0,12,$day,($year -1)); 
	} else { 
	   $previous_link .= mktime(0,0,0,($month -1),$day,$year); 
	} 
	$previous_link .= "\">&lt;&lt; Prev</a>"; 

	$next_link = "<a href=\"".$_SERVER['PHP_SELF']."?date="; 
	if($month == 12){ 
	   $next_link .= mktime(0,0,0,1,$day,($year + 1)); 
	} else { 
	   $next_link .= mktime(0,0,0,($month +1),$day,$year); 
	} 
	$next_link .= "\">Next >></a>"; 

	$calendar="<div id='dbem-calendar'>";
	
	$days_initials = "<td>".dbem_translate_and_trim("Monday")."</td><td>".dbem_translate_and_trim("Tuesday")."</td><td>".dbem_translate_and_trim("Wednesday")."</td><td>".dbem_translate_and_trim("Thursday")."</td><td>".dbem_translate_and_trim("Friday")."</td><td>".dbem_translate_and_trim("Saturday")."</td><td>".dbem_translate_and_trim("Sunday")."</td>\n";
	
	// Build the heading portion of the calendar table 
	$calendar .=  "<table id='dbem-calendar-table'>\n". 
	   	"<thead>\n<tr>\n".
		"<td colspan='7'>$month_name $year</td>\n". 
		"</tr>\n</thead>\n".	
	    "<tr class='days-names'>\n". 
	    $days_initials. 
	    "</tr>\n"; 

	// Now we break each key of the array  
	// into a week and create a new table row for each 
	// week with the days of that week in the table data 

	$i = 0; 
	foreach($weeks as $week){ 
	       $calendar .= "<tr>\n"; 
	       foreach($week as $d){ 
	         if($i < $offset_count){ //if it is PREVIOUS month
	             $calendar .= "<td class='eventless-pre'>$d</td>\n"; 
	         } 
		         if(($i >= $offset_count) && ($i < ($num_weeks * 7) - $outset)){ // if it is THIS month
	        	$fullday=$d;
				$d=date('j', $d);
				$day_link = "$d"; 
	           	if($date == mktime(0,0,0,$month,$d,$year)){ 
	               $calendar .= "<td class='eventless-today'>$d</td>\n"; 
	           } else { 
	               $calendar .= "<td class='eventless'>$day_link</td>\n"; 
	           } 
	        } elseif(($outset > 0)) { //if it is NEXT month
	            if(($i >= ($num_weeks * 7) - $outset)){ 
	               $calendar .= "<td class='eventless-post'>$d</td>\n"; 
	           } 
	        } 
	        $i++; 
	      } 
	      $calendar .= "</tr>\n";    
	} 
	  // $calendar .= "<tr>\n". 
	  // 	     "<td colspan='7'>\n". 
	  // 	     "<table align='center'>\n". 
	  // 	     "<tr class='month-navigation'>\n". 
	  // 	     "<td class='previous-month' colspan='2' align='left'>$previous_link</td>\n". 
	  // 	     "<td colspan='3'>&nbsp;</td>".	     
	  // 		"<td class='next-month' colspan='2'  align='right'>$next_link</td>\n". 
	  // 	     "</tr>\n". 
	  // 	     "</table>\n". 
	  // 	     "</td>\n</tr>\n";
	  	$calendar .= " </table>\n</div>";
	
	// query the database for events in this time span
	if ($month == 1) {
		$month_pre=12;
		$month_post=2;
		$year_pre=$year-1;
		$year_post=$year;
	} elseif($month == 12) {
		$month_pre=11;
		$month_post=1;
		$year_pre=$year;
		$year_post=$year+1;
	} else {
			$month_pre=$month-1;
			$month_post=$month+1;
			$year_pre=$year;
			$year_post=$year;
	}
	$limit_pre=date("Y-m-d", mktime(0,0,0,$month_pre, 1 , $year_pre));
	$limit_post=date("Y-m-d", mktime(0,0,0,$month_post, 30 , $year_post));
	$events_table = $wpdb->prefix.EVENTS_TBNAME; 
	$sql = "SELECT event_id, 
									event_name, 
								 	event_start_date, 
									DATE_FORMAT(event_start_date, '%w') AS 'event_weekday_n',
									DATE_FORMAT(event_start_date, '%e') AS 'event_day',
									DATE_FORMAT(event_start_date, '%c') AS 'event_month_n',
									DATE_FORMAT(event_start_time, '%Y') AS 'event_year',
									DATE_FORMAT(event_start_time, '%k') AS 'event_hh',
									DATE_FORMAT(event_start_time, '%i') AS 'event_mm'
		FROM $events_table WHERE event_start_date BETWEEN '$limit_pre' AND '$limit_post' ORDER BY event_start_date";               

	$events=$wpdb->get_results($sql);   

//----- DEBUG ------------
//foreach($events as $event) { //DEBUG
//	$calendar .= ("$event->event_day / $event->event_month_n - $event->event_name<br/>");
//}
// ------------------
	// inserts the events 
     
$events_page = get_option('dbem_events_page');
if($events){	
	foreach($events as $event) { 
		if ($event->event_month_n == $month_pre) {
			$calendar=str_replace("<td class='eventless-pre'>$event->event_day</td>","<td class='eventful-pre'><a href='?page_id=$events_page&amp;calendar_day="."$event->event_start_date'>$event->event_day</a></td>",$calendar);
		} elseif($event->event_month_n == $month_post) {
			$calendar=str_replace("<td class='eventless-post'>$event->event_day</td>","<td class='eventful-post'><a href='?page_id=$events_page&amp;calendar_day="."$event->event_start_date'>$event->event_day</a></td>",$calendar);
		} elseif($event->event_day == $day) {
			$calendar=str_replace("<td class='eventless-today'>$event->event_day</td>","<td class='eventful-today'><a href='?page_id=$events_page&amp;calendar_day="."$event->event_start_date'>$event->event_day</a></td>",$calendar);
		} else{
			$calendar=str_replace("<td class='eventless'>$event->event_day</td>","<td class='eventful'><a href='?page_id=$events_page&amp;calendar_day="."$event->event_start_date'>$event->event_day</a></td>",$calendar);
		}
	}
}
	$output=$calendar;
	
	echo $output;
}

function dbem_days_in_month($month, $year) {
	switch ($month) {
		case (2):
			if ($year % 4 == 0 && ($year % 100 != 0 || $year % 400 == 0)) {
				return 29;
			} else {
				return 28;
			}
		break;
		case (4):
			return 30;
		break;
		case (6):
			return 30;
		break;
		case (9):
			return 30;
		break;
		case (11):
			return 30;
		break;
		default:
			return 31;
		break;
	}		
}

function dbem_calendar_style() {
	?>
	<style type="text/css"> 
	#dbem-calendar td {
		padding: 2px 4px; 
		text-align: center;
	}
	#dbem-calendar tr.days-names {
		font-weight: bold;
	} 
	#dbem-calendar td.eventless-pre, #dbem-calendar td.eventless-post {
		color: #ccc;
	}
	#dbem-calendar td.eventful a {
	  font-weight: bold;
	  color: #FD7E29;
	}
	 #dbem-calendar td.eventless-today {
	   background-color: #CFCFCF;  
	}
	#dbem-calendar thead {
		font-size: 120%;  
		font-weight: bold;  
	}
	</style>
	<?php
}
add_action('wp_head', 'dbem_calendar_style');
 
function dbem_translate_and_trim($string, $length = 1) {
	return substr(__($string), 0, $length);
}
?>