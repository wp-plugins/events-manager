<!-- START General Search -->
<div class="em-search-geo em-search-field">
	<?php 
		/* This general search will find matches within event_name, event_notes, and the location_name, address, town, state and country. */
		$s = !empty($_REQUEST['geo']) ? esc_attr($_REQUEST['geo']):'';
	?>
	<input type="text" name="geo" class="em-search-geo" value="<?php echo $s; ?>"/>
	<input type="hidden" name="near" class="em-search-geo-coords" value="<?php if( !empty($_REQUEST['near']) ) echo esc_attr($_REQUEST['near']); ?>" />
	<div id="em-search-geo-attr" ></div>
</div>
<!-- WIP, will be moved into js file -->
<script type="text/javascript">
EM.geo_placeholder = '<?php echo esc_attr(get_option('dbem_search_form_geo_label', 'Near...')); ?>';
EM.geo_alert_guess = '<?php esc_attr_e('We are going to use %s for searching.','dbem'); ?> \n\n <?php esc_attr_e('If this is incorrect, click cancel and try a more specific address.','dbem') ?>';
<?php
//include seperately, which allows you to just modify the html or completely override the JS
em_locate_template('templates/search/geo.js',true);
?>
</script>
<!-- END General Search -->