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
			GROUP BY location_id
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
	
	/**
	 * Output a set of matched of events
	 * @param array $args
	 * @return string
	 */
	function output( $args ){
		global $EM_Location;
		$EM_Location_old = $EM_Location; //When looping, we can replace EM_Location global with the current event in the loop
		//Can be either an array for the get search or an array of EM_Location objects
		if( is_object(current($args)) && get_class((current($args))) == 'EM_Location' ){
			$locations = $args;
		}else{
			$locations = self::get( $args );
		}
		//What format shall we output this to, or use default
		$format = ( $args['format'] == '' ) ? get_option( 'dbem_location_list_item_format' ) : $args['format'] ;
		
		$output = "";
		if ( count($locations) > 0 ) {
			foreach ( $locations as $location ) {
				$EM_Location = $location;
				/* @var EM_Event $event */
				$output .= $location->output($format);
			}
			//Add headers and footers to output
			if( $format == get_option ( 'dbem_location_list_item_format' ) ){
				$single_event_format_header = get_option ( 'dbem_location_list_item_format_header' );
				$single_event_format_header = ( $single_event_format_header != '' ) ? $single_event_format_header : "<ul class='dbem_events_list'>";
				$single_event_format_footer = get_option ( 'dbem_location_list_item_format_footer' );
				$single_event_format_footer = ( $single_event_format_footer != '' ) ? $single_event_format_footer : "</ul>";
				$output =  $single_event_format_header .  $output . $single_event_format_footer;
			}
		} else {
			$output = get_option ( 'dbem_no_events_message' );
		}
		//FIXME check if reference is ok when restoring object, due to changes in php5 v 4
		$EM_Location_old= $EM_Location;
		return $output;		
	}
	
	/**
	 * Builds an array of SQL query conditions based on regularly used arguments
	 * @param array $args
	 * @return array
	 */
	function build_sql_conditions( $args = array() ){
		global $wpdb;
		$events_table = $wpdb->prefix . EVENTS_TBNAME;
		$locations_table = $wpdb->prefix . LOCATIONS_TBNAME;
		
		$conditions = parent::build_sql_conditions($args);
		//eventful locations
		if( true == $args['eventful'] ){
			$conditions[] = "{$events_table}.event_id IS NOT NULL";
		}elseif( true == $args['eventless'] ){
			$conditions[] = "{$events_table}.event_id IS NULL";
		}
		return $conditions;
	}
	
	/* 
	 * Generate a search arguments array from defalut and user-defined.
	 * @see wp-content/plugins/events-manager/classes/EM_Object::get_default_search()
	 */
	function get_default_search($args = array()){
		$defaults = array(
			'eventful' => false, //Locations that have an event (scope will also play a part here
			'eventless' => false, //Locations WITHOUT events, eventful takes precedence
			'scope' => 'all'
		);
		$args['eventful'] = ($args['eventful'] == true);
		$args['eventless'] = ($args['eventless'] == true);
		return parent::get_default_search($defaults, $args);
	}
	//TODO for all the static plural classes like this one, we might benefit from bulk actions like delete/add/save etc.... just a random thought.
}
?>