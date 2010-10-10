<?php
/**
 * Object that holds location info and related functions
 * @author marcus
 */
class EM_Location extends EM_Object {
	//DB Fields
	var $id = '';
	var $name = '';
	var $address = '';
	var $town = '';
	var $latitude = '';
	var $longitude = '';
	var $description = '';
	var $image_url = '';
	//Other Vars
	var $fields = array( 
		'location_id' => array('name'=>'id','type'=>'%d'), 
		'location_name' => array('name'=>'name','type'=>'%s'), 
		'location_address' => array('name'=>'address','type'=>'%s'),
		'location_town' => array('name'=>'town','type'=>'%s'),
		//Not Used - 'location_province' => array('name'=>'province','type'=>'%s'),
		'location_latitude' =>  array('name'=>'latitude','type'=>'%f'),
		'location_longitude' => array('name'=>'longitude','type'=>'%f'),
		'location_description' => array('name'=>'description','type'=>'%s')
	);
	var $required_fields;
	var $feedback_message = "";
	var $mime_types = array(1 => 'gif', 2 => 'jpg', 3 => 'png'); 
	var $errors = array();
	
	/**
	 * Gets data from POST (default), supplied array, or from the database if an ID is supplied
	 * @param $location_data
	 * @return null
	 */
	function EM_Location( $location_data = 0 ) {
		//Initialize
		$this->required_fields = array("name" => __('The location name', 'dbem'), "address" => __('The location address', 'dbem'), "town" => __('The location town', 'dbem'));
		if( $location_data != 0 ){
			//Load location data
			if( is_array($location_data) && isset($location_data['location_name']) ){
				$location = $location_data;
			}elseif( $location_data > 0 ){
				//Retreiving from the database		
				global $wpdb;
				$sql = "SELECT * FROM ". $wpdb->prefix.LOCATIONS_TBNAME ." WHERE location_id ='{$location_data}'";   
			  	$location = $wpdb->get_row($sql, ARRAY_A);
			}
			//If gmap is turned off, values may not be returned and set, so we set it here
			if(empty($location['location_latitude'])) {
				$location['location_latitude']  = 0;
				$location['location_longitude'] = 0;
			}
			//Save into the object
			$this->to_object($location, true);
			$this->get_image_url();
		} 
	}
	
	function get_post(){
		//We are getting the values via POST or GET
		$location = array();
		$location['location_id'] = $_POST['location_id'];
		$location['location_name'] = stripslashes($_POST['location_name']);
		$location['location_address'] = stripslashes($_POST['location_address']); 
		$location['location_town'] = stripslashes($_POST['location_town']); 
		$location['location_latitude'] = $_POST['location_latitude'];
		$location['location_longitude'] = $_POST['location_longitude'];
		$location['location_description'] = stripslashes($_POST['content']);
		$this->to_object($location);
	}
	
	function save(){
		global $wpdb;
		$table = $wpdb->prefix.LOCATIONS_TBNAME;
		$data = $this->to_array();
		unset($data['location_id']);
		unset($data['location_image_url']);
		if($this->id != ''){
			$where = array( 'location_id' => $this->id );  
			$wpdb->update($table, $data, $where, $this->get_types($data));
		}else{
			$wpdb->insert($table, $data, $this->get_types($data));
		    $this->id = $wpdb->insert_id;   
		}
		$image_upload = $this->image_upload();
		return ( $this->id > 0 && $image_upload );
	}
	
	function delete(){
		global $wpdb;	
		$table_name = $wpdb->prefix.LOCATIONS_TBNAME;
		$sql = "DELETE FROM $table_name WHERE location_id = '{$this->id}';";
		$wpdb->query($sql);
		$this->image_delete();	
	}
	
	function get_image_url(){
		if($this->image_url == ''){
		  	foreach($this->mime_types as $type) { 
				$file_path = "/".IMAGE_UPLOAD_DIR."/location-{$this->id}.$type";
				if( file_exists( ABSPATH . $file_path) ) {
					$result = get_bloginfo('wpurl').$file_path;
		  			$this->image_url = $result;
				}
			}
		}
		return $this->image_url;
	}
	
	function image_delete() {
		$file_name= ABSPATH.IMAGE_UPLOAD_DIR."/location-".$this->id;
		foreach($this->mime_types as $type) { 
			if (file_exists($file_name.".".$type))
	  		unlink($file_name.".".$type);
		}
	}
	
	function image_upload(){	
		//TODO better image upload error handling
		if ($_FILES['location_image']['size'] > 0 ) {	
		  	if( !file_exists(ABSPATH.IMAGE_UPLOAD_DIR) ){
				mkdir(ABSPATH.IMAGE_UPLOAD_DIR, 0777);
		  	}
			$this->image_delete();   
			list($width, $height, $type, $attr) = getimagesize($_FILES['location_image']['tmp_name']);
			$image_path = ABSPATH.IMAGE_UPLOAD_DIR."/location-".$this->id.".".$this->mime_types[$type];
			if (!move_uploaded_file($_FILES['location_image']['tmp_name'], $image_path)){
				$this->errors = __('The image could not be loaded','dbem');
				return false;
			}else{
				return true;
			}
		}
		return true;
	}

	function load_similar($criteria){
		global $wpdb;
		$locations_table = $wpdb->prefix.LOCATIONS_TBNAME; 
		$prepared_sql = $wpdb->prepare("SELECT * FROM $locations_table WHERE location_name = %s AND location_address = %s AND location_town = %s", stripcslashes($criteria['location_name']), stripcslashes($criteria['location_address']), stripcslashes($criteria['location_town']) );
		//$wpdb->show_errors(true);
		$location = $wpdb->get_row($prepared_sql, ARRAY_A);
		if( is_array($location) ){
			$this->to_object($location);
		}
		return $location;
	}

	/**
	 * Validates the location. Should be run during any form submission or saving operation.
	 * @return boolean
	 */
	function validate(){
		foreach ( $this->required_fields as $field => $description) {
			if ( $this->$field == "" ) {
				$this->errors[] = $description.__(" is missing!", "dbem");
			}       
		}
		if ($_FILES['location_image']['size'] > 0 ) { 
			if (is_uploaded_file($_FILES['location_image']['tmp_name'])) {
	 	 		$mime_types = array(1 => 'gif', 2 => 'jpg', 3 => 'png');
				$maximum_size = get_option('dbem_image_max_size'); 
				if ($_FILES['location_image']['size'] > $maximum_size){ 
			     	$this->errors[] = __('The image file is too big! Maximum size:', 'dbem')." $maximum_size";
				}
		  		list($width, $height, $type, $attr) = getimagesize($_FILES['location_image']['tmp_name']);
				$maximum_width = get_option('dbem_image_max_width'); 
				$maximum_height = get_option('dbem_image_max_height'); 
			  	if (($width > $maximum_width) || ($height > $maximum_height)) { 
					$this->errors[] = __('The image is too big! Maximum size allowed:')." $maximum_width x $maximum_height";
			  	}
			  	if (($type!=1) && ($type!=2) && ($type!=3)){ 
					$this->errors[] = __('The image is in a wrong format!');
			  	}
	  		}
		}
		return ( count($this->errors) == 0 );
	}
	
	function has_events(){
		global $wpdb;	
		$events_table = $wpdb->prefix.EVENTS_TBNAME;
		$sql = "SELECT event_id FROM $events_table WHERE location_id = {$this->id}";   
	 	$affected_events = $wpdb->get_results($sql);
		return (count($affected_events) > 0);
	}
	
	function output_single($target = 'html'){
		$format = get_option ( 'dbem_single_location_format' );
		return $this->output($format, $target);	
	}
	
	function output($format, $target="html") {
		$location_string = $format;
		preg_match_all("/#@?_?[A-Za-z]+/", $format, $placeholders);
		foreach($placeholders[0] as $result) {   
			// matches alla fields placeholder
			if (preg_match('/#_MAP/', $result)) {
			 	$map_div = EM_Map::get_single( array('location' => $this) );
			 	$location_string = str_replace($result, $map_div , $location_string );
			}
			if ( preg_match('/#_(LOC(ATION)?)?(NOTES|EXCERPT)/', $result) ) {
				if ($target == "html"){
					//If excerpt, we use more link text
					if($result == "#_LOCEXCERPT" || $result == "#_EXCERPT"){
						$matches = explode('<!--more-->', $this->notes);
						$field_value = apply_filters('dbem_notes_excerpt', $matches[0]);
					}else{
						$field_value = apply_filters('dbem_notes', $this->description);
					}
					//$field_value = apply_filters('the_content', $field_value); - chucks a wobbly if we do this.
				}else{
					if ($target == "map"){
						$field_value = apply_filters('dbem_notes_map', $field_value);
					} else {
			  			if($result == "#_LOCEXCERPT" || $result == "#_EXCERPT"){
							$matches = explode('<!--more-->', $this->notes);
							$field_value = htmlentities($matches[0]);
							$field_value = apply_filters('dbem_notes_rss', $field_value);
						}else{
							$field_value = apply_filters('dbem_notes_rss', $field_value);
						}
					}
					$field_value = apply_filters('the_content_rss', $field_value);
				  }
				  $location_string = str_replace($result, $field_value , $location_string );
			}  
			if (preg_match('/#_(LOCATION|NAME)$/', $result)) {   
				if ($target == "html"){
					$field_value = apply_filters('dbem_general', $this->name); 
				}else{ 
					$field_value = apply_filters('dbem_general_rss', $this->name);
				} 
				$location_string = str_replace($result, $field_value , $location_string ); 
		 	}
			if (preg_match('/#_(PASTEVENTS|NEXTEVENTS|ALLEVENTS)/', $result)) {
				if ($result == '#_PASTEVENTS'){ $scope = 'past'; }
				elseif ( $result == '#_NEXTEVENTS' ){ $scope = 'future'; }
				else{ $scope = 'all'; }
				$events = EM_Events::get( array('location'=>$this->id, 'scope'=>$scope) );
				$list = '';
				if ( count($events) > 0 ){
					foreach($events as $event){
						$list .= $event->output(get_option('dbem_location_event_list_item_format'));
					}
				} else {
					$list = get_option('dbem_location_no_events_message');
				}
			 	$location_string = str_replace($result, $list , $location_string ); 
			}	
			if (preg_match('/#_IMAGE$/', $result)) {
        		if($this->image_url != ''){
					$location_image = "<img src='".$this->image_url."' alt='".$this->name."'/>";
        		}else{
					$location_image = "";
        		}
				$location_string = str_replace($result, $location_image , $location_string ); 
			}
			if (preg_match('/#_(LOCATION(PAGE)?URL)/', $result)) { 
				$joiner = (stristr(EM_URI, "?")) ? "&amp;" : "?";
				$venue_page_link = EM_URI.$joiner."location_id=".$this->id;
		       	$location_string = str_replace($result, $venue_page_link , $location_string ); 
			}	 
			if (preg_match('/#_(LOCATIONLINK)/', $result)) { 
				$joiner = (stristr(EM_URI, "?")) ? "&amp;" : "?";
				$venue_page_link = EM_URI.$joiner."location_id=".$this->id;
				$venue_page_link = '<a href="'.$venue_page_link.'">'.$this->name.'</a>';
		       	$location_string = str_replace($result, $venue_page_link , $location_string ); 
			}	 
			if (preg_match('/#_(ADDRESS|TOWN|PROVINCE)/', $result)) { //TODO province in location is not being used
				$field = ltrim(strtolower($result), "#_");
				if ($target == "html") {    
						$field_value = apply_filters('dbem_general', $this->$field); 
				} else { 
						$field_value = apply_filters('dbem_general_rss', $this->$field); 
				}
				$location_string = str_replace($result, $field_value , $location_string ); 
		 	}
		}
		return $location_string;	
	}
}