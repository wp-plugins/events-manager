<?php
/**
 * Base class which others extend on. Contains functions shared across all EM objects.
 *
 */
class EM_Object {
	
	var $fields = array();
	
	/**
	 * Takes the array and provides a clean array of search parameters, along with details
	 * @param array $defaults
	 * @param array $array
	 * @return array
	 */
	function get_default_search($defaults=array(), $array = array()){
		//TODO accept all objects as search options as well as ids (e.g. location vs. location_id, person vs. person_id)
		//Create minimal defaults array, merge it with supplied defaults array
		$super_defaults = array(
			'limit' => false,
			'scope' => 'future', 
			'order' => 'ASC', //hard-coded at end of this function
			'orderby' => false,
			'format' => '', 
			'category' => 0, 
			'location' => 0, 
			'offset'=>0, 
			'recurrence'=>0,
			'recurring'=>false,
			'month'=>'',
			'year'=>'',
			'pagination'=>false,
			'array'=>false
		);
		//Return default if nothing passed
		if( empty($defaults) && empty($array) ){
			return $super_defaults;
		}
		//TODO decide on search defaults shared across all objects and then validate here
		$defaults = array_merge($super_defaults, $defaults);
		
		//We are still dealing with recurrence_id, location_id, category_id in some place, so we do a quick replace here just in case
		if( array_key_exists('recurrence_id', $array) && !array_key_exists('recurrence', $array) ) { $array['recurrence'] = $array['recurrence_id']; }
		if( array_key_exists('location_id', $array) && !array_key_exists('location', $array) ) { $array['location'] = $array['location_id']; }
		if( array_key_exists('category_id', $array) && !array_key_exists('category', $array) ) { $array['category'] = $array['category_id']; }
		
		if(is_array($array)){
			//If there's a location, then remove it and turn it into location_id
			if( array_key_exists('location', $array)){
				if ( is_numeric($array['location']) ) {
					$array['location'] = (int) $array['location'];
				} elseif( preg_match('/^([0-9],?)+$/', $array['location']) ) {
					$array['location'] = explode(',', $array['location']);
				}else{
					//No format we accept
					unset($array['location']);
				}
			}
			//Category - for now we just make both keys have an id number
			if( array_key_exists('category', $array)){
				if ( is_numeric($array['category']) ) {
					$array['category'] = (int) $array['category'];
				} elseif( preg_match('/^([0-9],?)+$/', $array['category']) ) {
					$array['category'] = explode(',', $array['category']);
				}else{
					//No format we accept
					unset($array['category']);
				}
			}
			//OrderBy - can be a comma-seperated array of field names to order by (field names of object, not db)
			if( array_key_exists('orderby', $array)){
				if( preg_match('/,/', $array['orderby']) ) {
					$array['orderby'] = explode(',', $array['orderby']);
				}
			}
			//TODO validate search query array
			//Clean the supplied array, so we only have allowed keys
			foreach( array_keys($array) as $key){
				if( !array_key_exists($key, $defaults) ) unset($array[$key]);		
			}
			//return clean array
			$defaults = array_merge ( $defaults, $array ); //No point using WP's cleaning function, we're doing it already.
		}
		//Do some spring cleaning for known values
		//Month & Year - may be array or single number
		$month_regex = '/^[0-9]{1,2}$/';
		$year_regex = '/^[0-9]{4}$/';
		if( is_array($defaults['month']) ){
			$defaults['month'] = ( preg_match($month_regex, $defaults['month'][0]) && preg_match($month_regex, $defaults['month'][1]) ) ? $defaults['month']:''; 
		}else{
			$defaults['month'] = preg_match($month_regex, $defaults['month']) ? $defaults['month']:'';	
		}
		if( is_array($defaults['year']) ){
			$defaults['year'] = ( preg_match($year_regex, $defaults['year'][0]) && preg_match($year_regex, $defaults['year'][1]) ) ? $defaults['year']:'';
		}else{
			$defaults['year'] = preg_match($year_regex, $defaults['year']) ? $defaults['year']:'';
		}
		//Order - it's either ASC or DESC, so let's just validate
		if( !is_array($defaults['order']) && preg_match('/,/', $defaults['order']) ) {
			$defaults['order'] = explode(',', $defaults['order']);
		}elseif( !in_array($defaults['order'], array('ASC','DESC')) ){
			$defaults['order'] = $super_defaults['order'];
		}
		//ORDER BY, split if an array
		if( !is_array($defaults['orderby']) && preg_match('/,/', $defaults['orderby']) ) {
			$defaults['orderby'] = explode(',', $defaults['orderby']);
		}
		//TODO should we clean format of malicious code over here and run everything thorugh this?
		$defaults['array'] = ($defaults['array'] == true);
		$defaults['pagination'] = ($defaults['pagination'] == true);
		$defaults['limit'] = (is_numeric($defaults['limit'])) ? $defaults['limit']:$super_defaults['limit'];
		$defaults['recurring'] = ($defaults['recurring'] == true);
		
		return apply_filters('em_object_default_search', $defaults);
	}
	
	/**
	 * Builds an array of SQL query conditions based on regularly used arguments
	 * @param array $args
	 * @return array
	 */
	function build_sql_conditions( $args = array() ){
		global $wpdb;
		$events_table = $wpdb->prefix . EM_EVENTS_TABLE;
		$locations_table = $wpdb->prefix . EM_LOCATIONS_TABLE;
		
		$args = apply_filters('em_object_sql_conditions_args',$args);
		
		//Format the arguments passed on
		$scope = $args['scope'];//undefined variable warnings in ZDE, could just delete this (but dont pls!)
		$recurring = $args['recurring'];
		$recurrence = $args['recurrence'];
		$category = $args['category'];
		$location = $args['location'];
		$month = $args['month'];
		$year = $args['year'];
		$today = date('Y-m-d', current_time('timestamp'));
		//Create the WHERE statement
		
		//Recurrences
		$conditions = array();
		if( $recurring ){
			$conditions['recurring'] = "`recurrence`=1";
		}elseif( $recurrence > 0 ){
			$conditions['recurrence'] = "`recurrence_id`=$recurrence";
		}else{
			$conditions['recurring'] = "(`recurrence`!=1 OR `recurrence` IS NULL)";			
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
			$conditions['scope'] = " ((event_start_date BETWEEN CAST('$date_start' AS DATE) AND CAST('$date_end' AS DATE)) OR (event_end_date BETWEEN CAST('$date_start' AS DATE) AND CAST('$date_end' AS DATE)))";
			$search_by_monthyear = true;
		}
		if( !isset($search_by_monthyear) ){
			//No date requested, so let's look at scope
			if ( preg_match ( "/^[0-9]{4}-[0-9]{2}-[0-9]{2},[0-9]{4}-[0-9]{2}-[0-9]{2}$/", $scope ) ) {
				//This is an array, let's split it up
				$dates = explode(',', $scope);
				$date_start = $dates[0];
				$date_end = $dates[1];
				$conditions['scope'] = " ((event_start_date BETWEEN CAST('$date_start' AS DATE) AND CAST('$date_end' AS DATE)) OR (event_end_date BETWEEN CAST('$date_start' AS DATE) AND CAST('$date_end' AS DATE)))";
			} elseif ( preg_match ( "/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/", $scope ) ) {
				//Scope can also be a specific date. However, if 'day', 'month', or 'year' are set, that will take precedence
				$conditions['scope'] = " ( (event_start_date = CAST('$scope' AS DATE)) OR (event_start_date <= CAST('$scope' AS DATE) AND event_end_date >= CAST('$scope' AS DATE)) )";
			} else {
				if ($scope == "past"){
					$conditions['scope'] = " event_start_date < '$today'";  
				}elseif ($scope == "today"){
					$conditions['scope'] = " ( (event_start_date = CAST('$today' AS DATE)) OR (event_start_date <= CAST('$today' AS DATE) AND event_end_date >= CAST('$today' AS DATE)) )";
				}elseif ($scope == "future" || $scope != 'all'){
					$conditions['scope'] = " (event_start_date >= CAST('$today' AS DATE) OR (event_end_date >= CAST('$today' AS DATE) AND event_end_date != '0000-00-00' AND event_end_date IS NOT NULL))";
				}
			}
		}
		
		//Filter by Location - can be object, array, or id
		if ( is_numeric($location) && $location > 0 ) { //Location ID takes precedence
			$conditions['location'] = " {$locations_table}.location_id = $location";
		}elseif ( self::array_is_numeric($location) ){
			$conditions['location'] = "( {$locations_table}.location_id = " . implode(" OR {$locations_table}.location_id = ", $location) .' )';
		}elseif ( is_object($location) && get_class($location)=='EM_Location' ){ //Now we deal with objects
			$conditions['location'] = " {$locations_table}.location_id = $location->id";
		}elseif ( is_array($location) && @get_class(current($location)=='EM_Location') ){ //we can accept array of ids or EM_Location objects
			foreach($location as $EM_Location){
				$location_ids[] = $EM_Location->id;
			}
			$conditions['location'] = "( {$locations_table}.location_id=". implode(" {$locations_table}.location_id=", $location_ids) ." )";
		}
				
		//Add conditions for category selection
		//Filter by category, can be id or comma seperated ids
		//TODO create an exclude category option
		if ( $category != '' && is_numeric($category) ){
			$conditions['category'] = " event_category_id = $category";
		}elseif( self::array_is_numeric($category) ){
			$conditions['category'] = "( event_category_id = ". implode(' OR event_category_id = ', $category).")";
		}
		
		return apply_filters('em_object_sql_conditions', $conditions);
	}
	
	function build_sql_orderby( $args, $accepted_fields, $default_order = 'ASC' ){
		//First, ORDER BY
		$args = apply_filters('em_object_sql_orderby_args', $args);
		$orderby = array();
		if(is_array($args['orderby'])){
			//Clean orderby array so we only have accepted values
			foreach( $args['orderby'] as $key => $field ){
				if( array_key_exists($field, $accepted_fields) ){
					$orderby[] = $accepted_fields[$field];
				}elseif( in_array($field,$accepted_fields) ){
					$orderby[] = $field;
				}else{
					unset($args['orderby'][$key]);
				}
			}
		}elseif( $args['orderby'] != '' && array_key_exists($args['orderby'], $accepted_fields) ){
			$orderby[] = $accepted_fields[$args['orderby']];
		}elseif( $args['orderby'] != '' && in_array($args['orderby'], $accepted_fields) ){
			$orderby[] = $args['orderby'];
		}
		//ORDER
		//If order is an array, we'll go through the orderby array and match the order values (in order of array) with orderby values
		//If orders don't match up, or it's not ASC/DESC, the default events search in EM settings/options page will be used.
		foreach($orderby as $i => $field){
			$orderby[$i] .= ' ';
			if(is_array($args['order'])){
				if( in_array($args['order'][$i], array('ASC','DESC')) ){
					$orderby[$i] .= $args['order'][$i];
				}else{
					$orderby[$i] .= $default_order;
				}
			}else{
				$orderby[$i] .= ( in_array($args['order'], array('ASC','DESC')) ) ? $args['order'] : $default_order;
			}
		}
		return apply_filters('em_object_sql_orderby', $orderby);
	}

	/**
	 * Save an array into this class.
	 * If you provide a record from the database table corresponding to this class type it will add the data to this object.
	 * @param array $array
	 * @return null
	 */
	function to_object( $array = array(), $addslashes = false ){
		//Save core data
		$array = apply_filters('em_to_object', $array);
		foreach ( $this->fields as $key => $val ) {
			if(array_key_exists($key, $array)){
				if( !is_object($array[$key]) && !is_array($array[$key]) ){
					$array[$key] = ($addslashes) ? stripslashes($array[$key]):$array[$key];
				}
				$this->$val['name'] = $array[$key];
			}
		}
	}

	/**
	 * Returns this object in the form of an array, useful for saving directly into a database table.
	 * @return array
	 */
	function to_array(){
		$array = array();
		foreach ( $this->fields as $key => $val ) {
			$array[$key] = $this->$val['name'];
		}
		return apply_filters('em_to_array', $array);
	}
	

	/**
	 * Function to retreive wpdb types for all fields, or if you supply an assoc array with field names as keys it'll return an equivalent array of wpdb types
	 * @param array $array
	 * @return array:
	 */
	function get_types($array = array()){
		$types = array();
		if( count($array)>0 ){
			//So we look at assoc array and find equivalents
			foreach ($array as $key => $val){
				$types[] = $this->fields[$key]['type'];
			}
		}else{
			//Blank array, let's assume we're getting a standard list of types
			foreach ($this->fields as $field){
				$types[] = $field['type'];
			}
		}
		return $types;
	}	
	
	function get_fields( $inverted_array=false ){
		if( is_array($this->fields) ){
			$return = array();
			foreach($this->fields as $fieldName => $fieldArray){
				if($inverted_array){
					$return[$fieldArray['name']] = $fieldName;
				}else{
					$return[$fieldName] = $fieldArray['name'];
				}
			}
			return $return;
		}
		return array();
	}

	/**
	 * Sanitize text before inserting into database
	 * @param string $value
	 * @return string
	 */
	function sanitize( $value ) {
		if( get_magic_quotes_gpc() ) 
	      $value = stripslashes( $value );
	
		//check if this function exists
		if( function_exists( "mysql_real_escape_string" ) ) {
	    	$value = mysql_real_escape_string( $value );
			//for PHP version < 4.3.0 use addslashes
		} else {
	      $value = addslashes( $value );
		}
		return $value;
	}
	
	/**
	 * Will return true if this is a simple (non-assoc) numeric array, meaning it has at one or more numeric entries and nothing else
	 * @param mixed $array
	 * @return boolean
	 */
	function array_is_numeric($array){
		$results = array();
		if(is_array($array)){
			foreach($array as $key => $item){
				$results[] = (is_numeric($item)&&is_numeric($key));
			}
		}
		return ( !in_array(false, $results) && count($results) > 0 );
	}
	
	/**
	 * Converts an array to JSON format, useful for outputting data for AJAX calls. Uses a PHP4 fallback function, given it doesn't support json_encode().
	 * @param array $array
	 * @return string
	 */
	function json_encode($array){
		if( function_exists("json_encode") ){
			$return = json_encode($array);
		}else{
			$return = self::array_to_json($array);
		}
		if( isset($_GET['callback']) ){
			$return = $_GET['callback']."($return)";
		}
		return $return;
	}	
	
	/**
	 * Compatible json encoder function for PHP4
	 * @param array $array
	 * @return string
	 */
	function array_to_json($array){
		//PHP4 Comapatability - This encodes the array into JSON. Thanks go to Andy - http://www.php.net/manual/en/function.json-encode.php#89908
		if( !is_array( $array ) ){
	        $array = array();
	    }
	    $associative = count( array_diff( array_keys($array), array_keys( array_keys( $array )) ));
	    if( $associative ){
	        $construct = array();
	        foreach( $array as $key => $value ){
	            // We first copy each key/value pair into a staging array,
	            // formatting each key and value properly as we go.
	            // Format the key:
	            if( is_numeric($key) ){
	                $key = "key_$key";
	            }
	            $key = "'".addslashes($key)."'";
	            // Format the value:
	            if( is_array( $value )){
	                $value = $this->array_to_json( $value );
	            }else if( is_bool($value) ) {
	            	$value = ($value) ? "true" : "false";
	            }else if( !is_numeric( $value ) || is_string( $value ) ){
	                $value = "'".addslashes($value)."'";
	            }
	            // Add to staging array:
	            $construct[] = "$key: $value";
	        }
	        // Then we collapse the staging array into the JSON form:
	        $result = "{ " . implode( ", ", $construct ) . " }";
	    } else { // If the array is a vector (not associative):
	        $construct = array();
	        foreach( $array as $value ){
	            // Format the value:
	            if( is_array( $value )){
	                $value = $this->array_to_json( $value );
	            } else if( !is_numeric( $value ) || is_string( $value ) ){
	                $value = "'".addslashes($value)."'";
	            }
	            // Add to staging array:
	            $construct[] = $value;
	        }
	        // Then we collapse the staging array into the JSON form:
	        $result = "[ " . implode( ", ", $construct ) . " ]";
	    }		
	    return $result;
	}	
}