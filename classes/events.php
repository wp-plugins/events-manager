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
		if( self::array_is_numeric($args) && count() ){ //Array of numbers, assume they are event IDs to retreive
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
		
		//Format the arguments passed on
		//We assume it's either an empty array or array of search arguments to merge with defaults			
		$args = self::get_default_search($args);
		$scope = $args['scope'];//undefined variable warnings in ZDE, could just delete this (but dont pls!)
		$offset = $args['offset'];
		$recurring = $args['recurring'];
		$recurrence = $args['recurrence'];
		$category = $args['category'];
		$location = $args['location'];
		$day = $args['day'];
		$month = $args['month'];
		$year = $args['year'];
		extract($args, EXTR_SKIP);
		$today = date( 'Y-m-d' );
		$limit = ( $limit && is_numeric($limit)) ? "LIMIT $limit" : '';
		$offset = ( $limit != "" && is_numeric($offset) ) ? "OFFSET $offset" : '';
		//TODO order by?
		$order = ($order == "DESC") ? "DESC" : "ASC";
		
		//Create the WHERE statement
		
		//Recurrences
		if( $recurring ){
			$conditions = array("`recurrence`=1");
		}elseif( $recurrence > 0 ){
			$conditions = array("`recurrence_id`=$recurrence");
		}else{
			$conditions = array("`recurrence`=0");			
		}
		//Dates - first check 'month', and 'year'
		if( !($month=='' && $year=='') ){
			//Sort out month range, if supplied an array of array(month,month), it'll check between these two months
			if( self::array_is_numeric($month) ){
				$date_month_start = $month[0];
				$date_month_end = $month[1];
			}else{
				$date_month_start = $date_month_end = $month;
			}
			//Sort out year range, if supplied an array of array(year,year), it'll check between these two years
			if( self::array_is_numeric($year) ){
				$date_year_start = $year[0];
				$date_year_end = $year[1];
			}else{
				$date_year_start = $date_year_end = $year;
			}
			$date_start = date('Y-m-d', mktime(0,0,0,$date_month_start,1,$date_year_start));
			$date_end = date('Y-m-t', mktime(0,0,0,$date_month_end,1,$date_year_end));
			$conditions[] = " ((event_start_date BETWEEN CAST('$date_start' AS DATE) AND CAST('$date_end' AS DATE)) OR (event_end_date BETWEEN CAST('$date_start' AS DATE) AND CAST('$date_end' AS DATE)))";
			$search_by_date = true;
		}
		if( !isset($search_by_date) ){
			//No date requested, so let's look at scope
			if ( preg_match ( "/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/", $scope ) ) {
				//Scope can also be a specific date. However, if 'day', 'month', or 'year' are set, that will take precedence
				$conditions [] = " ( (event_start_date = CAST('$scope' AS DATE)) OR (event_start_date <= CAST('$scope' AS DATE) AND event_end_date >= CAST('$scope' AS DATE)) )";
			} else {
				if ($scope == "past"){
					$conditions [] = " event_start_date < '$today'";  
				}elseif ($scope == "today"){
					$conditions [] = " ( (event_start_date = CAST('$today' AS DATE)) OR (event_start_date <= CAST('$today' AS DATE) AND event_end_date >= CAST('$today' AS DATE)) )";
				}elseif ($scope == "future" || $scope != 'all'){
					$conditions [] = " (event_start_date >= CAST('$today' AS DATE) OR (event_end_date >= CAST('$today' AS DATE) AND event_end_date != '0000-00-00' AND event_end_date IS NOT NULL))";
				}
			}
		}
		
		//Filter by Location - can be object, array, or id
		if ( is_numeric($location) && $location > 0 ) { //Location ID takes precedence
			$conditions [] = " {$locations_table}.location_id = $location";
		}elseif ( self::array_is_numeric($location) ){
			$conditions [] = "( {$locations_table}.location_id = " . implode(" OR {$locations_table}.location_id = ", $location) .' )';
		}elseif ( is_object($location) && get_class($location)=='EM_Location' ){ //Now we deal with objects
			$conditions [] = " {$locations_table}.location_id = $location->id";
		}elseif ( is_array($location) && @get_class(current($location)=='EM_Location') ){ //we can accept array of ids or EM_Location objects
			foreach($location as $EM_Location){
				$location_ids[] = $EM_Location->id;
			}
			$conditions[] = "( {$locations_table}.location_id=". implode(" {$locations_table}.location_id=", $location_ids) ." )";
		}	
				
		//Add conditions for category selection
		//Filter by category, can be id or comma seperated ids
		//TODO create an exclude category option
		if ( $category != '' && is_numeric($category) ){
			$conditions [] = " event_category_id = $category";
		}elseif( self::array_is_numeric($category) ){
			$conditions [] = "( event_category_id = ". implode(' OR event_category_id = ', $category).")";
		}
		
		//Put it all together
		$where = ( count($conditions) > 0 ) ? " WHERE " . implode ( " AND ", $conditions ):'';
		
		//Create the SQL statement and execute
		$sql = "
			SELECT * FROM $events_table
			LEFT JOIN $locations_table ON {$locations_table}.location_id={$events_table}.location_id
			$where
			ORDER BY event_start_date $order , event_start_time $order
			$limit $offset
		";
		$results = $wpdb->get_results ( $sql, ARRAY_A );
		
		//Make returned results EM_Event objects
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
		$old_EM_Event = $EM_Event; //When looping, we can replace EM_Event global with the current event in the loop
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
		$old_EM_Event = $EM_Event;
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