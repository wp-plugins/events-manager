<?php 
/* 
 * By modifying this in your theme folder within plugins/events-manager/templates/events-search.php, you can change the way the search form will look.
 * To ensure compatability, it is recommended you maintain class, id and form name attributes, unless you now what you're doing. 
 * You also must keep the _wpnonce hidden field in this form too.
 */
?>
<div class="em-events-search">
	<?php 
	$s_default = __('Search', 'dbem'); 
	$s = !empty($_REQUEST['search']) ? $_REQUEST['search']:$s_default;
	if( empty($_REQUEST['country']) && empty($_REQUEST['page']) ){
		$country = get_option('dbem_location_default_country');
	}elseif( empty($_REQUEST['country']) ){
		$country = $_REQUEST['country'];
	}
	//convert scope to an array in event of pagination
	if(!empty($_REQUEST['scope']) && !is_array($_REQUEST['scope'])){ $_REQUEST['scope'] = explode(',',$_REQUEST['scope']); }
	//get the events page to display search results
	?>
	<form action="<? echo EM_URI; ?>" method="post" class="em-events-search-form">
		<?php do_action('em_template_events_search_form_header'); ?>
		<!-- START General Search -->
		<?php /* This general search will find matches within event_name, event_notes, and the location_name, address, town, state and country. */ ?>
		<input type="text" name="search" class="em-events-search-text" value="<?php echo $s; ?>" onfocus="if(this.value=='<?php echo $s_default; ?>')this.value=''" onblur="if(this.value=='')this.value='<?php echo $s_default; ?>'" />
		<!-- END General Search -->
		<!-- START Date Search -->
		<span class="em-events-search-dates">
			<?php _e('between','dbem'); ?>:
			<input type="text" id="em-date-start-loc" />
			<input type="hidden" id="em-date-start" name="scope[0]" value="<?php if( !empty($_REQUEST['scope'][0]) ) echo $_REQUEST['scope'][0]; ?>" />
			<?php _e('and','dbem'); ?>
			<input type="text" id="em-date-end-loc" />
			<input type="hidden" id="em-date-end" name="scope[1]" value="<?php if( !empty($_REQUEST['scope'][1]) ) echo $_REQUEST['scope'][1]; ?>" />
		</span>
		<!-- END Date Search -->		
		<!-- START Category Search -->
		<select name="category" class="em-events-search-category">
			<option value=''><?php _e('All Categories','dbem'); ?></option>
			<?php foreach(EM_Categories::get(array('orderby'=>'category_name')) as $EM_Category): ?>
			 <option value="<?php echo $EM_Category->id; ?>" <?php echo (!empty($_REQUEST['category']) && $_REQUEST['category'] == $EM_Category->id) ? 'selected="selected"':''; ?>><?php echo $EM_Category->name; ?></option>
			<?php endforeach; ?>
		</select>
		<!-- END Category Search -->
		<!-- START Country Search -->
		<select name="country" class="em-events-search-country">
			<option value=''><?php _e('All Countries','dbem'); ?></option>
			<?php 
			//get the counties from locations table
			global $wpdb;
			$countries = em_get_countries();
			$em_countries = $wpdb->get_results("SELECT DISTINCT location_country FROM ".EM_LOCATIONS_TABLE." WHERE location_country IS NOT NULL AND location_country != '' ORDER BY location_country ASC", ARRAY_N);
			foreach($em_countries as $em_country): 
			?>
			 <option value="<?php echo $em_country[0]; ?>" <?php echo (!empty($country) && $country == $em_country[0]) ? 'selected="selected"':''; ?>><?php echo $countries[$em_country[0]]; ?></option>
			<?php endforeach; ?>
		</select>
		<!-- END Country Search -->	
		<!-- START Region Search -->
		<select name="region" class="em-events-search-region">
			<option value=''><?php _e('All Regions','dbem'); ?></option>
			<?php 
			if( !empty($country) ){
				//get the counties from locations table
				global $wpdb;
				$em_states = $wpdb->get_results($wpdb->prepare("SELECT DISTINCT location_region FROM ".EM_LOCATIONS_TABLE." WHERE location_region IS NOT NULL AND location_region != '' AND location_country=%s", $country), ARRAY_N);
				foreach($em_states as $state){
					?>
					 <option <?php echo ($_REQUEST['region'] == $state[0]) ? 'selected="selected"':''; ?>><?php echo $state[0]; ?></option>
					<?php 
				}
			}
			?>
		</select>	
		<!-- END Region Search -->	
		<!-- START State/County Search -->
		<select name="state" class="em-events-search-state">
			<option value=''><?php _e('All States','dbem'); ?></option>
			<?php 
			if( !empty($country) ){
				//get the counties from locations table
				global $wpdb;
				$em_states = $wpdb->get_results($wpdb->prepare("SELECT DISTINCT location_state FROM ".EM_LOCATIONS_TABLE." WHERE location_state IS NOT NULL AND location_state != '' AND location_country=%s", $country), ARRAY_N);
				foreach($em_states as $state){
					?>
					 <option <?php echo ($_REQUEST['state'] == $state[0]) ? 'selected="selected"':''; ?>><?php echo $state[0]; ?></option>
					<?php 
				}
			}
			?>
		</select>
		<!-- END State/County Search -->
		<?php do_action('em_template_events_search_form_ddm'); //depreciated, don't hook, use the one below ?>
		<?php do_action('em_template_events_search_form_footer'); ?>
		<input type="hidden" name="action" value="search_events" />
		<input type="submit" value="<?php echo $s_default; ?>" class="em-events-search-submit" />		
	</form>
</div>