<?php
/**
 * Static class which will help bulk add/edit/retrieve/manipulate arrays of EM_Location objects. 
 * Optimized for specifically retreiving locations (whether eventful or not). If you want event data AND location information for each event, use EM_Events
 * 
 */
class EM_Locations extends EM_Object {
	/**
	 * Returns an array of EM_Location objects
	 * @param boolean $eventful
	 * @param boolean $return_objects
	 * @return array
	 */
	function get( $args = array() ){
		global $wpdb;
		$events_table = $wpdb->prefix . EVENTS_TBNAME;
		$locations_table = $wpdb->prefix . LOCATIONS_TBNAME;
		
		//Quick version, we can accept an array of IDs, which is easy to retrieve
		if( self::array_is_numeric($args) && count() ){ //Array of numbers, assume they are event IDs to retreive
			//We can just get all the events here and return them
			$sql = "SELECT * FROM $locations_table WHERE location_id=".implode(" OR location_id=", $args);
			$results = $wpdb->get_results($sql);
			$events = array();
			foreach($results as $result){
				$locations[$result['location_id']] = new EM_Location($result);
			}
			return $locations; //We return all the events matched as an EM_Event array. 
		}
		

		//We assume it's either an empty array or array of search arguments to merge with defaults			
		$args = self::get_default_search($args);
		$limit = ( $args['limit'] && is_numeric($args['limit'])) ? "LIMIT {$args['limit']}" : '';
		$offset = ( $limit != "" && is_numeric($args['offset']) ) ? "OFFSET {$args['offset']}" : '';
		
		//Get the default conditions
		$conditions = self::build_sql_conditions($args);
		
		//Put it all together
		$EM_Location = new EM_Location(0); //Empty class for strict message avoidance
		$fields = $locations_table .".". implode(", {$locations_table}.", array_keys($EM_Location->fields));
		$where = ( count($conditions) > 0 ) ? " WHERE " . implode ( " AND ", $conditions ):'';
		
		//Create the SQL statement and execute
		$sql = "
			SELECT $fields FROM $locations_table
			LEFT JOIN $events_table ON {$locations_table}.location_id={$events_table}.location_id
			$where
			ORDER BY location_name {$args['order']} , location_address {$args['order']}
			$limit $offset
		";		
	
		$results = $wpdb->get_results($sql, ARRAY_A);
		
		//If we want results directly in an array, why not have a shortcut here?
		if( $args['array'] == true ){
			return $results;
		}
		
		$locations = array();
		foreach ($results as $location){
			$locations[] = new EM_Location($location);
		}
		return $locations;
	}
	
	//TODO for all the static plural classes like this one, we might benefit from bulk actions like delete/add/save etc.... just a random thought.
}
?>