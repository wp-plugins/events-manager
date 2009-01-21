<?php

function dbem_recurrence_test() {
	echo "<h2>Recurrence test</h2>";   
	$start_date = mktime(0,0,0, 02, 10, 2009);
	$end_date = mktime(0,0,0, 02, 24, 2009);
	$recurrence = array('weekday'=>2 , 'modifier' => 'every');
	$every_keys = array('every' => 1, 'every_second' => 2, 'every_third' => 3, 'every_fourth' => 4);  
	$every_N = $every_keys[$recurrence['modifier']]; 
 
	$month_position_keys = array('first_of_month'=>1, 'second_of_month' => 2, 'third_of_month' => 3, 'fourth_of_month' => 4);
	$month_position = $month_position_keys[$recurrence['modifier']]; 
	
	$last_week_start = array(25, 22, 25, 24, 25, 24, 25, 25, 24, 25, 24, 25);
	
	echo "start date: ".date("d-M-Y", $start_date)."<br/>";   
	echo "end date: ".date("d-M-Y", $end_date)."<br/>";
	print_r($recurrence);    
  //echo "<br/>every_N = $every_N - month_position = $month_position";     
	echo "<br/>";
  
	;
	echo $monthweek;
	$weekcounter = 0;
	$cycle_date = $start_date;     
	$matching_days = array(); 
	$aDay = 86400;  // a day in seconds
	while (date("d-M-Y", $cycle_date) != date('d-M-Y', $end_date + $aDay)) {
	 	
		
		$style = "";
		$monthweek =  floor(((date("d", $cycle_date)-1)/7))+1;   
		     
		if (date("N", $cycle_date) == $recurrence['weekday']) {
			$monthday = date("j", $cycle_date); 
			$month = date("n", $cycle_date);  
			
			//echo $monthweek;
			if(($month_position != "" && $month_position == $monthweek) || (($every_N != "") && ($weekcounter % $every_N == 0)) || ($recurrence['modifier'] == 'last_of_month' AND $monthday >= $last_week_start[$month-1]) )
				array_push($matching_days, $cycle_date);
				
			$weekcounter++;
			
		}
	  $cycle_date = $cycle_date + $aDay;         //adding a day        
	}   
	echo "<ul>";
	foreach($matching_days as $day) {
		echo"<li>".date("D d M Y", $day)."</li>";
	}	          
	echo "</ul>";
}
?>