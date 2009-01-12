<?php   
require_once('../../../wp-load.php');
$venues = dbem_get_venues();        
$items = array();       

foreach ($venues as $venue) {
// echo $venue->venue_name."<br/>".$venue->venue_address."|".$venue->venue_id."\n"; 
  // using <span>-</span> to avoid eventual conflicts with names with dashes in them
	$items[$venue->venue_name."<br/><small>".$venue->venue_address." <span>-</span> ".$venue->venue_town."</small>"]= $venue->venue_name;
}   

$q = strtolower($_GET["q"]);
if (!$q) return;

foreach ($items as $key=>$value) {
	if (strpos(strtolower($key), $q) !== false) {
		echo "$key|$value\n";
	}
}

function dbem_get_venues() { 
	global $wpdb;

	$sql = "SELECT venue_id, venue_address, venue_name, venue_town 
				FROM ".$wpdb->prefix."venues  
				ORDER BY venue_name";   
	     
	
	$venues = $wpdb->get_results($sql); 
	return $venues;  

}
?>