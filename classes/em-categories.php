<?php
class EM_Categories extends EM_Object {
		
	function get( $args = array() ) {
		global $wpdb;
		$categories_table = $wpdb->prefix.EM_CATEGORIES_TABLE;
		$events_table = $wpdb->prefix.EM_EVENTS_TABLE;
		
		//Quick version, we can accept an array of IDs, which is easy to retrieve
		if( self::array_is_numeric($args) ){ //Array of numbers, assume they are event IDs to retreive
			//We can just get all the events here and return them
			$sql = "SELECT * FROM $categories_table WHERE category_id=".implode(" OR category_id=", $args);
			$results = $wpdb->get_results(apply_filters('em_categories_get_sql',$sql),ARRAY_A);
			$categories = array();
			foreach($results as $result){
				$categories[$result['category_id']] = new EM_Event($result);
			}
			return $categories; //We return all the categories matched as an EM_Event array. 
		}
		
		//We assume it's either an empty array or array of search arguments to merge with defaults			
		$args = self::get_default_search($args);
		$limit = ( $args['limit'] && is_numeric($args['limit'])) ? "LIMIT {$args['limit']}" : '';
		$offset = ( $limit != "" && is_numeric($args['offset']) ) ? "OFFSET {$args['offset']}" : '';
		
		//Get the default conditions
		$conditions = self::build_sql_conditions($args);
		//Put it all together
		$where = ( count($conditions) > 0 ) ? " WHERE " . implode ( " AND ", $conditions ):'';
		
		//Get ordering instructions
		$EM_Category = new EM_Category();
		$accepted_fields = $EM_Category->get_fields(true);
		$orderby = self::build_sql_orderby($args, $accepted_fields, get_option('dbem_categories_default_order'));
		//Now, build orderby sql
		$orderby_sql = ( count($orderby) > 0 ) ? 'ORDER BY '. implode(', ', $orderby) : '';
		
		//Create the SQL statement and execute
		$sql = "
			SELECT * FROM $categories_table
			LEFT JOIN $events_table ON {$events_table}.event_category_id={$categories_table}.category_id
			$where
			$orderby_sql
			$limit $offset GROUP BY category_id
		";
		$results = $wpdb->get_results( apply_filters('em_categories_get_sql',$sql, $args), ARRAY_A);
		//If we want results directly in an array, why not have a shortcut here?
		if( $args['array'] == true ){
			return $results;
		}
		
		//Make returned results EM_Event objects
		$results = (is_array($results)) ? $results:array();
		$categories = array();
		foreach ( $results as $category_array ){
			$categories[] = new EM_Category($category_array);
		}
		
		return apply_filters('em_categories_get', $categories);
	}
	
	/**
	 * Will delete given an array of category_ids or EM_Event objects
	 * @param unknown_type $id_array
	 */
	function delete( $array ){
		global $wpdb;
		//Detect array type and generate SQL for event IDs
		$category_ids = array();
		if( @get_class(current($array)) == 'EM_Category' ){
			foreach($array as $EM_Category){
				$category_ids[] = $EM_Category->id;
			}
		}else{
			$category_ids = $array;
		}
		if(self::array_is_numeric($category_ids)){
			apply_filters('em_categories_delete', $category_ids);
			$condition = implode(" OR category_id=", $category_ids);
			//Delete all the bookings
			$result_bookings = $wpdb->query("DELETE FROM ". $wpdb->prefix . EM_BOOKINGS_TABLE ." WHERE category_id=$condition;");
			//Now delete the categories
			$result = $wpdb->query ( "DELETE FROM ". $wpdb->prefix . EM_CATEGORIES_TABLE ." WHERE category_id=$condition;" );
			do_action('em_categories_delete', $category_ids);
		}
		//TODO add error detection on categories delete fails
		return apply_filters('em_categories_delete', true, $category_ids);
	}

	/* Overrides EM_Object method to apply a filter to result
	 * @see wp-content/plugins/categories-manager/classes/EM_Object#build_sql_conditions()
	 */
	function build_sql_conditions( $args = array() ){
		global $wpdb;
		$events_table = $wpdb->prefix . EM_EVENTS_TABLE;
		$locations_table = $wpdb->prefix . EM_LOCATIONS_TABLE;
		
		//FIXME EM_Categories doesn't build sql conditions in EM_Object
		$conditions = array();
		//eventful locations
		if( true == $args['eventful'] ){
			$conditions['eventful'] = "{$events_table}.event_id IS NOT NULL";
		}elseif( true == $args['eventless'] ){
			$conditions['eventless'] = "{$events_table}.event_id IS NULL";
		}
		//owner lookup
		if( is_numeric($args['owner']) ){
			$conditions['owner'] = "category_owner=".get_current_user_id();
		}		
		return apply_filters( 'em_categories_build_sql_conditions', $conditions, $args );
	}
	
	/* Overrides EM_Object method to apply a filter to result
	 * @see wp-content/plugins/categories-manager/classes/EM_Object#build_sql_orderby()
	 */
	function build_sql_orderby( $args, $accepted_fields, $default_order = 'ASC' ){
		return apply_filters( 'em_categories_build_sql_orderby', parent::build_sql_orderby($args, $accepted_fields, get_option('dbem_categories_default_order')), $args, $accepted_fields, $default_order );
	}
	
	/* 
	 * Adds custom categories search defaults
	 * @param array $array
	 * @return array
	 * @uses EM_Object#get_default_search()
	 */
	function get_default_search( $array = array() ){
		$defaults = array(
			'scope'=>false,
			'eventful' => false, //cats that have an event (scope will also play a part here
			'eventless' => false, //cats WITHOUT events, eventful takes precedence
		);
		return apply_filters('em_categories_get_default_search', parent::get_default_search($defaults,$array), $array, $defaults);
	}	

}