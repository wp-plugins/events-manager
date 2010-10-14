<?php
/**
 * Use this class to query and manipulate sets of events. If dealing with more than one event, you probably want to use this class in some way.
 *
 */
class EM_Events extends EM_Object {
	
	/**
	 * Returns an array of EM_Events that match the given specs in the argument, or returns a list of future evetnts in future 
	 * (see EM_Events::get_default_search() ) for explanation of possible search array values. You can also supply a numeric array
	 * containing the ids of the events you'd like to obtain 
	 * 
	 * @param array $args
	 * @return EM_Event array()
	 */
	function get( $args = array() ) {
		global $wpdb;
		$events_table = $wpdb->prefix . EVENTS_TBNAME;
		$locations_table = $wpdb->prefix . LOCATIONS_TBNAME;
		
		//Quick version, we can accept an array of IDs, which is easy to retrieve
		if( self::array_is_numeric($args) ){ //Array of numbers, assume they are event IDs to retreive
			//We can just get all the events here and return them
			$sql = "
				SELECT * FROM $events_table
				LEFT JOIN $locations_table ON {$locations_table}.location_id={$events_table}.location_id
				WHERE event_id=".implode(" OR event_id=", $args)."
			";
			$results = $wpdb->get_results($sql);
			$events = array();
			foreach($results as $result){
				$events[$result['event_id']] = new EM_Event($result);
			}
			return $events; //We return all the events matched as an EM_Event array. 
		}
		
		//We assume it's either an empty array or array of search arguments to merge with defaults			
		$args = self::get_default_search($args);
		$limit = ( $args['limit'] && is_numeric($args['limit'])) ? "LIMIT {$args['limit']}" : '';
		$offset = ( $limit != "" && is_numeric($args['offset']) ) ? "OFFSET {$args['offset']}" : '';
		
		//Get the default conditions
		$conditions = self::build_sql_conditions($args);
		
		//Put it all together
		$where = ( count($conditions) > 0 ) ? " WHERE " . implode ( " AND ", $conditions ):'';
		
		//Create the SQL statement and execute
		$sql = "
			SELECT * FROM $events_table
			LEFT JOIN $locations_table ON {$locations_table}.location_id={$events_table}.location_id
			$where
			ORDER BY event_start_date {$args['order']} , event_start_time {$args['order']}
			$limit $offset
		";		
	
		$results = $wpdb->get_results($sql, ARRAY_A);

		//If we want results directly in an array, why not have a shortcut here?
		if( $args['array'] == true ){
			return $results;
		}
		
		//Make returned results EM_Event objects
		$results = (is_array($results)) ? $results:array();
		$events = array();
		foreach ( $results as $event ){
			$events[] = new EM_Event($event);
		}
		
		return $events;
	}
	
	/**
	 * Returns the number of events on a given date
	 * @param $date
	 * @return int
	 */
	function count_date($date){
		global $wpdb;
		$table_name = $wpdb->prefix . EVENTS_TBNAME;
		$sql = "SELECT COUNT(*) FROM  $table_name WHERE (event_start_date  like '$date') OR (event_start_date <= '$date' AND event_end_date >= '$date');";
		return $wpdb->get_var ( $sql );
	}
	
	/**
	 * Will delete given an array of event_ids or EM_Event objects
	 * @param unknown_type $id_array
	 */
	function delete( $array ){
		global $wpdb;
		//Detect array type and generate SQL for event IDs
		$event_ids = array();
		if( @get_class(current($array)) == 'EM_Event' ){
			foreach($array as $EM_Event){
				$event_ids[] = $EM_Event->id;
			}
		}else{
			$event_ids = $array;
		}
		if(self::array_is_numeric($event_ids)){
			$condition = implode(" OR event_id=", $event_ids);
			//Delete all the bookings
			$result_bookings = $wpdb->query("DELETE FROM ". $wpdb->prefix . BOOKINGS_TBNAME ." WHERE event_id=$condition;");
			//Now delete the events
			$result = $wpdb->query ( "DELETE FROM ". $wpdb->prefix . EVENTS_TBNAME ." WHERE event_id=$condition;" );
		}
		return true;
	}
	
	
	/**
	 * Output a set of matched of events
	 * @param array $args
	 * @return string
	 */
	function output( $args ){
		global $EM_Event;
		$EM_Event_old = $EM_Event; //When looping, we can replace EM_Event global with the current event in the loop
		//Can be either an array for the get search or an array of EM_Event objects
		if( is_object(current($args)) && get_class((current($args))) == 'EM_Event' ){
			$events = $args;
		}else{
			$events = self::get( $args );
		}
		//What format shall we output this to, or use default
		$format = ( $args['format'] == '') ? get_option( 'dbem_event_list_item_format' ) : $args['format'] ;
		
		$output = "";
		if ( count($events) > 0 ) {
			foreach ( $events as $event ) {
				$EM_Event = $event;
				/* @var EM_Event $event */
				$output .= $event->output($format);
			}
			//Add headers and footers to output
			if( $format == get_option ( 'dbem_event_list_item_format' ) ){
				$single_event_format_header = get_option ( 'dbem_event_list_item_format_header' );
				$single_event_format_header = ( $single_event_format_header != '' ) ? $single_event_format_header : "<ul class='dbem_events_list'>";
				$single_event_format_footer = get_option ( 'dbem_event_list_item_format_footer' );
				$single_event_format_footer = ( $single_event_format_footer != '' ) ? $single_event_format_footer : "</ul>";
				$output =  $single_event_format_header .  $output . $single_event_format_footer;
			}
		} else {
			$output = get_option ( 'dbem_no_events_message' );
		}
		//TODO check if reference is ok when restoring object, due to changes in php5 v 4
		$EM_Event_old = $EM_Event;
		return $output;		
	}
	
	/**
	 * Takes the array and provides a clean array of search parameters, along with details
	 * @param array $array
	 * @return array
	 */
	function get_default_search($array = array()){
		//TODO trim these defaults, the EM_Object will have some of these already
		$defaults = array(
			'limit' => false, 
			'scope' => 'all', 
			'order' => 'ASC', 
			'format' => '', 
			'category' => 0, 
			'location' => 0, 
			'offset'=>0, 
			'recurrence'=>0, 
			'recurring'=>false ,
			'month'=>'', //If this is set, month must be set
			'year'=>'' //If this is set, takes precedence over scope
		);
		return parent::get_default_search($defaults, $array);
	}
}
?>