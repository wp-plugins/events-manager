<?php   
require_once('../../../wp-load.php');
$venues = dbem_get_venues();        
$items = array();       

foreach($venues as $item) {
  $record = array();
  $record['id']      = $item->venue_id;
  $record['name']    = $item->venue_name; 
	$record['address'] = $item->venue_address;   
	$record['town']    = $item->venue_town; 
  $return[]  = $record;
}

$q = strtolower($_GET["q"]);
if (!$q) return;

foreach($return as $row) {
    if (strpos(strtolower($row['name']), $q) !== false) { 
			echo json_encode($row);
    	echo "\n";
		}
}

// 
// foreach ($venues as $venue) {
// // echo $venue->venue_name."<br/>".$venue->venue_address."|".$venue->venue_id."\n"; 
//   // using <span>-</span> to avoid eventual conflicts with names with dashes in them
// 	$items[$venue->venue_name."<br/><small>".$venue->venue_address." <span>-</span> ".$venue->venue_town."</small>"]= $venue->venue_name;
// }   
// 

// 
// foreach ($items as $key=>$value) {
// 	if (strpos(strtolower($key), $q) !== false) {
// 		echo "$key|$value\n";
// 	}
// }

function dbem_get_venues() { 
	global $wpdb;
  $venues_table = $wpdb->prefix.VENUES_TBNAME; 
	$sql = "SELECT venue_id, venue_address, venue_name, venue_town 
				FROM $venues_table ORDER BY venue_name";   
	     
	
	$venues = $wpdb->get_results($sql); 
	return $venues;  

}
?>