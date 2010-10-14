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
		//Create minimal defaults array, merge it with supplied defaults array
		$super_defaults = array(
			'limit' => false, 
			'scope' => 'all', 
			'order' => 'ASC', 
			'format' => '', 
			'category' => 0, 
			'location' => 0, 
			'offset'=>0, 
			'recurrence'=>0, 
			'recurring'=>false,
			'month'=>'',
			'year'=>'',
			'array'=>false
		);
		//TODO decide on search defaults shared across all objects and then validate here
		$defaults = array_merge($super_defaults, $defaults);
		
		//We are still dealing with recurrence_id, location_id, category_id in some place, so we do a quick replace here just in case
		if( array_key_exists('recurrence_id', $array) && !array_key_exists('recurrence', $array) ) { $array['recurrence'] = $array['recurrence_id']; }
		if( array_key_exists('location_id', $array) && !array_key_exists('location', $array) ) { $array['location'] = $array['location_id']; }
		if( array_key_exists('category_id', $array) && !array_key_exists('category', $array) ) { $array['category'] = $array['category_id']; }
		
		if(is_array($array)){
			//TODO accept all objects as search options as well as ids (e.g. location vs. location_id, person vs. person_id)
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
		//TODO should we clean format of malicious code over here and run everything thorugh this?
		$defaults['order'] = ($defaults['order'] == "ASC") ? "ASC" : $super_defaults['order'];
		$defaults['array'] = ($defaults['array'] == true);
		$defaults['limit'] = (is_numeric($defaults['limit'])) ? $defaults['limit']:$super_defaults['limit'];
		$defaults['limit'] = (is_numeric($defaults['limit'])) ? $defaults['limit']:$super_defaults['limit'];
		$defaults['recurring'] = ($defaults['recurring'] == true);
		return $defaults;
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
		
		//Format the arguments passed on
		$scope = $args['scope'];//undefined variable warnings in ZDE, could just delete this (but dont pls!)
		$recurring = $args['recurring'];
		$recurrence = $args['recurrence'];
		$category = $args['category'];
		$location = $args['location'];
		$day = $args['day'];
		$month = $args['month'];
		$year = $args['year'];
		$today = date('Y-m-d');
		//Create the WHERE statement
		
		//Recurrences
		if( $recurring ){
			$conditions = array("`recurrence`=1");
		}elseif( $recurrence > 0 ){
			$conditions = array("`recurrence_id`=$recurrence");
		}else{
			$conditions = array("(`recurrence`!=1 OR `recurrence` IS NULL)");			
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
		
		return $conditions;
	}
	

	/**
	 * Save an array into this class.
	 * If you provide a record from the database table corresponding to this class type it will add the data to this object.
	 * @param array $array
	 * @return null
	 */
	function to_object( $array = array(), $addslashes = false ){
		//Save core data
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
		return $array;
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