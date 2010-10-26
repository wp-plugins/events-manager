<?php
/*
 * This page will search for either a specific location via GET "id" variable 
 * or will search for events by name via the GET "q" variable.
 */
require_once('../../../../wp-load.php');

global $wpdb;

$locations_table = $wpdb->prefix . EM_LOCATIONS_TABLE;

$term = (isset($_GET['term'])) ? '%'.$_GET['term'].'%' : '%'.$_GET['q'].'%';
$sql = $wpdb->prepare("
	SELECT 
		Concat( location_name, ', ', location_address, ', ', location_town)  AS `label`,
		location_name AS `value`,
		location_address AS `address`, 
		location_town AS `town`, 
		location_id AS `id`
	FROM $locations_table 
	WHERE ( `location_name` LIKE %s ) LIMIT 10
", $term);

$locations_array = $wpdb->get_results($sql);

echo EM_Object::json_encode($locations_array);
/*
$return_string_array = array();
foreach($locations_array as $location){
	$return_string_class = array();
	foreach($location as $key => $value ){
		$return_string_class[] = "$key : '".addslashes($value)."'";
	}
	$return_string_array[] = '{'. implode(',', $return_string_class) .'}'; 
}
echo '['. implode(',', $return_string_array) .']';
*/
?>