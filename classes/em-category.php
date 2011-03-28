<?php
//TODO expand em_category to be like other classes
class EM_Category extends EM_Object {	
	//DB Fields
	var $id = '';
	var $slug = '';
	var $owner = '';
	var $name = '';
	//Other Vars
	var $fields = array(
		'category_id' => array('name'=>'id','type'=>'%d'),
		'category_slug' => array('name'=>'slug','type'=>'%s'), 
		'category_owner' => array('name'=>'owner','type'=>'%d'),
		'category_name' => array('name'=>'name','type'=>'%s')
	);
	var $required_fields;
	var $feedback_message = "";
	var $errors = array();
	
	/**
	 * Gets data from POST (default), supplied array, or from the database if an ID is supplied
	 * @param $location_data
	 * @return null
	 */
	function EM_Category( $category_data = false ) {
		global $wpdb;
		//Initialize
		$this->required_fields = array("category_name" => __('The category name', 'dbem'));
		$category = array();
		if( !empty($category_data) ){
			//Load location data
			if( is_array($category_data) && isset($category_data['category_name']) ){
				$category = $category_data;
			}elseif( is_numeric($category_data) ){
				//Retreiving from the database		
				$sql = "SELECT * FROM ". $wpdb->prefix.EM_CATEGORIES_TABLE ." WHERE category_id ='{$category_data}'";   
			  	$category = $wpdb->get_row($sql, ARRAY_A);
			}else{
				$sql = "SELECT * FROM ". $wpdb->prefix.EM_CATEGORIES_TABLE ." WHERE category_slug ='{$category_data}'";   
			  	$category = $wpdb->get_row($sql, ARRAY_A);
			}
			//Save into the object
			$this->to_object($category);
		} 
		do_action('em_category',$this, $category_data);
	}
	
	function get_post(){
		//We are getting the values via POST or GET
		do_action('em_location_get_post_pre', $this);
		$category = array();
		$category['category_id'] = ( !empty($_POST['category_id']) ) ? $_POST['category_id']:'';
		$category['category_name'] = ( !empty($_POST['category_name']) ) ? stripslashes($_POST['category_name']):'';
		$category['category_owner'] = ( !empty($_POST['category_owner']) && is_numeric($_POST['category_owner']) ) ? $_POST['category_owner']:get_current_user_id();
		$this->to_object( apply_filters('em_category_get_post', $category, $this) );
	}
	
	function save(){
		global $wpdb;
		$result = false;
		if( $this->can_manage('edit_categories') ){
			do_action('em_category_save_pre', $this);
			$table = $wpdb->prefix.EM_CATEGORIES_TABLE;
			$this->slug = $this->sanitize_title();
			$data = $this->to_array();
			unset($data['category_id']);
			if($this->id != ''){
				$where = array( 'category_id' => $this->id );  
				$result = $wpdb->update($table, $data, $where, $this->get_types($data));
			}else{
				$wpdb->insert($table, $data, $this->get_types($data));
			    $result = $this->id = $wpdb->insert_id;   
			}
		}
		return apply_filters('em_category_save', ($result !== false), $this);
	}
	
	/**
	 * Takes the title and gives either a unique slug or returns the currently used slug if this record already has it.
	 * @param unknown_type $title
	 */
	function sanitize_title($iteration = 1){
		global $wpdb;
		//Generate the slug. If this is a new event, create the slug automatically, if not, verify it is still unique and if not rewrite
		if( empty($this->slug) ){
			$this->slug = sanitize_title($this->name);
		}
		$slug = $this->slug;
		$slug_matches = $wpdb->get_results('SELECT category_id FROM '.$wpdb->prefix.EM_LOCATIONS_TABLE." WHERE category_slug='{$slug}'", ARRAY_A);
		if( count($slug_matches) > 0 ){ //we will check that the slug is unique
			if( $slug_matches[0]['category_id'] != $this->id || count($slug_matches) > 1 ){
				//we have a conflict, so try another alternative
				$this->slug = preg_replace('/\-[0-9]+$/', '', $slug).'-'.($iteration+1);
				$this->sanitize_title($iteration+1);
			}
		}
		return apply_filters('em_location_sanitize_title', $this->slug, $this);
	}
	
	function delete(){
		global $wpdb;
		$result = false;
		if( $this->can_manage('edit_categories') ){
			do_action('em_category_delete_pre', $this);
			$table_name = $wpdb->prefix.EM_CATEGORIES_TABLE;
			$sql = "DELETE FROM $table_name WHERE category_id = '{$this->id}';";
			$result = $wpdb->query($sql);
		}
		return apply_filters('em_category_delete', $result, $this);
	}

	/**
	 * Validates the category. Should be run during any form submission or saving operation.
	 * @return boolean
	 */
	function validate(){
		$missing_fields = Array ();
		foreach ( $this->required_fields as $key => $field ) {
			$true_field = $this->fields[$key]['name'];
			if ( $this->$true_field == "") {
				$missing_fields[] = $field;
			}
		}
		if ( count($missing_fields) > 0){
			// TODO Create friendly equivelant names for missing fields notice in validation 
			$this->errors[] = __ ( 'Missing fields: ' ) . implode ( ", ", $missing_fields ) . ". ";
		}
		return apply_filters('em_category_validate', ( count($this->errors) == 0 ), $this);
	}
	
	function has_events(){
		global $wpdb;	
		$events_table = $wpdb->prefix.EM_EVENTS_TABLE;
		$sql = "SELECT count(event_id) as events_no FROM $events_table WHERE category_id = {$this->id}";   
	 	$affected_events = $wpdb->get_row($sql);
		return apply_filters('em_category_has_events', (count($affected_events) > 0), $this);
	}
	
	function output_single($target = 'html'){
		$format = get_option ( 'dbem_single_category_format' );
		return apply_filters('em_category_output_single', $this->output($format, $target), $this, $target);	
	}
	
	function output($format, $target="html") {
		$category_string = $format;		 
		preg_match_all("/#_[A-Za-z]+/", $format, $placeholders);
		foreach($placeholders[0] as $result) {
			$match = true;
			$replace = '';
			switch( $result ){
				case '#_CATEGORYNAME':
					$replace = $this->name;
					break;
				case '#_CATEGORYID':
					$replace = $this->id;
					break;
				default:
					$match = false;
					break;
			}
			if($match){ //if true, we've got a placeholder that needs replacing
				//TODO FILTER - placeholder filter
				$replace = apply_filters('em_category_output_placeholder', $replace, $this, $result, $target); //USE WITH CAUTION! THIS MIGHT GET RENAMED
				$category_string = str_replace($result, $replace , $category_string );
			}
		}
		$name_filter = ($target == "html") ? 'dbem_general':'dbem_general_rss'; //TODO remove dbem_ filters
		$category_string = str_replace('#_CATEGORY', apply_filters($name_filter, $this->name) , $category_string ); //Depreciated
		return apply_filters('em_category_output', $category_string, $this, $format, $target);	
	}
	
	function can_manage( $capability_owner = 'edit_categories', $capability_admin = false ){
		return current_user_can($capability_owner);
	}
}
?>