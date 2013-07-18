<!-- START City Search -->
<div class="em-search-town em-search-field">
	<label><?php echo esc_html(get_option('dbem_search_form_town_label')); ?></label>
	<select name="town" class="em-search-town em-events-search-town">
		<option value=''><?php echo esc_html(get_option('dbem_search_form_towns_label')); ?></option>
		<?php 
		if( !empty($_REQUEST['country']) ){
			//get the counties from locations table
			global $wpdb;
			$cond = !empty($_REQUEST['region']) ? $wpdb->prepare(" AND location_region=%s ", $_REQUEST['region']):'';
			$cond .= !empty($_REQUEST['state']) ? $wpdb->prepare(" AND location_state=%s ", $_REQUEST['state']):'';
			$em_towns = $wpdb->get_results($wpdb->prepare("SELECT DISTINCT location_town FROM ".EM_LOCATIONS_TABLE." WHERE location_town IS NOT NULL AND location_town != '' AND location_country=%s $cond AND location_status=1 ORDER BY location_town", $_REQUEST['country']), ARRAY_N);
			foreach($em_towns as $town){
				?>
				 <option <?php echo (!empty($_REQUEST['town']) && $_REQUEST['town'] == $town[0]) ? 'selected="selected"':''; ?>><?php echo esc_html($town[0]); ?></option>
				<?php 
			}
		}
		?>
	</select>
</div>
<!-- END City Search -->