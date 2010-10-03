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
			'order' => 'DESC', 
			'format' => '', 
			'category_id' => 0, 
			'location_id' => 0, 
			'offset'=>0, 
			'recurrence_id'=>0, 
			'recurrence'=>false,
			'month'=>'',
			'year'=>''
		);
		//TODO decide on search defaults shared across all objects and then validate here
		$defaults = array_merge($super_defaults, $defaults);
		
		//TODO accept all objects as search options as well as ids (e.g. location vs. location_id, person vs. person_id)
		//If there's a location, then remove it and turn it into location_id
		if( array_key_exists('location', $array)){
			if ( is_numeric($array['location']) ) {
				$array['location_id'] = $array['location'];
				unset($array['location']);
			} elseif( preg_match('/^([0-9],?)+$/', $array['location_id']) ) {
				$array['location_id'] = explode(',', $array['location_id']);
				unset($array['location']);
			} elseif ( @get_class(current($array)) != 'EM_Location' ) {
				unset($array['location']);
			}
		}
		//Category - for now we just make both keys have an id number
		if( array_key_exists('category', $array)){
			$array['category_id'] = $array['category']; 
			unset($array['category']);
		}
		//TODO validate search query array
		//Clean the supplied array, so we only have allowed keys
		if(is_array($array)){
			foreach( array_keys($array) as $key){
				if( !array_key_exists($key, $defaults) ) unset($array[$key]);		
			}
			//return clean array
			return array_merge ( $defaults, $array ); //No point using WP's cleaning function, we're doing it already.
		}
		return $defaults;
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
				$array[$key] = ($addslashes) ? stripslashes($array[$key]):$array[$key];
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
	                $value = dbem_array_to_json( $value );
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
	                $value = dbem_array_to_json( $value );
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