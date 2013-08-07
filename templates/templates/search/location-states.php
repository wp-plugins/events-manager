<!-- START State/County Search -->
<div class="em-search-state em-search-field">
	<label><?php echo esc_html(get_option('dbem_search_form_state_label')); ?></label>
	<select name="state" class="em-search-state em-events-search-state">
		<option value=''><?php echo esc_html(get_option('dbem_search_form_states_label')); ?></option>
		<?php 
		if( !empty($_REQUEST['country']) ){
			//get the counties from locations table
			global $wpdb;
			$cond = !empty($_REQUEST['region']) ? $wpdb->prepare(" AND location_region=%s ", $_REQUEST['region']):'';
			$em_states = $wpdb->get_results($wpdb->prepare("SELECT DISTINCT location_state FROM ".EM_LOCATIONS_TABLE." WHERE location_state IS NOT NULL AND location_state != '' AND location_country=%s $cond AND location_status=1 ORDER BY location_state", $_REQUEST['country']), ARRAY_N);
			foreach($em_states as $state){
				?>
				 <option<?php echo (!empty($_REQUEST['state']) && $_REQUEST['state'] == $state[0]) ? ' selected="selected"':''; ?>><?php echo esc_html($state[0]); ?></option>
				<?php 
			}
		}
		?>
	</select>
</div>
<!-- END State/County Search -->