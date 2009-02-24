<?php   
require_once('../../../wp-load.php');
$locations = dbem_get_locations();        
$return = array();       

foreach($locations as $item) {
  	$record = array();
  	$record['id']      = $item['location_id'];
  	$record['name']    = $item['location_name']; 
		$record['address'] = $item['location_address'];   
		$record['town']    = $item['location_town']; 
  	$return[]  = $record;
}

$q = strtolower($_GET["q"]);
if (!$q) return;
 
foreach($return as $row) {

	if (strpos(strtolower($row['name']), $q) !== false) { 
		$location = array();
		$rows =array();
		foreach($row as $key => $value)
			$location[] = "'$key' : '$value'";
		echo ("{".implode(" , ", $location)." }\n");	    
		}
		
	}
?>