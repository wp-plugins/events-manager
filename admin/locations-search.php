<?php
/*
 * This page will search for either a specific location via GET "id" variable 
 * or will search for events by name via the GET "q" variable.
 */
require_once('../../../../wp-load.php');

global $wpdb;

$locations_table = $wpdb->prefix . LOCATIONS_TBNAME;

$term = '%'.$_GET['term'].'%';
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
$return = $locations_array;

echo EM_Object::json_encode($return);
?>