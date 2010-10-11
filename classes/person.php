<?php
// TODO make person details more secure and integrate with WP user data 
class EM_Person extends EM_Object{
	//DB Fields
	var $id = '';
	var $name = '';
	var $email = '';
	var $phone = '';
	//Other Vars
	var $fields = array( 
		'person_id' => array('name'=>'id','type'=>'%d'), 
		'person_name' => array('name'=>'name','type'=>'%s'), 
		'person_email' => array('name'=>'email','type'=>'%s'),
		'person_phone' => array('name'=>'phone','type'=>'%s')
	);
	var $required_fields = array('person_id', 'person_name', 'person_email', 'person_phone');
	var $feedback_message = "";
	var $errors = array();
	
	function EM_Person( $person_data = false ){
		if( $person_data != 0 ){
			//Load person data
			$person = array();
			if( is_array($person_data) ){
				$person = $person_data;
			}elseif( $person_data > 0 ){
				//Retreiving from the database		
				global $wpdb;			
				$sql = "SELECT * FROM ". $wpdb->prefix . PEOPLE_TBNAME ." WHERE person_id ='$person_data'";   
			  	$person = $wpdb->get_row($sql, ARRAY_A);
			}
			//Save into the object
			$this->to_object($person);
		}
	}
 
	/**
	 * Load an record into this object by passing an associative array of table criterie to search for. 
	 * Returns boolean depending on whether a record is found or not. 
	 * @param $search
	 * @return boolean
	 */
	function get($search) {
		global $wpdb;
		$conds = array(); 
		foreach($search as $key => $value) {
			if( array_key_exists($key, $this->fields) ){
				$conds[] = "`$key`='$value'";
			} 
		}
		$sql = "SELECT * FROM ". $wpdb->prefix.PEOPLE_TBNAME ." WHERE " . implode(' AND ', $conds) ;
		$result = $wpdb->get_row($sql, ARRAY_A);
		if($result){
			$this->to_object($result);
			return true;	
		}else{
			return false;
		}
	}
	
	function save(){
		global $wpdb;
		if($this->validate()){
			//Does this person already exist?
			$this->load_similar();
			$table = $wpdb->prefix.PEOPLE_TBNAME;
			$data = $this->to_array();
			unset($data['person_id']);
			if($this->id != ''){
				$where = array( 'person_id' => $this->id );  
				$wpdb->update($table, $data, $where);
			}else{
				$wpdb->insert($table, $data);
			    $this->id = $wpdb->insert_id;   
			}
			return ($wpdb->insert_id);
		}else{
			return false;
		}
	}
	
	function validate(){
		if( !preg_match('/^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,4}$/', $this->email) ){
			$this->errors[] = __('Please provide a valid email address.', 'dbem');
			return false;
		}
		return true;
	}
	
	/**
	 * Checks agains the database to see if this user exists already
	 * @return boolean|int
	 */
	function find_similar(){
		global $wpdb;
		$sql = "SELECT * FROM ". $wpdb->prefix.PEOPLE_TBNAME ." WHERE person_name='%s' AND person_email='%s' AND person_phone='%s'";
		$row = $wpdb->get_row( $wpdb->prepare($sql, array($this->name, $this->email, $this->phone)), ARRAY_A );
		if( is_array($row) ){
			return $row['person_id'];
		}
		return false;
	}
	
	/**
	 * Checks if a similar record exists (same name, email and phone) and if so it loads
	 * @return boolean
	 */
	function load_similar(){
		$return = $this->find_similar();
		if( is_numeric($return) ){
			$this->id = $return;
			return true;
		}
		return false;
	}
}
?>