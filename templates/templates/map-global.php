<?php 
/*
 * This file contains the HTML generated for maps. You can copy this file to yourthemefolder/plugins/events/templates and modify it in an upgrade-safe manner.
 * 
 * There is one argument passed to you, which is the $args variable. This contains the arguments you could pass into shortcodes, template tags or functions like EM_Events::get().
 * 
 * In this template, we encode the $args array into JSON for javascript to easily parse and request the locations from the server via AJAX.
 */

if (get_option('dbem_gmap_is_active') == '1') {
	$args['em_ajax'] = true;
	$args['query'] = 'GlobalMapData';
    //get dimensions with px or % added in
	$width = (!empty($args['width'])) ? $args['width']:get_option('dbem_map_default_width','400px');
	$width = preg_match('/(px)|%/', $width) ? $width:$width.'px';
	$height = (!empty($args['height'])) ? $args['height']:get_option('dbem_map_default_height','300px');
	$height = preg_match('/(px)|%/', $height) ? $height:$height.'px';
	//assign random number for element id reference
	$rand = substr(md5(rand().rand()),0,5);
	?>
	<div class="em-location-map-container"  style='position:relative; background: #CDCDCD; width: <?php echo $width ?>; height: <?php echo $height ?>;'>
		<div class='em-locations-map' id='em-locations-map-<?php echo $rand; ?>' style="width:100%; height:100%"><em><?php _e('Loading Map....', 'dbem'); ?></em></div>
	</div>
	<div class='em-locations-map-coords' id='em-locations-map-coords-<?php echo $rand; ?>' style="display:none; visibility:hidden;"><?php echo EM_Object::json_encode($args); ?></div>
	<?php
}
?>