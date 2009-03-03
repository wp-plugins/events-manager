<?php  
$feedback_message = "";
 
$location_required_fields = array("location_name" => __('The location name', 'dbem'), "location_address" => __('The location address', 'dbem'), "location_town" => __('The location town', 'dbem'));


add_action('init', 'dbem_intercept_locations_actions');
function dbem_intercept_locations_actions() {
	if(isset($_GET['page']) && $_GET['page'] == "locations") {
  	if(isset($_GET['doaction2']) && $_GET['doaction2'] == "Delete") {
	  	if(isset($_GET['action2']) && $_GET['action2'] == "delete") {
				$locations = $_GET['locations'];
				foreach($locations as $location_ID) {
				 	dbem_delete_location($location_ID);
				}
			}
		}
	}
}

function dbem_locations_page() {      

	if(isset($_GET['action']) && $_GET['action'] == "edit") { 
		// edit location  
		$location_id = $_GET['location_ID'];
		$location = dbem_get_location($location_id);
		dbem_locations_edit_layout($location);
  } else { 
    if(isset($_POST['action']) && $_POST['action'] == "editedlocation") { 
		
			// location update required  
			$location = array();
			$location['location_id'] = $_POST['location_ID'];
			$location['location_name'] = $_POST['location_name'];
			$location['location_address'] = $_POST['location_address']; 
			$location['location_town'] = $_POST['location_town']; 
			$location['location_latitude'] = $_POST['location_latitude'];
			$location['location_longitude'] = $_POST['location_longitude'];
			$location['location_description'] = $_POST['location_description'];
			
			if(empty($location['location_latitude'])) {
				$location['location_latitude']  = 0;
				$location['location_longitude'] = 0;
			}
			
			$validation_result = dbem_validate_location($location);
			if ($validation_result == "OK") {
				  
				dbem_update_location($location); 
				    
			  if ($_FILES['location_image']['size'] > 0 )
					dbem_upload_location_picture($location);
				$message = __('The location has been updated.', 'dbem');
				
				$locations = dbem_get_locations();
				dbem_locations_table_layout($locations, $message);
			} else {
				$message = $validation_result;   
				dbem_locations_edit_layout($location, $message);
			}
		} elseif(isset($_POST['action']) && $_POST['action'] == "addlocation") {    
				$location = array();
				$location['location_name'] = $_POST['location_name'];
				$location['location_address'] = $_POST['location_address'];
				$location['location_town'] = $_POST['location_town']; 
				$location['location_latitude'] = $_POST['location_latitude'];
				$location['location_longitude'] = $_POST['location_longitude'];
				$location['location_description'] = $_POST['location_description'];
				$validation_result = dbem_validate_location($location);
				if ($validation_result == "OK") {   
					$new_location = dbem_insert_location($location);   
		 			// uploading the image
				 
					if ($_FILES['location_image']['size'] > 0 ) {
						dbem_upload_location_picture($new_location);
			    }
					
					 
					
					
					
					
					// -------------
					
					//RESETME $message = __('The location has been added.', 'dbem'); 
					$locations = dbem_get_locations();
					dbem_locations_table_layout($locations, null,$message);
				} else {
					$message = $validation_result;
					$locations = dbem_get_locations();
					   
					dbem_locations_table_layout($locations, $location, $message);
				}
				
				
				
			} else {  
			// no action, just a locations list
			$locations = dbem_get_locations();
			dbem_locations_table_layout($locations, $message);
  	}
	} 
}  

function dbem_locations_edit_layout($location, $message = "") {
	$layout = "
	<div class='wrap'>
		<div id='icon-edit' class='icon32'>
			<br/>
		</div>
			
		<h2>".__('Edit location', 'dbem')."</h2>";   
 		
		if($message != "") {
			$layout .= "
		<div id='message' class='updated fade below-h2' style='background-color: rgb(255, 251, 204);'>
			<p>$message</p>
		</div>";
		}
		$layout .= "
		<div id='ajax-response'></div>

		<form enctype='multipart/form-data' name='editcat' id='editcat' method='post' action='admin.php?page=locations' class='validate'>
		<input type='hidden' name='action' value='editedlocation' />
		<input type='hidden' name='location_ID' value='".$location['location_id']."'/>";
		
		$layout .= "<table class='form-table'>
				<tr class='form-field form-required'>
					<th scope='row' valign='top'><label for='location_name'>".__('Location name', 'dbem')."</label></th>
					<td><input name='location_name' id='location-name' type='text' value='".$location['location_name']."' size='40'  /><br />
		           ".__('The name of the location', 'dbem')."</td>
				</tr>

				<tr class='form-field'>
					<th scope='row' valign='top'><label for='location_address'>".__('Location address', 'dbem')."</label></th>
					<td><input name='location_address' id='location-address' type='text' value='".$location['location_address']."' size='40' /><br />
		            ".__('The address of the location', 'dbem').".</td>

				</tr>
				
				<tr class='form-field'>
					<th scope='row' valign='top'> <label for='location_town'>".__('Location town', 'dbem')."</label></th>
					<td><input name='location_town' id='location-town' type='text' value='".$location['location_town']."' size='40' /><br />
		            ".__('The town where the location is located', 'dbem').".</td>

				</tr>";
				$gmap_is_active = get_option('dbem_gmap_is_active');
				if ($gmap_is_active) {  
			  	$layout .= " 
			    
				 <tr style='display:none;'>
				  <td>Coordinates</td>
					<td><input id='location-latitude' name='location_latitude' id='location_latitude' type='text' value='".$location['location_latitude']."' size='15'  />
					<input id='location-longitude' name='location_longitude' id='location_longitude' type='text' value='".$location['location_longitude']."' size='15'  /></td>
				 </tr>\n
			
				<tr>
			 <th scope='row' valign='top'><label for='location_map'>".__('Location map', 'dbem')."</label></th>
										<td>
										<div id='map-not-found' style='width: 450px; font-size: 140%; text-align: center; margin-top: 100px; display: hide'><p>".__('Map not found')."</p></div>
						 <div id='event-map' style='width: 450px; height: 300px; background: green; display: hide; margin-right:8px'></div></td></tr>";   
						}
			$layout .= "
				<tr class='form-field'>
					<th scope='row' valign='top'><label for='location_description'>".__('Location description', 'dbem')."</label></th>
					<td><textarea name='location_description' id='location_description' rows='5' cols='50' style='width: 97%;'>".$location['location_description']."</textarea><br />
		            ".__('A description of the Location. You may include any kind of info here.', 'dbem')."</td>

				</tr>
				<tr class='form-field'>
					<th scope='row' valign='top'><label for='location_picture'>".__('Location image', 'dbem')."</label></th>
					<td>";
					if ($location['location_image_url'] != '') 
						$layout .= "<img src='".$location['location_image_url']."' alt='".$location['location_name']."'/>";
					else 
						$layout .= __('No image uploaded for this location yet', 'debm');
					
					$layout .= "</td>

				</tr>
				<tr>
					<th scope='row' valign='top'><label for='location_image'>".__('Upload/change picture', 'dbem')."</label></th>
					<td><input id='location-image' name='location_image' id='location_image' type='file' size='40' /></td>
			 </tr>\n
			</table>
		<p class='submit'><input type='submit' class='button-primary' name='submit' value='".__('Update location', 'dbem')."' /></p>
		</form>
		   
   	
	</div>
			
	";  
	echo $layout;
}

function dbem_locations_table_layout($locations, $new_location, $message = "") {
	$destination = get_bloginfo('url')."/wp-admin/admin.php"; 
	$table = "
		<div class='wrap nosubsub'>\n
			<div id='icon-edit' class='icon32'>
				<br/>
			</div>
 	 		<h2>".__('Locations', 'dbem')."</h2>\n ";   
	 		
			if($message != "") {
				$table .= "
			<div id='message' class='updated fade below-h2' style='background-color: rgb(255, 251, 204);'>
				<p>$message</p>
			</div>";
			}
			$table .= "
			<div id='col-container'>\n
				<div id='col-right'>\n
			 	 <div class='col-wrap'>\n       
				 	 <form id='bookings-filter' method='get' action='$destination'>\n
						<input type='hidden' name='page' value='locations'/>\n
						<input type='hidden' name='action' value='edit_location'/>\n
						<input type='hidden' name='event_id' value='$event_id'/>\n";
						
						if (count($locations)>0) {
						$table .= "<table class='widefat'>\n
							<thead>\n
								<tr>\n
									<th class='manage-column column-cb check-column' scope='col'><input type='checkbox' class='select-all' value='1'/></th>\n
									<th>".__('Name', 'dbem')."</th>\n
									<th>".__('Address', 'dbem')."</th>\n
									<th>".__('Town', 'dbem')."</th>\n                
								</tr>\n 
							</thead>\n
							<tfoot>\n
								<tr>\n
									<th class='manage-column column-cb check-column' scope='col'><input type='checkbox' class='select-all' value='1'/></th>\n
									<th>".__('Name', 'dbem')."</th>\n
									<th>".__('Address', 'dbem')."</th>\n
									<th>".__('Town', 'dbem')."</th>\n      
								</tr>\n             
							</tfoot>\n
							<tbody>\n";
						foreach ($locations as $this_location) {
							$table .= "		
								<tr>\n
								<td><input type='checkbox' class ='row-selector' value='".$this_location['location_id']."' name='locations[]'/></td>\n
								<td><a href='".get_bloginfo('url')."/wp-admin/admin.php?page=locations&amp;action=edit&amp;location_ID=".$this_location['location_id']."'>".$this_location['location_name']."</a></td>\n
								<td>".$this_location['location_address']."</td>\n
								<td>".$this_location['location_town']."</td>\n                         
								</tr>\n";
						}
						$table .= "
							</tbody>\n

						</table>\n

						<div class='tablenav'>\n
							<div class='alignleft actions'>\n
							<input type='hidden' name='action2' value='delete'/>
						 	<input class='button-secondary action' type='submit' name='doaction2' value='Delete'/>\n
							<br class='clear'/>\n 
							</div>\n
							<br class='clear'/>\n
							</div>\n";
						} else {
								$table .= "<p>".__('No venues have been inserted yet!', 'dbem');
						}
						 $table .= "
						</form>\n
					</div>\n
				</div>  <?-- end col-right -->\n     
				
				<div id='col-left'>\n
			  	<div class='col-wrap'>\n
						<div class='form-wrap'>\n 
							<div id='ajax-response'/>
					  	<h3>".__('Add location', 'dbem')."</h3>\n
							 <form enctype='multipart/form-data' name='addlocation' id='addlocation' method='post' action='admin.php?page=locations' class='add:the-list: validate'>\n
							 		
									<input type='hidden' name='action' value='addlocation' />\n
							    <div class='form-field form-required'>\n
							      <label for='location_name'>".__('Location name', 'dbem')."</label>\n
								 	<input id='location-name' name='location_name' id='location_name' type='text' value='".$new_location['location_name']."' size='40' />\n
								    <p>".__('The name of the location', 'dbem').".</p>\n
								 </div>\n
               
								 <div class='form-field'>\n
								   <label for='location_address'>".__('Location address', 'dbem')."</label>\n
								 	<input id='location-address' name='location_address' id='location_address' type='text' value='".$new_location['location_address']."' size='40'  />\n
								    <p>".__('The address of the location', 'dbem').".</p>\n
								 </div>\n
               
								 <div class='form-field '>\n
								   <label for='location_town'>".__('Location town', 'dbem')."</label>\n
								 	<input id='location-town' name='location_town' id='location_town' type='text' value='".$new_location['location_town']."' size='40'  />\n
								    <p>".__('The town of the location', 'dbem').".</p>\n
								 </div>\n   
								
							     <div class='form-field' style='display:none;'>\n
								   <label for='location_latitude'>LAT</label>\n
								 	<input id='location-latitude' name='location_latitude' type='text' value='".$new_location['location_latitude']."' size='40'  />\n
								 </div>\n
								 <div class='form-field' style='display:none;'>\n
								   <label for='location_longitude'>LONG</label>\n
								 	<input id='location-longitude' name='location_longitude' type='text' value='".$new_location['location_longitude']."' size='40'  />\n
								 </div>\n
								
								 <div class='form-field'>\n
								   <label for='location_image'>".__('Location image', 'dbem')."</label>\n
								 	<input id='location-image' name='location_image' id='location_image' type='file' size='35' />\n
								    <p>".__('Select an image to upload', 'dbem').".</p>\n
								 </div>\n";
									$gmap_is_active = get_option('dbem_gmap_is_active'); 
									
                 	if ($gmap_is_active) {
							 		 	$table .= "<div id='map-not-found' style='width: 450px; float: right; font-size: 140%; text-align: center; margin-top: 100px; display: hide'><p>".__('Map not found')."</p></div>";
								 		$table .= "<div id='event-map' style='width: 450px; height: 300px; background: green; float: right; display: hide; margin-right:8px'></div>";   
							}   
								$table .= "
								 	<div class='form-field'>\n
								 	<label for='location_description'>".__('Location description', 'dbem')."</label>\n
								 	<textarea name='location_description' id='location_description' rows='5' cols='40'>".$new_location['location_description']."</textarea>\n
								    <p>".__('A description of the location. You may include any kind of info here.', 'dbem')."</p>\n
								 </div>\n
               
								 <p class='submit'><input type='submit' class='button' name='submit' value='".__('Add location', 'dbem')."' /></p>\n
							 </form>\n   

					  </div>\n
					</div>\n 
				</div>  <?-- end col-left -->\n   
			</div>\n 
  	</div>\n";                                                

	echo $table;  
}

	 

function dbem_get_locations($eventful = false) { 
	global $wpdb;
	$locations_table = $wpdb->prefix.LOCATIONS_TBNAME; 
	$events_table = $wpdb->prefix.EVENTS_TBNAME;
	if ($eventful == 'true') {
		$sql = "SELECT * from $locations_table JOIN $events_table ON $locations_table.location_id = $events_table.location_id";
	} else {
		$sql = "SELECT location_id, location_address, location_name, location_town,location_latitude, location_longitude 
			FROM $locations_table ORDER BY location_name";   
	}

	$locations = $wpdb->get_results($sql, ARRAY_A); 
	return $locations;  

}

function dbem_get_location($location_id) { 
	global $wpdb;
	$locations_table = $wpdb->prefix.LOCATIONS_TBNAME; 
	$sql = "SELECT * FROM $locations_table WHERE location_id ='$location_id'";   
  $location = $wpdb->get_row($sql, ARRAY_A);
	$location['location_image_url'] = dbem_image_url_for_location_id($location['location_id']);
	return $location;  

}

function dbem_image_url_for_location_id($location_id) {
	$file_name= ABSPATH.IMAGE_UPLOAD_DIR."/location-".$location_id;
  $mime_types = array('gif','jpg','png');
	foreach($mime_types as $type) { 
		$file_path = "$file_name.$type";
		if (file_exists($file_path)) {
			$result = get_bloginfo('url')."/".IMAGE_UPLOAD_DIR."/location-$location_id.$type";
  		return $result;
		}
	}
	return '';
}

function dbem_get_identical_location($location) { 
	global $wpdb;
	
	$locations_table = $wpdb->prefix.LOCATIONS_TBNAME; 
	//$sql = "SELECT * FROM $locations_table WHERE location_name ='".$location['location_name']."' AND location_address ='".$location['location_address']."' AND location_town ='".$location['location_town']."';";   
  $prepared_sql=$wpdb->prepare("SELECT * FROM $locations_table WHERE location_name = %s AND location_address = %s AND location_town = %s", $location['location_name'], $location['location_address'], $location['location_town'] );
	$wpdb->show_errors(true);
	$cached_location = $wpdb->get_row($prepared_sql, ARRAY_A);
	return $cached_location;  

}

function dbem_validate_location($location) {
	global $location_required_fields;
	$troubles = "";
	foreach ($location_required_fields as $field => $description) {
		if ($location[$field] == "" ) {
		$troubles .= "<li>".$description.__(" is missing!", "dbem")."</li>";
		}       
	}
	if ($_FILES['location_image']['size'] > 0 ) { 
		if (is_uploaded_file($_FILES['location_image']['tmp_name'])) {
 	 		$mime_types = array(1 => 'gif', 2 => 'jpg', 3 => 'png');
			$maximum_size = get_option('dbem_image_max_size'); 
			if ($_FILES['location_image']['size'] > $maximum_size) 
	     	$troubles = "<li>".__('The image file is too big! Maximum size:', 'dbem')." $maximum_size</li>";
	  	list($width, $height, $type, $attr) = getimagesize($_FILES['location_image']['tmp_name']);
			$maximum_width = get_option('dbem_image_max_width'); 
			$maximum_height = get_option('dbem_image_max_height'); 
	  	if (($width > $maximum_width) || ($height > $maximum_height)) 
	     	$troubles .= "<li>". __('The image is too big! Maximum size allowed:')." $maximum_width x $maximum_height</li>";
	  	if (($type!=1) && ($type!=2) && ($type!=3)) 
		      $troubles .= "<li>".__('The image is in a wrong format!')."</li>";
  	} 
	}

  if ($troubles == "")
		return "OK";
	else {
		$message = __('Ach, some problems here:', 'dbem')."<ul>\n$troubles</ul>";
		return $message; 
	}
}

function dbem_update_location($location) {
	global $wpdb;
	$locations_table = $wpdb->prefix.LOCATIONS_TBNAME;
	$sql="UPDATE ".$locations_table. 
	" SET location_name='".$location['location_name']."', ".
		"location_address='".$location['location_address']."',".
		"location_town='".$location['location_town']."', ".
		"location_latitude=".$location['location_latitude'].",". 
		"location_longitude=".$location['location_longitude'].",".
		"location_description='".$location['location_description']."' ". 
		"WHERE location_id='".$location['location_id']."';";  
	$wpdb->query($sql);      

}   

function dbem_insert_location($location) {    
 
		global $wpdb;	
		$table_name = $wpdb->prefix.LOCATIONS_TBNAME; 
		// if GMap is off the hidden fields are empty, so I add a custom value to make the query work
		if (empty($location['location_longitude'])) 
			$location['location_longitude'] = 0;
		if (empty($location['location_latitude'])) 
			$location['location_latitude'] = 0;
		$sql = "INSERT INTO ".$table_name." (location_name, location_address, location_town, location_latitude, location_longitude, location_description)
		VALUES ('".$location['location_name']."','".$location['location_address']."','".$location['location_town']."',".$location['location_latitude'].",".$location['location_longitude'].",'".$location['location_description']."')"; 
		$wpdb->query($sql);
    $new_location = dbem_get_location(mysql_insert_id());            

		return $new_location;
}

function dbem_delete_location($location) {
		global $wpdb;	
		$table_name = $wpdb->prefix.LOCATIONS_TBNAME;
		$sql = "DELETE FROM $table_name WHERE location_id = '$location';";
		$wpdb->query($sql);
    dbem_delete_image_files_for_location_id($location);
}          

function dbem_location_has_events($location_id) {
	global $wpdb;	
	$events_table = $wpdb->prefix.EVENTS_TBNAME;
	$sql = "SELECT event_id FROM $events_table WHERE location_id = $location_id";   
 	$affected_events = $wpdb->get_results($sql);
	return (count($affected_events) > 0);
}             

function dbem_upload_location_picture($location) {
  	if(!file_exists("../".IMAGE_UPLOAD_DIR))
				mkdir("../".IMAGE_UPLOAD_DIR, 0777);
	dbem_delete_image_files_for_location_id($location['location_id']);
	$mime_types = array(1 => 'gif', 2 => 'jpg', 3 => 'png');    
	list($width, $height, $type, $attr) = getimagesize($_FILES['location_image']['tmp_name']);
	$image_path = "../".IMAGE_UPLOAD_DIR."/location-".$location['location_id'].".".$mime_types[$type];
	if (!move_uploaded_file($_FILES['location_image']['tmp_name'], $image_path)) 
		$msg = "<p>".__('The image could not be loaded','dbem')."</p>";
}    
function dbem_delete_image_files_for_location_id($location_id) {
	$file_name= "../".IMAGE_UPLOAD_DIR."/location-".$location_id;
	$mime_types = array(1 => 'gif', 2 => 'jpg', 3 => 'png');
	foreach($mime_types as $type) { 
		if (file_exists($file_name.".".$type))
  		unlink($file_name.".".$type);
	}
}          



function dbem_global_map($atts) {  
	if (get_option('dbem_gmap_is_active') == '1') {
	extract(shortcode_atts(array(
			'eventful' => "false",
			'scope' => 'all',
			'width' => 450,
			'height' => 300
		), $atts));                                  
	$events_page = dbem_get_events_page(true, false);
	$gmaps_key = get_option('dbem_gmap_key');
	$result = "<h3>Mappa totale</h3>";
	$result .= "<div id='dbem_global_map' style='width: {$width}px; height: {$height}px'>map</div>";
	$result .= "<script type='text/javascript'>
	<!--// 
	  eventful = $eventful;
	  scope = '$scope';
	  events_page = '$events_page';
	  GMapsKey = '$gmaps_key'; 
		location_infos = '$location_infos'
	//-->
	</script>";
	$result .= "<script src='".get_bloginfo('url')."/wp-content/plugins/events-manager/dbem_global_map.js' type='text/javascript'></script>";
	$result .= "<ol id='dbem_locations_list'></ol>"; 
	
	} else {
		$result = "";
	}
	return $result;
}
add_shortcode('locations_map', 'dbem_global_map'); 

function dbem_replace_locations_placeholders($format, $location, $target="html") {
	$location_string = $format;
	preg_match_all("/#@?_?[A-Za-z]+/", $format, $placeholders);
	foreach($placeholders[0] as $result) {    
		// echo "RESULT: $result <br>";
		// matches alla fields placeholder
		if (preg_match('/#_MAP/', $result)) {
		 	$map_div = dbem_single_location_map($location);
		 	$location_string = str_replace($result, $map_div , $location_string ); 
		 
		}
		if (preg_match('/#_PASTEVENTS/', $result)) {
		 	$list = dbem_events_in_location_list($location, "past");
		 	$location_string = str_replace($result, $list , $location_string ); 
		}
		if (preg_match('/#_NEXTEVENTS/', $result)) {
		 	$list = dbem_events_in_location_list($location);
		 	$location_string = str_replace($result, $list , $location_string ); 
		}
		if (preg_match('/#_ALLEVENTS/', $result)) {
		 	$list = dbem_events_in_location_list($location, "all");
		 	$location_string = str_replace($result, $list , $location_string ); 
		}
	  
		if (preg_match('/#_(NAME|ADDRESS|TOWN|PROVINCE|DESCRIPTION)/', $result)) {
			$field = "location_".ltrim(strtolower($result), "#_");
		 	$field_value = $location[$field];      
		
			if ($field == "location_description") {
				if ($target == "html")
					$field_value = apply_filters('dbem_notes', $field_value);
				else
				  if ($target == "map")
					$field_value = apply_filters('dbem_notes_map', $field_value);
				  else
				 	$field_value = apply_filters('dbem_notes_rss', $field_value);
		  	} else {
				if ($target == "html")    
					$field_value = apply_filters('dbem_general', $field_value); 
				else 
					$field_value = apply_filters('dbem_general_rss', $field_value); 
			}
			$location_string = str_replace($result, $field_value , $location_string ); 
	 	}
	  
		if (preg_match('/#_(IMAGE)/', $result)) {
				
        	if($location['location_image_url'] != '')
				  $location_image = "<img src='".$location['location_image_url']."' alt='".$location['location_name']."'/>";
				else
					$location_image = "";
			$location_string = str_replace($result, $location_image , $location_string ); 
		}
	 if (preg_match('/#_(LOCATIONPAGEURL)/', $result)) {
	       $venue_page_link = dbem_get_events_page(true, false)."&location_id=".$location['location_id'];
	      	$location_string = str_replace($result, $venue_page_link , $location_string ); 
	 }
			
	}
	return $location_string;	
	
}
function dbem_single_location_map($location) {
	$gmap_is_active = get_option('dbem_gmap_is_active'); 
	$map_text = dbem_replace_locations_placeholders(get_option('dbem_location_baloon_format'), $location);
	if ($gmap_is_active) {  
   		$gmaps_key = get_option('dbem_gmap_key');
   		$map_div = "<div id='dbem-location-map' style=' background: green; width: 400px; height: 300px'></div>" ;
   		$map_div .= "<script type='text/javascript'>
  			<!--// 
  		latitude = parseFloat('".$location['location_latitude']."');
  		longitude = parseFloat('".$location['location_longitude']."');
  		GMapsKey = '$gmaps_key';
  		map_text = '$map_text';
		//-->
		</script>";
		$map_div .= "<script src='".get_bloginfo('url')."/wp-content/plugins/events-manager/dbem_single_location_map.js' type='text/javascript'></script>";
	} else {
		$map_div = "";
	}
	return $map_div;
}

function dbem_events_in_location_list($location, $scope = "") {
	$events = dbem_get_events("",$scope,"","",$location['location_id']);
	$list = "";
	if (count($events) > 0) {
		foreach($events as $event)
			$list .= dbem_replace_placeholders(get_option('dbem_location_event_list_item_format'), $event);
	} else {
		$list = get_option('dbem_location_no_events_message');
	}
	return $list;
}

add_action ('admin_head', 'dbem_locations_autocomplete');  

function dbem_locations_autocomplete() {     
	if ((isset($_REQUEST['action']) && $_REQUEST['action'] == 'edit_event') || (isset($_GET['page']) && $_GET['page'] == 'new_event')) { 	 
		?>
		<link rel="stylesheet" href="../wp-content/plugins/events-manager/js/jquery-autocomplete/jquery.autocomplete.css" type="text/css"/>
    

		<script src="../wp-content/plugins/events-manager/js/jquery-autocomplete/lib/jquery.bgiframe.min.js" type="text/javascript"></script>
		<script src="../wp-content/plugins/events-manager/js/jquery-autocomplete/lib/jquery.ajaxQueue.js" type="text/javascript"></script> 

		<script src="../wp-content/plugins/events-manager/js/jquery-autocomplete/jquery.autocomplete.min.js" type="text/javascript"></script>

		<script type="text/javascript">
		//<![CDATA[
		$j=jQuery.noConflict();


		$j(document).ready(function() {
			var gmap_enabled = <?php echo get_option('dbem_gmap_is_active'); ?>; 
		 
			$j("input#location-name").autocomplete("../wp-content/plugins/events-manager/locations-search.php", {
				width: 260,
				selectFirst: false,
				formatItem: function(row) {
					item = eval("(" + row + ")");
					return item.name+'<br/><small>'+item.address+' - '+item.town+ '</small>';
				},
				formatResult: function(row) {
					item = eval("(" + row + ")");
					return item.name;
				} 

			});
			$j('input#location-name').result(function(event,data,formatted) {       
				item = eval("(" + data + ")"); 
				$j('input#location-address').val(item.address);
				$j('input#location-town').val(item.town);
				if(gmap_enabled) {   
					eventLocation = $j("input#location-name").val(); 
			    eventTown = $j("input#location-town").val(); 
					eventAddress = $j("input#location-address").val();
					
					loadMap(eventLocation, eventTown, eventAddress)
				} 
			});

		});	
		//]]> 

		</script>

		<?php

	}
}


function dbem_cache_location($event){
	$related_location = dbem_get_location_by_name($event['location_name']);  
	if (!$related_location) {
		dbem_insert_location_from_event($event);
		return;
	} 
	if ($related_location->location_address != $event['location_address'] || $related_location->location_town != $event['location_town']  ) {
		dbem_insert_location_from_event($event);
	}      

}     

function dbem_get_location_by_name($name) {
	global $wpdb;	
	$sql = "SELECT location_id, 
	location_name, 
	location_address,
	location_town
	FROM ".$wpdb->prefix.LOCATIONS_TBNAME.  
	" WHERE location_name = '$name'";   
	$event = $wpdb->get_row($sql);	

	return $event;
}   

function dbem_insert_location_from_event($event) {
	global $wpdb;	
	$table_name = $wpdb->prefix.LOCATIONS_TBNAME;
	$wpdb->query("INSERT INTO ".$table_name." (location_name, location_address, location_town)
	VALUES ('".$event['location_name']."', '".$event['location_address']."','".$event['location_town']."')");

}