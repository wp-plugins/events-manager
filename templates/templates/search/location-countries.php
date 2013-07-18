<!-- START Country Search -->
<div class="em-search-country em-search-field">
	<label><?php echo esc_html(get_option('dbem_search_form_country_label')); ?></label>
	<select name="country" class="em-search-country em-events-search-country">
		<option value=''><?php echo esc_html(get_option('dbem_search_form_countries_label')); ?></option>
		<?php 
		//get the counties from locations table
		global $wpdb;
		$countries = em_get_countries();
		$em_countries = $wpdb->get_results("SELECT DISTINCT location_country FROM ".EM_LOCATIONS_TABLE." WHERE location_country IS NOT NULL AND location_country != '' AND location_status=1 ORDER BY location_country ASC", ARRAY_N);
		$ddm_countries = array();
		foreach($em_countries as $em_country){
			$ddm_countries[$em_country[0]] = $countries[$em_country[0]];
		}
		asort($ddm_countries);
		foreach( $ddm_countries as $country_code => $country_name ):
		//we're not using esc_ functions here because values are hard-coded within em_get_countries() 
		?>
		<option value="<?php echo $country_code; ?>" <?php echo (!empty($_REQUEST['country']) && $_REQUEST['country'] == $country_code) ? 'selected="selected"':''; ?>><?php echo $country_name; ?></option>
		<?php endforeach; ?>
	</select>
</div>
<!-- END Country Search -->