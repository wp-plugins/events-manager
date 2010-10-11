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
			?>
			<div id='em-locations-map' style='width:<?php echo $atts['width']; ?>px; height:<?php echo $atts['height']; ?>px'><em><?php _e('Loading Map....', 'dbem'); ?></em></div>
			<script src='<?php echo bloginfo('wpurl') ?>/wp-content/plugins/events-manager/includes/js/em_maps.js' type='text/javascript'></script>
			<script type='text/javascript'>
			<!--// 
				var eventful = <?php echo ($atts['eventful']) ? 'true':'false'; ?>; 
				var scope = '<?php echo $atts['scope']; ?>';
				em_load_map('em_map_global');
			//-->
			</script> 
			<?php
			return ob_get_clean();
		}
		return '';
	}
	
	
	/**
	 * Returns th HTML and JS required to produce a google map in for this location.
	 * @param EM_Location $location
	 * @return string
	 */
	function get_single($args) {
		//TODO do some validation here of defaults
		$location = $args['location'];
		if ( get_option('dbem_gmap_is_active') && ( is_object($location) && $location->latitude != 0 && $location->longitude != 0 ) ) {
			$width = (isset($args['width'])) ? $args['width']:'400';
			$height = (isset($args['height'])) ? $args['height']:'300';
			ob_start();
			?>
	   		<div id='em-location-map' style='background: #CDCDCD; width: <?php echo $width ?>px; height: <?php echo $height ?>px'><?php _e('Loading Map....', 'dbem'); ?></div>
   		<div id='em-location-map-info' style="display:none; visibility:hidden;"><div id="em-map-balloon" style="font-size:12px;"><div id="content"><?php echo $location->output(get_option('dbem_location_baloon_format')); ?></div></div></div>
			<script src='<?php bloginfo('wpurl'); ?>/wp-content/plugins/events-manager/includes/js/em_maps.js' type='text/javascript'></script>
	   		<script type='text/javascript'>
	  			<!--// 
			  		var latitude = parseFloat('<?php echo $location->latitude; ?>');
			  		var longitude = parseFloat('<?php echo $location->longitude; ?>');
			  		em_load_map('em_map_single');
				//-->
			</script>			
			<?php
			return ob_get_clean();
		}
		return '<i>'. __('Map Unavailable', 'dbem') .'</i>';
	}	
}