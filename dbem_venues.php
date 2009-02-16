<?php  
$feedback_message = "";
 
$venue_required_fields = array("venue_name" => __('The venue name', 'dbem'), "venue_address" => __('The venue address', 'dbem'), "venue_town" => __('The venue town', 'dbem'));


add_action('init', 'dbem_intercept_venues_actions');
function dbem_intercept_venues_actions() {
	if(isset($_GET['page']) && $_GET['page'] == "venues") {
  	if(isset($_GET['doaction2']) && $_GET['doaction2'] == "Delete") {
	  	if(isset($_GET['action2']) && $_GET['action2'] == "delete") {
				$venues = $_GET['venues'];
				foreach($venues as $venue_ID) {
				 	dbem_delete_venue($venue_ID);
				}
			}
		}
	}
}

function dbem_venues_page() {      
	
	if(isset($_GET['action']) && $_GET['action'] == "edit") { 
		// edit venue  
		$venue_id = $_GET['venue_ID'];
		$venue = dbem_get_venue($venue_id);
		dbem_venues_edit_layout($venue);
  } else { 
    if(isset($_POST['action']) && $_POST['action'] == "editedvenue") { 
			// venue update required  
			$venue = array();
			$venue['venue_id'] = $_POST['venue_ID'];
			$venue['venue_name'] = $_POST['venue_name'];
			$venue['venue_address'] = $_POST['venue_address']; 
			$venue['venue_town'] = $_POST['venue_town']; 
			$venue['venue_latitude'] = $_POST['venue_latitude'];
			$venue['venue_longitude'] = $_POST['venue_longitude'];
			$venue['venue_description'] = $_POST['venue_description'];
			//$venue['venue_description'] = $_POST['venue_description'];
			$validation_result = dbem_validate_venue($venue);
			if ($validation_result == "OK") {   
				dbem_update_venue($venue);    
			  if ($_FILES['venue_image']['size'] > 0 )
					dbem_upload_venue_picture($venue);
				$message = __('The venue has been updated.', 'dbem'); 
				$venues = dbem_get_venues();
				dbem_venues_table_layout($venues, $message);
			} else {
				$message = $validation_result;   
				dbem_venues_edit_layout($venue, $message);
			}
		} elseif(isset($_POST['action']) && $_POST['action'] == "addvenue") {    
				$venue = array();
				$venue['venue_name'] = $_POST['venue_name'];
				$venue['venue_address'] = $_POST['venue_address'];
				$venue['venue_town'] = $_POST['venue_town']; 
				$venue['venue_latitude'] = $_POST['venue_latitude'];
				$venue['venue_longitude'] = $_POST['venue_longitude'];
				$venue['venue_description'] = $_POST['venue_description'];
				$validation_result = dbem_validate_venue($venue);
				if ($validation_result == "OK") {   
					$new_venue = dbem_insert_venue($venue);   
		 			// uploading the image
				 
					if ($_FILES['venue_image']['size'] > 0 ) {
						dbem_upload_venue_picture($new_venue);
			    }
					
					 
					
					
					
					
					// -------------
					
					//RESETME $message = __('The venue has been added.', 'dbem'); 
					$venues = dbem_get_venues();
					dbem_venues_table_layout($venues, null,$message);
				} else {
					$message = $validation_result;
					$venues = dbem_get_venues();
					   
					dbem_venues_table_layout($venues, $venue, $message);
				}
				
				
				
			} else {  
			// no action, just a venues list
			$venues = dbem_get_venues();
			dbem_venues_table_layout($venues, $message);
  	}
	} 
}  

function dbem_venues_edit_layout($venue, $message = "") {
	$layout = "
	<div class='wrap'>
		<div id='icon-edit' class='icon32'>
			<br/>
		</div>
			
		<h2>".__('Edit venue', 'dbem')."</h2>";   
 		
		if($message != "") {
			$layout .= "
		<div id='message' class='updated fade below-h2' style='background-color: rgb(255, 251, 204);'>
			<p>$message</p>
		</div>";
		}
		$layout .= "
		<div id='ajax-response'></div>

		<form enctype='multipart/form-data' name='editcat' id='editcat' method='post' action='admin.php?page=venues' class='validate'>
		<input type='hidden' name='action' value='editedvenue' />
		<input type='hidden' name='venue_ID' value='".$venue['venue_id']."'/>";
		
		$layout .= "<table class='form-table'>
				<tr class='form-field form-required'>
					<th scope='row' valign='top'><label for='venue_name'>".__('Venue name', 'dbem')."</label></th>
					<td><input name='venue_name' id='venue-name' type='text' value='".$venue['venue_name']."' size='40'  /><br />
		           ".__('The name of the venue', 'dbem')."</td>
				</tr>

				<tr class='form-field'>
					<th scope='row' valign='top'><label for='venue_address'>".__('Venue address', 'dbem')."</label></th>
					<td><input name='venue_address' id='venue-address' type='text' value='".$venue['venue_address']."' size='40' /><br />
		            ".__('The address of the venue', 'dbem').".</td>

				</tr>
				
				<tr class='form-field'>
					<th scope='row' valign='top'> <label for='venue_town'>".__('Venue town', 'dbem')."</label></th>
					<td><input name='venue_town' id='venue-town' type='text' value='".$venue['venue_town']."' size='40' /><br />
		            ".__('The town where the venue is located', 'dbem').".</td>

				</tr>";
				$gmap_is_active = get_option('dbem_gmap_is_active');
				if ($gmap_is_active) {  
			  	$layout .= " 
			    
				 <tr>
				  <td>Coordinates</td>
					<td><input id='venue-latitude' name='venue_latitude' id='venue_latitude' type='text' value='".$venue['venue_latitude']."' size='15'  />
					<input id='venue-longitude' name='venue_longitude' id='venue_longitude' type='text' value='".$venue['venue_longitude']."' size='15'  /></td>
				 </tr>\n
			
				<tr>
			 <th scope='row' valign='top'><label for='venue_map'>".__('Venue map', 'dbem')."</label></th>
										<td>
										<div id='map-not-found' style='width: 450px; font-size: 140%; text-align: center; margin-top: 100px; display: hide'><p>".__('Map not found')."</p></div>
						 <div id='event-map' style='width: 450px; height: 300px; background: green; display: hide; margin-right:8px'></div></td></tr>";   
						}
			$layout .= "
				<tr class='form-field'>
					<th scope='row' valign='top'><label for='venue_description'>".__('Venue description', 'dbem')."</label></th>
					<td><textarea name='venue_description' id='venue_description' rows='5' cols='50' style='width: 97%;'>".$venue['venue_description']."</textarea><br />
		            ".__('A description of the Venue. You may include any kind of info here.', 'dbem')."</td>

				</tr>
				<tr class='form-field'>
					<th scope='row' valign='top'><label for='venue_picture'>".__('Venue picture', 'dbem')."</label></th>
					<td>";
					if ($venue['venue_image_url'] != '') 
						$layout .= "<img src='".$venue['venue_image_url']."' alt='".$venue['venue_name']."'/>";
					else 
						$layout .= __('No image uploaded for this venue yet', 'debm');
					
					$layout .= "</td>

				</tr>
				<tr>
					<th scope='row' valign='top'><label for='venue_image'>".__('Upload/change picture', 'dbem')."</label></th>
					<td><input id='venue-image' name='venue_image' id='venue_image' type='file' size='40' /></td>
			 </tr>\n
			</table>
		<p class='submit'><input type='submit' class='button-primary' name='submit' value='".__('Update venue', 'dbem')."' /></p>
		</form>
		   
   	
	</div>
			
	";  
	echo $layout;
}

function dbem_venues_table_layout($venues, $new_venue, $message = "") {
	$destination = get_bloginfo('url')."/wp-admin/admin.php"; 
	$table = "
		<div class='wrap nosubsub'>\n
			<div id='icon-edit' class='icon32'>
				<br/>
			</div>
 	 		<h2>".__('Venues', 'dbem')."</h2>\n ";   
	 		
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
						<input type='hidden' name='page' value='venues'/>\n
						<input type='hidden' name='action' value='edit_venue'/>\n
						<input type='hidden' name='event_id' value='$event_id'/>\n
						<table class='widefat'>\n
							<thead>\n
								<tr>\n
									<th class='manage-column column-cb check-column' scope='col'>&nbsp;</th>\n
									<th>".__('Name', 'dbem')."</th>\n
									<th>".__('Address', 'dbem')."</th>\n
									<th>".__('Town', 'dbem')."</th>\n                
								</tr>\n 
							</thead>\n
							<tbody>\n";
						foreach ($venues as $this_venue) {
							$table .= "		
								<tr>\n
								<td><input type='checkbox' value='".$this_venue->venue_id."' name='venues[]'/></td>\n
								<td><a href='".get_bloginfo('url')."/wp-admin/admin.php?page=venues&action=edit&venue_ID=$this_venue->venue_id'> $this_venue->venue_name</a></td>\n
								<td>$this_venue->venue_address</td>\n
								<td>$this_venue->venue_town</td>\n                         
								</tr>\n";
						}
						$table .= "
							</tbody>\n
							<tfoot>\n
								<tr>\n
									<th class='manage-column column-cb check-column' scope='col'>&nbsp;</th>\n
									<th>".__('Name', 'dbem')."</th>\n
									<th>".__('Address', 'dbem')."</th>\n
									<th>".__('Town', 'dbem')."</th>\n      
								</tr>\n             
							</tfoot>\n
						</table>\n

						<div class='tablenav'>\n
							<div class=alignleft actions>\n
							 <input type='hidden' name='action2' value='delete'/>
						 	 <input class='button-secondary action' type='submit' name='doaction2' value='Delete'/>\n
								<br class='clear'/>\n 
							</div>\n
							<br class='clear'/>\n
							</div>\n
						</form>\n
					</div>\n
				</div>  <?-- end col-right -->\n     
				
				<div id='col-left'>\n
			  	<div class='col-wrap'>\n
						<div class='form-wrap'>\n 
							<div id='ajax-response'/>
					  	<h3>".__('Add venue', 'dbem')."</h3>\n
							 <form enctype='multipart/form-data' name='addvenue' id='addvenue' method='post' action='admin.php?page=venues' class='add:the-list: validate'>\n
							 		
									<input type='hidden' name='action' value='addvenue' />\n
							    <div class='form-field form-required'>\n
							      <label for='venue_name'>".__('Venue name', 'dbem')."</label>\n
								 	<input id='venue-name' name='venue_name' id='venue_name' type='text' value='".$new_venue['venue_name']."' size='40' />\n
								    <p>".__('The name of the venue', 'dbem').".</p>\n
								 </div>\n
               
								 <div class='form-field'>\n
								   <label for='venue_address'>".__('Venue address', 'dbem')."</label>\n
								 	<input id='venue-address' name='venue_address' id='venue_address' type='text' value='".$new_venue['venue_address']."' size='40'  />\n
								    <p>".__('The address of the venue', 'dbem').".</p>\n
								 </div>\n
               
								 <div class='form-field'>\n
								   <label for='venue_town'>".__('Venue town', 'dbem')."</label>\n
								 	<input id='venue-town' name='venue_town' id='venue_town' type='text' value='".$new_venue['venue_town']."' size='40'  />\n
								    <p>".__('The town where the venue is located', 'dbem').".</p>\n
								 </div>\n   
								
							     <div class='form-field'>\n
								   <label for='venue_latitude'>LAT</label>\n
								 	<input id='venue-latitude' name='venue_latitude' id='venue_latitude' type='text' value='".$new_venue['venue_latitude']."' size='40'  />\n
								 </div>\n
								 <div class='form-field'>\n
								   <label for='venue_longitude'>LONG</label>\n
								 	<input id='venue-longitude' name='venue_longitude' id='venue_longitude' type='text' value='".$new_venue['venue_longitude']."' size='40'  />\n
								 </div>\n
								
								 <div class='form-field'>\n
								   <label for='venue_image'>".__('Venue image', 'dbem')."</label>\n
								 	<input id='venue-image' name='venue_image' id='venue_image' type='file' size='35' />\n
								    <p>".__('Select an image to upload', 'dbem').".</p>\n
								 </div>\n";
									$gmap_is_active = get_option('dbem_gmap_is_active'); 
									
                 	if ($gmap_is_active) {
							 		 	$table .= "<div id='map-not-found' style='width: 450px; float: right; font-size: 140%; text-align: center; margin-top: 100px; display: hide'><p>".__('Map not found')."</p></div>";
								 		$table .= "<div id='event-map' style='width: 450px; height: 300px; background: green; float: right; display: hide; margin-right:8px'></div>";   
							}   
								$table .= "
								 	<div class='form-field'>\n
								 	<label for='venue_description'>".__('Venue description', 'dbem')."</label>\n
								 	<textarea name='venue_description' id='venue_description' rows='5' cols='40'>".$new_venue['venue_description']."</textarea>\n
								    <p>".__('A description of the Venue. You may include any kind of info here.', 'dbem')."</p>\n
								 </div>\n
               
								 <p class='submit'><input type='submit' class='button' name='submit' value='".__('Add venue', 'dbem')."' /></p>\n
							 </form>\n   

					  </div>\n
					</div>\n 
				</div>  <?-- end col-left -->\n   
			</div>\n 
  	</div>\n";                                                

	echo $table;  
}

	 

function dbem_get_venues($eventful = false) { 
	global $wpdb;
	$venues_table = $wpdb->prefix.VENUES_TBNAME; 
	$events_table = $wpdb->prefix.EVENTS_TBNAME;
	if ($eventful == 'true') {
		$sql = "SELECT * from $venues_table JOIN $events_table ON $venues_table.venue_id = $events_table.venue_id";
	} else {
		$sql = "SELECT venue_id, venue_address, venue_name, venue_town,venue_latitude, venue_longitude 
			FROM $venues_table ORDER BY venue_name";   
	}

	$venues = $wpdb->get_results($sql); 
	return $venues;  

}

function dbem_get_venue($venue_id) { 
	global $wpdb;
	$venues_table = $wpdb->prefix.VENUES_TBNAME; 
	$sql = "SELECT * FROM $venues_table WHERE venue_id ='$venue_id'";   
  $venue = $wpdb->get_row($sql, ARRAY_A);    
	$venue['venue_image_url'] = dbem_image_url_for_venue_id($venue['venue_id']);
	return $venue;  

}

function dbem_image_url_for_venue_id($venue_id) {
	$file_name= ABSPATH.IMAGE_UPLOAD_DIR."/venue-".$venue_id;
  $mime_types = array('gif','jpg','png');
	foreach($mime_types as $type) { 
		$file_path = "$file_name.$type";
		if (file_exists($file_path)) {
			$result = get_bloginfo('url')."/".IMAGE_UPLOAD_DIR."/venue-$venue_id.$type";
  		return $result;
		}
	}
	return '';
}

function dbem_get_identical_venue($venue) { 
	global $wpdb;
	
	$venues_table = $wpdb->prefix.VENUES_TBNAME; 
	//$sql = "SELECT * FROM $venues_table WHERE venue_name ='".$venue['venue_name']."' AND venue_address ='".$venue['venue_address']."' AND venue_town ='".$venue['venue_town']."';";   
  $prepared_sql=$wpdb->prepare("SELECT * FROM $venues_table WHERE venue_name = %s AND venue_address = %s AND venue_town = %s", $venue['venue_name'], $venue['venue_address'], $venue['venue_town'] );
	$cached_venue = $wpdb->get_row($prepared_sql, ARRAY_A); 
 
	return $cached_venue;  

}

function dbem_validate_venue($venue) {
	global $venue_required_fields;
	$troubles = "";
	foreach ($venue_required_fields as $field => $description) {
		if ($venue[$field] == "" ) {
		$troubles .= "<li>".$description.__(" is missing!", "dbem")."</li>";
		}       
	}
	if ($_FILES['venue_image']['size'] > 0 ) { 
		if (is_uploaded_file($_FILES['venue_image']['tmp_name'])) {
 	 		$mime_types = array(1 => 'gif', 2 => 'jpg', 3 => 'png');
			$maximum_size = get_option('dbem_image_max_size'); 
			if ($_FILES['venue_image']['size'] > $maximum_size) 
	     	$troubles = "<li>".__('The image file is too big! Maximum size:', 'dbem')." $maximum_size</li>";
	  	list($width, $height, $type, $attr) = getimagesize($_FILES['venue_image']['tmp_name']);
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

function dbem_update_venue($venue) {
	global $wpdb;
	$venues_table = $wpdb->prefix.VENUES_TBNAME;
	$sql="UPDATE ".$venues_table. 
	" SET venue_name='".$venue['venue_name']."', ".
		"venue_address='".$venue['venue_address']."',".
		"venue_town='".$venue['venue_town']."', ".
		"venue_latitude=".$venue['venue_latitude'].",". 
		"venue_longitude=".$venue['venue_longitude'].",".
		"venue_description='".$venue['venue_description']."' ". 
		"WHERE venue_id='".$venue['venue_id']."';";  
	$wpdb->query($sql);
}   

function dbem_insert_venue($venue) {
		global $wpdb;	
		$table_name = $wpdb->prefix.VENUES_TBNAME; 
		$sql = "INSERT INTO ".$table_name." (venue_name, venue_address, venue_town, venue_latitude, venue_longitude, venue_description)
		VALUES ('".$venue['venue_name']."','".$venue['venue_address']."','".$venue['venue_town']."','".$venue['venue_latitude']."','".$venue['venue_longitude']."','".$venue['venue_description']."')";
		$wpdb->query($sql);
    $new_venue = dbem_get_identical_venue($venue); 
		return $new_venue;
}

function dbem_delete_venue($venue) {
		global $wpdb;	
		$table_name = $wpdb->prefix.VENUES_TBNAME;
		$sql = "DELETE FROM $table_name WHERE venue_id = '$venue';";
		$wpdb->query($sql);
    dbem_delete_image_files_for_venue_id($venue);
}          

function dbem_venue_has_events($venue_id) {
	global $wpdb;	
	$events_table = $wpdb->prefix.EVENTS_TBNAME;
	$sql = "SELECT event_id FROM $events_table WHERE venue_id = $venue_id";   
 	$affected_events = $wpdb->get_results($sql);
	return (count($affected_events) > 0);
}             

function dbem_upload_venue_picture($venue) {
  dbem_delete_image_files_for_venue_id($venue['venue_id']);
	$mime_types = array(1 => 'gif', 2 => 'jpg', 3 => 'png');    
	list($width, $height, $type, $attr) = getimagesize($_FILES['venue_image']['tmp_name']);
	$image_path = "../".IMAGE_UPLOAD_DIR."/venue-".$venue['venue_id'].".".$mime_types[$type];
	if (!move_uploaded_file($_FILES['venue_image']['tmp_name'], $image_path)) 
		$msg = "<p>".__('The image could not be loaded','dbem')."</p>";
}    
function dbem_delete_image_files_for_venue_id($venue_id) {
	$file_name= "../".IMAGE_UPLOAD_DIR."/venue-".$venue_id;
	$mime_types = array(1 => 'gif', 2 => 'jpg', 3 => 'png');
	foreach($mime_types as $type) { 
		if (file_exists($file_name.".".$type))
  		unlink($file_name.".".$type);
	}
}          



function dbem_global_map($atts) {
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
	//-->
	</script>";
	$result .= "<script src='".get_bloginfo('url')."/wp-content/plugins/events-manager/dbem_global_map.js' type='text/javascript'></script>";
	$result .= "<ol id='dbem_venues_list'></ol>"; 
	return $result;
}
add_shortcode('venues_map', 'dbem_global_map'); 

function dbem_replace_venues_placeholders($format, $venue, $target="html") {
	$venue_string = $format;
	preg_match_all("/#@?_?[A-Za-z]+/", $format, $placeholders);
	foreach($placeholders[0] as $result) {    
		// echo "RESULT: $result <br>";
		// matches alla fields placeholder
		if (preg_match('/#_MAP/', $result)) {
		 	$map_div = dbem_single_venue_map($venue);
		 	$venue_string = str_replace($result, $map_div , $venue_string ); 
		 
		}
		if (preg_match('/#_PASTEVENTS/', $result)) {
		 	$list = dbem_events_in_venue_list($venue, "past");
		 	$venue_string = str_replace($result, $list , $venue_string ); 
		}
		if (preg_match('/#_NEXTEVENTS/', $result)) {
		 	$list = dbem_events_in_venue_list($venue);
		 	$venue_string = str_replace($result, $list , $venue_string ); 
		}
		if (preg_match('/#_ALLEVENTS/', $result)) {
		 	$list = dbem_events_in_venue_list($venue, "all");
		 	$venue_string = str_replace($result, $list , $venue_string ); 
		}
	  
		if (preg_match('/#_(NAME|ADDRESS|TOWN|PROVINCE|DESCRIPTION)/', $result)) {
			$field = "venue_".ltrim(strtolower($result), "#_");
		 	$field_value = $venue[$field];      
		
			if ($field == "venue_description") {
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
			$venue_string = str_replace($result, $field_value , $venue_string ); 
	 	}
	  
		if (preg_match('/#_(IMAGE)/', $result)) {
				
        	if($venue['venue_image_url'] != '')
				  $venue_image = "<img src='".$venue['venue_image_url']."' alt='".$venue['venue_name']."'/>";
				else
					$venue_image = "";
			$venue_string = str_replace($result, $venue_image , $venue_string ); 
		}
			
	}
	return $venue_string;	
	
}
function dbem_single_venue_map($venue) {
	$gmap_is_active = get_option('dbem_gmap_is_active'); 
	$map_text = dbem_replace_venues_placeholders(get_option('dbem_venue_baloon_format'), $venue);
	if ($gmap_is_active) {  
   		$gmaps_key = get_option('dbem_gmap_key');
   		$map_div = "<div id='dbem-venue-map' style=' background: green; width: 400px; height: 300px'></div>" ;
   		$map_div .= "<script type='text/javascript'>
  			<!--// 
  		latitude = parseFloat('".$venue['venue_latitude']."');
  		longitude = parseFloat('".$venue['venue_longitude']."');
  		GMapsKey = '$gmaps_key';
  		map_text = '$map_text';
		//-->
		</script>";
		$map_div .= "<script src='".get_bloginfo('url')."/wp-content/plugins/events-manager/dbem_single_venue_map.js' type='text/javascript'></script>";
	} else {
		$map_div = "";
	}
	return $map_div;
}

function dbem_events_in_venue_list($venue, $scope = "") {
	$events = dbem_get_events("",$scope,"","",$venue['venue_id']);
	$list = "";
	if (count($events) > 0) {
		foreach($events as $event)
			$list .= dbem_replace_placeholders(get_option('dbem_venue_event_list_item_format'), $event);
	} else {
		$list = get_option('dbem_venue_no_events_message');
	}
	return $list;
}
