<?php
/**
 * Obtains the html required to display a google map for given location(s)
 *
 */
class EM_Map extends EM_Object {
	/**
	 * Shortcode for producing a google map with all the locations. Unfinished and undocumented.
	 * @param array $atts
	 * @return string
	 */
	function get_global($atts) { 
		//TODO Finish and document this feature, need to add balloons here
		if (get_option('dbem_gmap_is_active') == '1') {
			ob_start();
			$atts['em_ajax'] = true;
			$atts['query'] = 'GlobalMapData';
			$rand = substr(md5(rand().rand()),0,5);
			//build js array of arguments to send to event query
			?>
			<div class='em-locations-map' id='em-locations-map-<?php echo $rand; ?>' style='width:<?php echo $atts['width']; ?>px; height:<?php echo $atts['height']; ?>px'><em><?php _e('Loading Map....', 'dbem'); ?></em></div>
			<div class='em-locations-map-coords' id='em-locations-map-coords-<?php echo $rand; ?>' style="display:none; visibility:hidden;"><?php echo EM_Object::json_encode($atts); ?></div>
			<?php
			return apply_filters('em_map_get_global', ob_get_clean());
		}else{
			return '';	
		}
	}
	
	
	/**
	 * Returns th HTML and JS required to produce a google map in for this location.
	 * @param EM_Location $location
	 * @return string
	 */
	function get_single($args) {
		//TODO do some validation here of defaults
		//FIXME change baloon to balloon for consistent spelling
		$location = $args['location'];
		if ( get_option('dbem_gmap_is_active') && ( is_object($location) && $location->latitude != 0 && $location->longitude != 0 ) ) {
			$width = (isset($args['width'])) ? $args['width']:'400';
			$height = (isset($args['height'])) ? $args['height']:'300';
			ob_start();
			$rand = substr(md5(rand().rand()),0,5);
			?>
	   		<div class='em-location-map' id='em-location-map-<?php echo $rand ?>' style='background: #CDCDCD; width: <?php echo $width ?>px; height: <?php echo $height ?>px'><?php _e('Loading Map....', 'dbem'); ?></div>
   			<div class='em-location-map-info' id='em-location-map-info-<?php echo $rand ?>' style="display:none; visibility:hidden;"><div class="em-map-balloon" style="font-size:12px;"><div class="em-map-balloon-content" ><?php echo $location->output(get_option('dbem_location_baloon_format')); ?></div></div></div>
			<div class='em-location-map-coords' id='em-location-map-coords-<?php echo $rand ?>' style="display:none; visibility:hidden;">
				<span class="lat"><?php echo $location->latitude; ?></span>
				<span class="lng"><?php echo $location->longitude; ?></span>
			</div>			
			<?php
			return ob_get_clean();
		}elseif( is_object($location) && $location->latitude == 0 && $location->longitude == 0 ){
			return '<i>'. __('Map Unavailable', 'dbem') .'</i>';
		}else{
			return '';
		}
	}	
}