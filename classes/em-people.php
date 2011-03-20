<?php
class EM_People extends EM_Object {
	
	/**
	 * Gets all users, if $return_people false an array associative arrays will be returned. If $return_people is true this will return an array of EM_Person objects
	 * @param $return_people
	 * @return array
	 */
	function get( $return_people = true ) {
		global $wpdb; 
		$sql = "SELECT *  FROM ". $wpdb->prefix.EM_PEOPLE_TABLE ;    
		$result = $wpdb->get_results($sql, ARRAY_A);
		if( $return_people ){
			//Return people as EM_Person objects
			$people = array();
			foreach ($result as $person){
				$people[] = new EM_Person($person);
			}
			return $people;
		}
		return $result;
	}
	
	function get_new( $args = array() ) {
		global $wpdb;
		$people_table = $wpdb->prefix.EM_PEOPLE_TABLE;
		$bookings_table = $wpdb->prefix.EM_BOOKINGS_TABLE;
		
		//Quick version, we can accept an array of IDs, which is easy to retrieve
		if( self::array_is_numeric($args) ){ //Array of numbers, assume they are event IDs to retreive
			//We can just get all the events here and return them
			$sql = "SELECT * FROM $people_table WHERE person_id=".implode(" OR person_id=", $args);
			$results = $wpdb->get_results(apply_filters('em_people_get_sql',$sql),ARRAY_A);
			$people = array();
			foreach($results as $result){
				$people[$result['person_id']] = new EM_Person($result);
			}
			return $people; //We return all the people matched as an EM_Event array. 
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
		$EM_Person = new EM_Person();
		$accepted_fields = $EM_Person->get_fields(true);
		$orderby = self::build_sql_orderby($args, $accepted_fields, get_option('dbem_people_default_order'));
		//Now, build orderby sql
		$orderby_sql = ( count($orderby) > 0 ) ? 'ORDER BY '. implode(', ', $orderby) : '';
		
		//Create the SQL statement and execute
		$sql = "
			SELECT * FROM $people_table
			LEFT JOIN $bookings_table ON {$bookings_table}.person_id={$people_table}.person_id
			$where
			GROUP BY person_id
			$orderby_sql
			$limit $offset
		";
		$results = $wpdb->get_results( apply_filters('em_people_get_sql',$sql, $args), ARRAY_A);
		//If we want results directly in an array, why not have a shortcut here?
		if( $args['array'] == true ){
			return $results;
		}
		
		//Make returned results EM_Event objects
		$results = (is_array($results)) ? $results:array();
		$people = array();
		foreach ( $results as $person_array ){
			$people[$person_array['person_id']] = new EM_Person($person_array);
		}
		
		return apply_filters('em_people_get', $people);
	}
	
	/* Overrides EM_Object method to apply a filter to result
	 * @see wp-content/plugins/events-manager/classes/EM_Object#build_sql_conditions()
	 */
	function build_sql_conditions( $args = array() ){
		global $wpdb;
		//FIXME EM_People doesn't build sql conditions in EM_Object
		$conditions = array();
		
		return apply_filters( 'em_people_build_sql_conditions', $conditions, $args );
	}
	
	/* Overrides EM_Object method to apply a filter to result
	 * @see wp-content/plugins/people-manager/classes/EM_Object#build_sql_orderby()
	 */
	function build_sql_orderby( $args, $accepted_fields, $default_order = 'ASC' ){
		return apply_filters( 'em_people_build_sql_orderby', parent::build_sql_orderby($args, $accepted_fields, get_option('dbem_people_default_order')), $args, $accepted_fields, $default_order );
	}
	
	/* 
	 * Adds custom people search defaults
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
		//figure out default owning permissions, but since public is for viewing events, only impose limitations in admin area
		if( is_admin() ){
			switch( get_option('dbem_permissions_events') ){
				case 0:
					$defaults['owner'] = get_current_user_id();
					break;
				case 1:
					$defaults['owner'] = false;
					break;
			}
			$defaults['owner'] = ( em_verify_admin() ) ? false:$defaults['owner'];
		}
		return apply_filters('em_people_get_default_search', parent::get_default_search($defaults,$array), $array, $defaults);
	}	
	
}
?>