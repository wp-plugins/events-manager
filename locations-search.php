<?php   
require_once('../../../wp-load.php');
if(isset($_GET['id']) && $_GET['id'] != "") {
	$location = dbem_get_location($_GET['id']);
	echo '{"id":"'.$location['location_id'].'" , "name"  : "'.$location['location_name'].'","town" : "'.$location['location_town'].'","address" : "'.$location['location_address'].'" }';
	
} else {

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
				$location[] = "'$key' : '".str_replace("'", "\'", $value)."'";
			echo ("{".implode(" , ", $location)." }\n");	    
		 }
		
	}
	    
}
?>