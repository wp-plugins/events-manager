<?php

 

function dbem_recurrence_test() {
	echo "<h2>Recurrence iCalendar</h2>";   
	
	echo "<h3>Daily, every other day</h3>";  
	$recurrence = array('start_date' => '2009-02-10', 'end_date' => '2009-03-10', 'freq'=>'daily' , 'interval' => 2); 
	$matching_days = dbem_ical_recurrence_events($recurrence);
	
	print_r($recurrence);    
  //echo "<br/>every_N = $every_N - month_position = $month_position";     
	echo "<br/>";
	echo "<ul>";
	foreach($matching_days as $day) {
		echo"<li>".date("D d M Y", $day)."</li>";
	}	          
	echo "</ul>";
	
	echo "<h3>Weekly</h3>";
	$recurrence = array('start_date' => '2009-02-10', 'end_date' => '2009-04-24', 'freq'=>'weekly', 'byday'=>2 , 'interval' => 3); 
	$matching_days = dbem_ical_recurrence_events($recurrence);
	
	print_r($recurrence);    
  //echo "<br/>every_N = $every_N - month_position = $month_position";     
	echo "<br/>";
	echo "<ul>";
	foreach($matching_days as $day) {
		echo"<li>".date("D d M Y", $day)."</li>";
	}	          
	echo "</ul>";  
	
	echo "<h3>Monthly, second week</h3>";
	$recurrence = array('start_date' => '2009-02-10', 'end_date' => '2009-04-24', 'freq'=>'monthly', 'byday' => 2, 'byweekno'=>2 , 'interval' => 1); 
	$matching_days = dbem_ical_recurrence_events($recurrence);
	
	print_r($recurrence);    
  //echo "<br/>every_N = $every_N - month_position = $month_position";     
	echo "<br/>";
	echo "<ul>";
	foreach($matching_days as $day) {
		echo"<li>".date("D d M Y", $day)."</li>";
	}	          
	echo "</ul>"; 
	
	echo "<h3>Last week of the month</h3>";  
	$recurrence = array('start_date' => '2009-02-10', 'end_date' => '2009-04-24', 'freq'=>'monthly', 'byday' => 2, 'byweekno'=> -1 , 'interval' => 1); 
	$matching_days = dbem_ical_recurrence_events($recurrence);
	
	print_r($recurrence);    
  //echo "<br/>every_N = $every_N - month_position = $month_position";     
	echo "<br/>";
	echo "<ul>";
	foreach($matching_days as $day) {
		echo"<li>".date("D d M Y", $day)."</li>";
	}	          
	echo "</ul>";
}         

function dbem_ical_recurrence_events($recurrence){
	//print_r($recurrence);
	$start_date = mktime(0, 0, 0, substr($recurrence['start_date'],5,2), substr($recurrence['start_date'],8,2), substr($recurrence['start_date'],0,4));
	$end_date = mktime(0, 0, 0, substr($recurrence['end_date'],5,2), substr($recurrence['end_date'],8,2), substr($recurrence['end_date'],0,4));     
 
	$every_keys = array('every' => 1, 'every_second' => 2, 'every_third' => 3, 'every_fourth' => 4);  
	$every_N = $every_keys[$recurrence['recurrence_modifier']]; 
 
	$month_position_keys = array('first_of_month'=>1, 'second_of_month' => 2, 'third_of_month' => 3, 'fourth_of_month' => 4);
	$month_position = $month_position_keys[$recurrence['recurrence_modifier']]; 
	
	$last_week_start = array(25, 22, 25, 24, 25, 24, 25, 25, 24, 25, 24, 25);
	
	echo $monthweek;
	$weekcounter = 0;
	$daycounter = 0; 
	$counter = 0;
	$cycle_date = $start_date;     
	$matching_days = array(); 
	$aDay = 86400;  // a day in seconds  
 
  
	while (date("d-M-Y", $cycle_date) != date('d-M-Y', $end_date + $aDay)) {
 	 // echo (date("d-M-Y", $cycle_date));
		$style = "";
		$monthweek =  floor(((date("d", $cycle_date)-1)/7))+1;   
		 if($recurrence['freq'] == 'daily') {
				if($counter % $recurrence['interval'] == 0 )
					array_push($matching_days, $cycle_date);
				$counter++;
		}
		     
		if (date("N", $cycle_date) == $recurrence['byday']) {
			$monthday = date("j", $cycle_date); 
			$month = date("n", $cycle_date);      

			if($recurrence['freq'] == 'weekly') {
				if($counter % $recurrence['interval'] == 0 )
					array_push($matching_days, $cycle_date);
				$counter++;
			}
			if($recurrence['freq'] == 'monthly') {    
		   	if(($recurrence['byweekno'] == -1) && ($monthday >= $last_week_start[$month-1])) {
					if ($counter % $recurrence['interval'] == 0)
						array_push($matching_days, $cycle_date);
					$counter++;
				} elseif($recurrence['byweekno'] == $monthweek) {
					if ($counter % $recurrence['interval'] == 0)
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
function dbem_recurrence_test_backup() {
	echo "<h2>Recurrence test</h2>";   
	$recurrence = array('start_date' => '2009-02-10', 'end_date' => '2009-02-24', 'weekday'=>2 , 'modifier' => 'every'); 
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

function dbem_insert_recurrent_event($event, $recurrence ){
 	 	$output = "<h2>Recurring</h2>";
		$output .= "Modifier: ".$recurrence['recurrence_modifier']."<br/>";
		$matching_days = array();
		if($recurrence['recurrence_modifier'] == "every") { 
				foreach($recurrence['recurrence_weekdays'] as $weekday) {
					$recurrence['weekday'] = $weekday;                                      
					$new_events =
			  	$matching_days = array_merge($matching_days, dbem_get_recurrence_events($recurrence));
				}
					
		} else {
			  $matching_days = dbem_get_recurrence_events($recurrence);
		} 
		sort($matching_days);
		foreach($matching_days as $day) {
			echo"<li>".date("D d M Y", $day)."</li>";
		}
		die(); 
}

function dbem_get_recurrence_events($recurrence){
	print_r($recurrence);
	$start_date = mktime(0, 0, 0, substr($recurrence['start_date'],5,2), substr($recurrence['start_date'],8,2), substr($recurrence['start_date'],0,4));
	$end_date = mktime(0, 0, 0, substr($recurrence['end_date'],5,2), substr($recurrence['end_date'],8,2), substr($recurrence['end_date'],0,4));     
 
	$every_keys = array('every' => 1, 'every_second' => 2, 'every_third' => 3, 'every_fourth' => 4);  
	$every_N = $every_keys[$recurrence['recurrence_modifier']]; 
 
	$month_position_keys = array('first_of_month'=>1, 'second_of_month' => 2, 'third_of_month' => 3, 'fourth_of_month' => 4);
	$month_position = $month_position_keys[$recurrence['recurrence_modifier']]; 
	
	$last_week_start = array(25, 22, 25, 24, 25, 24, 25, 25, 24, 25, 24, 25);
	
	echo $monthweek;
	$weekcounter = 0;
	$cycle_date = $start_date;     
	$matching_days = array(); 
	$aDay = 86400;  // a day in seconds  
 
  
	while (date("d-M-Y", $cycle_date) != date('d-M-Y', $end_date + $aDay)) {
 	 // echo (date("d-M-Y", $cycle_date));
		$style = "";
		$monthweek =  floor(((date("d", $cycle_date)-1)/7))+1;   
		     
		if (date("N", $cycle_date) == $recurrence['recurrence_weekday']) {
			$monthday = date("j", $cycle_date); 
			$month = date("n", $cycle_date);  
			
			//echo $monthweek;
			if(($recurrence['recurrence_modifier'] == "every") || ($month_position != "" && $month_position == $monthweek) || (($every_N != "") && ($weekcounter % $every_N == 0)) || ($recurrence['recurrence_modifier'] == 'last_of_month' AND $monthday >= $last_week_start[$month-1]) )
				array_push($matching_days, $cycle_date);
				
			$weekcounter++;
	
		}
	  $cycle_date = $cycle_date + $aDay;         //adding a day        
	}   
	
	
	return $matching_days ;
	
}




?>