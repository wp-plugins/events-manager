<?php

 

function dbem_recurrence_test() {
	echo "<h2>Recurrence iCalendar</h2>";   
	
	echo "<h3>Daily, every other day</h3>";  
	$recurrence = array('recurrence_start_date' => '2009-02-10', 'recurrence_end_date' => '2009-03-10', 'recurrence_freq'=>'daily' , 'recurrence_interval' => 2); 
	$matching_days = dbem_get_recurrence_events($recurrence);
	
	print_r($recurrence);    
  //echo "<br/>every_N = $every_N - month_position = $month_position";     
	echo "<br/>";
	echo "<ul>";
	foreach($matching_days as $day) {
		echo"<li>".date("D d M Y", $day)."</li>";
	}	          
	echo "</ul>";
	
	echo "<h3>Weekly</h3>";
	$recurrence = array('recurrence_start_date' => '2009-02-10', 'recurrence_end_date' => '2009-04-24', 'recurrence_freq'=>'weekly', 'recurrence_byday'=>2 , 'recurrence_interval' => 3); 
	$matching_days = dbem_get_recurrence_events($recurrence);
	
	print_r($recurrence);    
  //echo "<br/>every_N = $every_N - month_position = $month_position";     
	echo "<br/>";
	echo "<ul>";
	foreach($matching_days as $day) {
		echo"<li>".date("D d M Y", $day)."</li>";
	}	          
	echo "</ul>";  
	
	echo "<h3>Monthly, second week</h3>";
	$recurrence = array('recurrence_start_date' => '2009-02-10', 'recurrence_end_date' => '2009-04-24', 'recurrence_freq'=>'monthly', 'recurrence_byday' => 2, 'recurrence_byweekno'=>2 , 'recurrence_interval' => 1); 
	$matching_days = dbem_get_recurrence_events($recurrence);
	
	print_r($recurrence);    
  //echo "<br/>every_N = $every_N - month_position = $month_position";     
	echo "<br/>";
	echo "<ul>";
	foreach($matching_days as $day) {
		echo"<li>".date("D d M Y", $day)."</li>";
	}	          
	echo "</ul>"; 
	
	echo "<h3>Last week of the month</h3>";  
	$recurrence = array('recurrence_start_date' => '2009-02-10', 'recurrence_end_date' => '2009-04-24', 'recurrence_freq'=>'monthly', 'recurrence_byday' => 2, 'recurrence_byweekno'=> -1 , 'recurrence_interval' => 1); 
	$matching_days = dbem_get_recurrence_events($recurrence);
	
	print_r($recurrence);    
  //echo "<br/>every_N = $every_N - month_position = $month_position";     
	echo "<br/>";
	echo "<ul>";
	foreach($matching_days as $day) {
		echo"<li>".date("D d M Y", $day)."</li>";
	}	          
	echo "</ul>";
}         

function dbem_get_recurrence_events($recurrence){
	//print_r($recurrence);
	$start_date = mktime(0, 0, 0, substr($recurrence['recurrence_start_date'],5,2), substr($recurrence['recurrence_start_date'],8,2), substr($recurrence['recurrence_start_date'],0,4));
	$end_date = mktime(0, 0, 0, substr($recurrence['recurrence_end_date'],5,2), substr($recurrence['recurrence_end_date'],8,2), substr($recurrence['recurrence_end_date'],0,4));     
 
	$every_keys = array('every' => 1, 'every_second' => 2, 'every_third' => 3, 'every_fourth' => 4);  
	$every_N = $every_keys[$recurrence['recurrence_modifier']]; 
 
	$month_position_keys = array('first_of_month'=>1, 'second_of_month' => 2, 'third_of_month' => 3, 'fourth_of_month' => 4);
	$month_position = $month_position_keys[$recurrence['recurrence_modifier']]; 
	
	$last_week_start = array(25, 22, 25, 24, 25, 24, 25, 25, 24, 25, 24, 25);
	
	$weekdays = explode(",", $recurrence['recurrence_byday']);
	print_r($weekdays);
	
	$weekcounter = 0;
	$daycounter = 0; 
	$counter = 0;
	$cycle_date = $start_date;     
	$matching_days = array(); 
	$aDay = 86400;  // a day in seconds  
 
  
	while (date("d-M-Y", $cycle_date) != date('d-M-Y', $end_date + $aDay)) {
 	 //echo (date("d-M-Y", $cycle_date));
		$style = "";
		$monthweek =  floor(((date("d", $cycle_date)-1)/7))+1;   
		 if($recurrence['recurrence_freq'] == 'daily') {
				if($counter % $recurrence['recurrence_interval'] == 0 )
					array_push($matching_days, $cycle_date);
				$counter++;
		}
	     
		if (in_array(date("N", $cycle_date), $weekdays )) {
			
			$monthday = date("j", $cycle_date); 
			$month = date("n", $cycle_date);      

			if($recurrence['recurrence_freq'] == 'weekly') {
				if($counter % $recurrence['recurrence_interval'] == 0 )
					array_push($matching_days, $cycle_date);
				$counter++;
			}
			if($recurrence['recurrence_freq'] == 'monthly') { 
	
		   	if(($recurrence['recurrence_byweekno'] == -1) && ($monthday >= $last_week_start[$month-1])) {
					if ($counter % $recurrence['recurrence_interval'] == 0)
						array_push($matching_days, $cycle_date);
					$counter++;
				} elseif($recurrence['recurrence_byweekno'] == $monthweek) {
					if ($counter % $recurrence['recurrence_interval'] == 0)
						array_push($matching_days, $cycle_date);
					$counter++;
			  }
			}
			$weekcounter++;
	  }
		$daycounter++;
	  $cycle_date = $cycle_date + $aDay;         //adding a day        
	}   
	
	
	return $matching_days ;
	
}



///////////////////////////////////////////////


function dbem_insert_recurrent_event($event, $recurrence ){
 	 	
		global $wpdb;
		$recurrence_table = $wpdb->prefix.RECURRENCE_TBNAME;
		$events_table = $wpdb->prefix.EVENTS_TBNAME;
		if (true) {
			$wpdb->show_errors(true);
			$wpdb->insert($recurrence_table, $recurrence);
		 	$recurrence_id = mysql_insert_id();
			$output = "<h2>Recurring</h2>";
			print_r($recurrence); 
			echo "recurrence_id = $recurrence_id<br/>";  

			$matching_days = array();
			// if($recurrence['recurrence_freq'] == "weekly") {
			// 		foreach($recurrence['recurrence_bydays'] as $weekday) {  
			// 			
			// 			$recurrence['recurrence_byday'] = $weekday;                                      
			// 			//$new_events =
			// 	  	$matching_days = array_merge($matching_days, dbem_get_recurrence_events($recurrence));
			// 		}
			// 			
			// } else {  
		  $matching_days = dbem_get_recurrence_events($recurrence);
			//} 
			sort($matching_days);
			
			foreach($matching_days as $day) {
				$new_event['event_name'] = $recurrence['recurrence_name'];
				$new_event['event_start_time'] = $recurrence['recurrence_start_time'];
				$new_event['event_end_time'] = $recurrence['recurrence_end_time'];   
				$new_event['event_rsvp'] = $recurrence['recurrence_rsvp'];
				$new_event['event_seats'] = $recurrence['recurrence_seats'];
				$new_event['venue_id'] = $recurrence['venue_id'];
				$new_event['recurrence_id'] = $recurrence_id;
				$new_event['event_start_date'] = date("Y-m-d", $day); 
				//print_r($new_event);
				echo "<br/>";          
				$wpdb->insert($events_table, $new_event);
				//echo date("D d M Y", $day)."<br/>";
			}
			die();                          
	}
}





?>