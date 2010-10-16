<?php
class EM_People extends EM_Object {
	
	/**
	 * Gets all users, if $return_people false an array associative arrays will be returned. If $return_people is true this will return an array of EM_Person objects
	 * @param $return_people
	 * @return array
	 */
	function get( $return_people = true ) {
		global $wpdb; 
		$sql = "SELECT *  FROM ". $wpdb->prefix.PEOPLE_TBNAME ;    
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
	
}
?>