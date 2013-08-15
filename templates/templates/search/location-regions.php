<!-- START Region Search -->
<div class="em-search-region em-search-field">
	<label><?php echo esc_html(get_option('dbem_search_form_region_label')); ?></label>
	<select name="region" class="em-search-region em-events-search-region">
		<option value=''><?php echo esc_html(get_option('dbem_search_form_regions_label')); ?></option>
		<?php 
		if( !empty($_REQUEST['country']) ){
			//get the counties from locations table
			global $wpdb;
			$em_states = $wpdb->get_results($wpdb->prepare("SELECT DISTINCT location_region FROM ".EM_LOCATIONS_TABLE." WHERE location_region IS NOT NULL AND location_region != '' AND location_country=%s AND location_status=1 ORDER BY location_region", $_REQUEST['country']), ARRAY_N);
			foreach($em_states as $state){
				?>
				 <option<?php echo (!empty($_REQUEST['region']) && $_REQUEST['region'] == $state[0]) ? ' selected="selected"':''; ?>><?php echo esc_html($state[0]); ?></option>
				<?php 
			}
		}
		?>
	</select>
</div>	
<!-- END Region Search -->