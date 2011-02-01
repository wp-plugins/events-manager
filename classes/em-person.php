<?php
// TODO make person details more secure and integrate with WP user data 
class EM_Person extends EM_Object{
	//DB Fields
	var $id = '';
	var $owner = '';
	var $name = '';
	var $email = '';
	var $phone = '';
	//Other Vars
	var $fields = array( 
		'person_id' => array('name'=>'id','type'=>'%d'), 
		'person_owner' => array('name'=>'owner','type'=>'%d'), 
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
			}elseif( is_numeric($person_data) ){
				//Retreiving from the database		
				global $wpdb;			
				$sql = "SELECT * FROM ". $wpdb->prefix . EM_PEOPLE_TABLE ." WHERE person_id ='$person_data'";   
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
		$sql = "SELECT * FROM ". $wpdb->prefix.EM_PEOPLE_TABLE ." WHERE " . implode(' AND ', $conds) ;
		$result = $wpdb->get_row($sql, ARRAY_A);
		if($result){
			$this->to_object($result);
			return true;	
		}else{
			return false;
		}
	}
	
	function get_post(){
		$this->name = ( !empty($_REQUEST['person_name']) ) ? stripslashes($_REQUEST['person_name']) : '' ;
		$this->email = ( !empty($_REQUEST['person_email']) ) ? $_REQUEST['person_email'] : '';
		$this->phone = ( !empty($_REQUEST['person_phone']) ) ? $_REQUEST['person_phone'] : '';
		return apply_filters('em_person_get_post', $this->validate(), $this);
	}
	
	function save(){
		global $wpdb;
		do_action('em_person_save_pre',$this);
		if($this->validate()){
			//Does this person already exist?
			$this->load_similar();
			$table = $wpdb->prefix.EM_PEOPLE_TABLE;
			$data = $this->to_array();
			unset($data['person_id']);
			if($this->id != ''){
				$where = array( 'person_id' => $this->id );  
				$result = $wpdb->update($table, $data, $where);
			}else{
				$result = $wpdb->insert($table, $data);
			    $this->id = $wpdb->insert_id;   
			}
			$this->feedback_message = ($result) ? __('Changes saved.'):  __('Could not edit person details.','dbem');
			return apply_filters('em_save_delete', $result, $this);
		}else{
			$this->feedback_message = __('Could not edit person details.','dbem');
			return apply_filters('em_save_delete', false, $this);
		}
	}
	
	function delete(){
		global $wpdb;
		do_action('em_person_delete_pre', $this);
		$results = array();
		$results[] = $wpdb->query ( $wpdb->prepare("DELETE FROM ". $wpdb->prefix . EM_PEOPLE_TABLE ." WHERE person_id=%d", $this->id) );
		$results[] = $wpdb->query ( $wpdb->prepare("DELETE FROM ". $wpdb->prefix . EM_BOOKINGS_TABLE ." WHERE person_id=%d", $this->id) );
		return apply_filters('em_event_delete', !in_array(false,$results), $this);
	}
	
	function validate(){
		if( !preg_match('/^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,4}$/', $this->email) ){
			$this->errors[] = __('Please provide a valid email address.', 'dbem');
			return false;
		}
		return true;
	}
	
	function get_bookings(){
		global $wpdb;
		$results = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix.EM_BOOKINGS_TABLE." WHERE person_id={$this->id}",ARRAY_A);
		$bookings = array();
		foreach($results as $booking_data){
			$bookings[] = new EM_Booking($booking_data);
		}
		return $bookings;
	}
	
	/**
	 * Checks agains the database to see if this user exists already
	 * @return boolean|int
	 */
	function find_similar(){
		global $wpdb;
		$sql = "SELECT * FROM ". $wpdb->prefix.EM_PEOPLE_TABLE ." WHERE person_name='%s' AND person_email='%s' AND person_phone='%s'";
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
	
	/**
	 * Can the user manage this event? 
	 */
	function can_manage(){
		return ( get_option('dbem_permissions_events') || $this->owner == get_current_user_id() || em_verify_admin() );
	}
}
?>