<?php
/**
 * Static class which will help bulk add/edit/retrieve/manipulate arrays of EM_Location objects
 * @author marcus
 *
 */
class EM_Locations extends EM_Object {
	/**
	 * Returns an array of EM_Location objects
	 * @param boolean $eventful
	 * @param boolean $return_objects
	 * @return array
	 */
	function get( $eventful = false, $return_objects = true ){
		global $wpdb;
		$locations_table = $wpdb->prefix.LOCATIONS_TBNAME; 
		$events_table = $wpdb->prefix.EVENTS_TBNAME;
		if ($eventful == 'true') {
			$sql = "SELECT * from $locations_table JOIN $events_table ON $locations_table.location_id = $events_table.location_id";
		} else {
			$sql = "SELECT location_id, location_address, location_name, location_town,location_latitude, location_longitude 
				FROM $locations_table ORDER BY location_name";   
		}	
		$locations = $wpdb->get_results($sql, ARRAY_A);
		if( true == $return_objects ){
			$location_objects = array();
			foreach ($locations as $location){
				$location_objects[] = new EM_Location($location);
			}
			$locations = $location_objects;
		}
		return $locations;
	}
	
	//TODO for the static plural classes like this one, we might benefit from bulk actions like delete/add/save etc.... just a random thought.
}
?>