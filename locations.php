<?php
/*
 * Currently these are template tags that refer to the main objects.
 * Please replace calls to these with direct object methods when possible. 
 * These will be phased out unless they are specifically template tags for public use.
 */

function dbem_get_locations($eventful = false) { 
	return EM_Locations::get($eventful, false);
}

function dbem_get_location($location_id) {
	$EM_Location = new EM_Location($location_id);
	return $EM_Location->to_array();
}

/**
 * Find a location with same name, address and town as supplied array
 * @param $location
 * @return array
 */
function dbem_get_identical_location($location) {
	$EM_Location = new EM_Location($location);
	return $EM_Location->load_similar();
}

function dbem_validate_location($location) {
	$EM_Location = new EM_Location($location);
	if ( $EM_Location->validate() ){
		return "OK";
	}else{
		return '<strong>'.__('Ach, some problems here:', 'dbem').'</strong><br /><br />'."\n".implode('<br />', $EM_Location->errors);
	}
}

function dbem_update_location($location) {
	$EM_Location = new EM_Location($location);
	$EM_Location->update();
}   

function dbem_insert_location($location) { 
	$EM_Location = new EM_Location($location);
	$EM_Location->insert();
	return $EM_Location->to_array();
}

function dbem_delete_location($location) {
	$EM_Location = new EM_Location($location);
	$EM_Location->delete();
}          

function dbem_location_has_events($location_id) {
	$EM_Location = new EM_Location($location_id);
	return $EM_Location->has_events();
}           

function dbem_upload_location_picture($location) {
	$EM_Location = new EM_Location($location);
	$EM_Location->image_upload();
}    

function dbem_delete_image_files_for_location_id($location_id) {
	$EM_Location = new EM_Location($location_id);
	$EM_Location->image_delete();
}   

function dbem_replace_locations_placeholders($format, $location, $target="html") {
	$EM_Location = new EM_Location($location);
	return $EM_Location->output($format, $target);
}  

/*
Deleted these functions due to not being used (and unecessary):
function dbem_cache_location($event){}  
function dbem_get_location_by_name($name) {}   
function dbem_insert_location_from_event($event) {}
*/