<?php
class EM_Calendar extends EM_Object {
	
	function init(){
		add_action('wp_head', array('EM_Calendar', 'insert_js'));
	}
	
	function get($args = array()) {
		$args = self::get_default_search($args);
		$full = $args['full']; //For ZDE, don't delete pls
		$month = $args['month']; 
		$year = $args['year'];
		$long_events = ($args['long_events']) ? 1:0;
		
		$week_starts_on_sunday = get_option('dbem_week_starts_sunday');
	   	$start_of_week = get_option('start_of_week');
	
	 	global $wpdb;    
		if( !(is_numeric($month) && $month <= 12 && $month > 0) )   {
			$month = date('m'); 
		}
		if( !( is_numeric($year) ) ){
			$year = date('Y');
		}
		
		$date = mktime(0,0,0,$month, date('d'), $year); 
		$day = date('d', $date); 
		// $month = date('m', $date); 
		// $year = date('Y', $date);       
		// Get the first day of the month 
		$month_start = mktime(0,0,0,$month, 1, $year);
		// Get friendly month name  
		
		$month_name = mysql2date('M', "$year-$month-$day 00:00:00");
		// Figure out which day of the week 
		// the month starts on. 
		$month_start_day = date('D', $month_start);
	  
	  	switch($month_start_day){ 
			case "Sun": $offset = 0; break; 
			case "Mon": $offset = 1; break; 
			case "Tue": $offset = 2; break; 
			case "Wed": $offset = 3; break; 
			case "Thu": $offset = 4; break; 
			case "Fri": $offset = 5; break; 
			case "Sat": $offset = 6; break;
		}       
	   
		$offset -= $start_of_week;
		if($offset<0)
			$offset += 7;
		
		// determine how many days are in the last month. 
		if($month == 1) { 
		   $num_days_last = self::days_in_month(12, ($year -1)); 
		} else { 
		  $num_days_last = self::days_in_month(($month-1), $year); 
		}
		// determine how many days are in the current month. 
		$num_days_current = self::days_in_month($month, $year);
		// Build an array for the current days 
		// in the month 
		for($i = 1; $i <= $num_days_current; $i++){ 
		   $num_days_array[] = mktime(0,0,0,$month, $i, $year); 
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
		$base_link = "?".$_SERVER['QUERY_STRING']."&amp;";       
		
		if($month == 1){ 
			 $back_month = 12;
			 $back_year = $year-1;
		} else { 
		   $back_month = $month -1;
			 $back_year = $year;
		}  
		$full ? $link_extra_class = "full-link" : $link_extra_class = '';
		$previous_link = "<a class='em-calnav $link_extra_class' href='?ajaxCalendar=1&amp;calmonth={$back_month}&amp;calyear={$back_year}&amp;longevents={$long_events}'>&lt;&lt;</a>"; 
	
		if($month == 12){ 
		   $next_month = 1;
			 $next_year = $year+1;
		} else { 
		   $next_month = $month + 1;
			 $next_year = $year;	
		} 
		$next_link = "<a class='em-calnav $link_extra_class' href='?ajaxCalendar=1&amp;calmonth={$next_month}&amp;calyear={$next_year}&amp;longevents={$long_events}'>&gt;&gt;</a>";
		$class = ($full) ? 'dbem-calendar-full' : 'dbem-calendar';
		$calendar="<div class='$class'><div style='display:none' class='month_n'>$month</div><div class='year_n' style='display:none' >$year</div>";
		
	 	$weekdays = array('Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday');
	   $n = 0 ;
		while( $n < $start_of_week ) {   
			$last_day = array_shift($weekdays);     
			$weekdays[]= $last_day; 
			$n++;
		}
	   
		$days_initials = "";
		foreach($weekdays as $weekday) {
			$days_initials .= "<td>".self::translate_and_trim($weekday)."</td>";
		} 
		$full ? $fullclass = 'fullcalendar' : $fullclass='';
		// Build the heading portion of the calendar table 
		$calendar .=  "<table class='dbem-calendar-table $fullclass'>\n". 
		   	"<thead>\n<tr>\n".
			"<td>$previous_link</td><td class='month_name' colspan='5'>$month_name $year</td><td>$next_link</td>\n". 
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
				  	if($d == date('j') && $month == date('m') && $year == date('Y')) {
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
		$events = EM_Events::get( array('year'=>array($year_pre, $year_post), 'month'=>array($month_pre, $month_post)) );
	
		$eventful_days= array();
		if($events){
			//Go through the events and slot them into the right d-m index
			foreach($events as $event) {   
				if( $long_events ){
					//If $long_events is set then show a date as eventful if there is an multi-day event which runs during that day
					$event_start_date = strtotime($event->start_date);
					$event_end_date = strtotime($event->end_date);
					if( $event_end_date == '' ) $event_end_date = $event_start_date;
					while( $event_start_date <= $event_end_date ){
						$event_eventful_date = date('Y-m-d', $event_start_date);
						if( is_array($eventful_days[$event_eventful_date]) ){
							$eventful_days[$event_eventful_date][] = $event; 
						} else {
							$eventful_days[$event_eventful_date] = array($event);  
						}	
						$event_start_date += (60*60*24);				
					}
				}else{
					//Only show events on the day that they start
					if( isset($eventful_days[$event->start_date]) && is_array($eventful_days[$event->start_date]) ){
						$eventful_days[$event->start_date][] = $event; 
					} else {
						$eventful_days[$event->start_date] = array($event);  
					}
				}
			}
		}
	   
		$event_format = get_option('dbem_full_calendar_event_format'); 
		$event_title_format = get_option('dbem_small_calendar_event_title_format');
		$event_title_separator_format = get_option('dbem_small_calendar_event_title_separator');
		$cells = array() ;
		foreach($eventful_days as $day_key => $events) {
			//Set the date into the key
			$event_start_date = explode('-', $day_key);
			$cells[$day_key]['day'] = ltrim($event_start_date[2],'0');  
			$cells[$day_key]['month'] = $event_start_date[1];
			$events_titles = array();
			foreach($events as $event) { 
				$events_titles[] = $event->output($event_title_format);
			}   
			$link_title = implode($event_title_separator_format,$events_titles);       
			
			$events_page_id = get_option('dbem_events_page');
			$event_page_link = get_permalink($events_page_id);
			if (stristr($event_page_link, "?"))
				$joiner = "&amp;";
			else
				$joiner = "?";
			
			
			$cells[$day_key]['cell'] = "<a title='$link_title' href='".$event_page_link.$joiner."calendar_day={$day_key}'>{$cells[$day_key]['day']}</a>";
			if ($full) {
				$cells[$day_key]['cell'] .= "<ul>";
			
				foreach($events as $event) {
					$cells[$day_key]['cell'] .= $event->output($event_format);
				} 
				$cells[$day_key]['cell'] .= "</ul>";  
	   		}
		}      
		
		if($events){
			foreach($cells as $cell) {  
				if ($cell['month'] == $month_pre) {
				 	$calendar=str_replace("<td class='eventless-pre'>".$cell['day']."</td>","<td class='eventful-pre'>".$cell['cell']."</td>",$calendar);
				} elseif($cell['month'] == $month_post) {
				 	$calendar=str_replace("<td class='eventless-post'>".$cell['day']."</td>","<td class='eventful-post'>".$cell['cell']."</td>",$calendar);
				} elseif($cell['day'] == $day && $cell['month'] == date('m')) {
	  			 	$calendar=str_replace("<td class='eventless-today'>".$cell['day']."</td>","<td class='eventful-today'>".$cell['cell']."</td>",$calendar);
				} elseif( $cell['month'] == $month ){   
			    	$calendar=str_replace("<td class='eventless'>".$cell['day']."</td>","<td class='eventful'>".$cell['cell']."</td>",$calendar);
		   		}
			}
		}       
		return $calendar;
	}	

	/**
	 * Echoes the calendar external JS contents directly into the head of the document
	 * @return unknown_type
	 */
	function insert_js() { 
		?>
		<script type='text/javascript'>
		<?php include(WP_PLUGIN_DIR.'/events-manager/includes/js/em_calendar_ajax.js'); ?>	
		</script>
		<?php
	}


	function days_in_month($month, $year) {
		return cal_days_in_month(CAL_GREGORIAN, $month, $year);
	}
	 
	function translate_and_trim($string, $length = 1) {
		return substr(__($string), 0, $length);
	}  
	
	function get_default_search($array=array()){
		$defaults = array( 'full' => 0, 'month' => '', 'year' => '', 'long_events' => 0 );
		return parent::get_default_search($defaults, $array);
	}
} 
add_action('init', array('EM_Calendar', 'init'));
?>