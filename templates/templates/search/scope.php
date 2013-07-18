<!-- START Date Search -->
<?php
	//convert scope to an array in event of pagination
	if(!empty($_REQUEST['scope']) && !is_array($_REQUEST['scope'])){ $_REQUEST['scope'] = explode(',',$_REQUEST['scope']); }
?>
<div class="em-search-scope em-search-field">
	<span class="em-search-scope em-events-search-dates em-date-range">
		<label><?php echo esc_html(get_option('dbem_search_form_dates_label')); ?></label>
		<input type="text" class="em-date-input-loc em-date-start" />
		<input type="hidden" class="em-date-input" name="scope[0]" value="<?php if( !empty($_REQUEST['scope'][0]) ) echo esc_attr($_REQUEST['scope'][0]); ?>" />
		<?php echo esc_html(get_option('dbem_search_form_dates_separator')); ?>
		<input type="text" class="em-date-input-loc em-date-end" />
		<input type="hidden" class="em-date-input" name="scope[1]" value="<?php if( !empty($_REQUEST['scope'][1]) ) echo esc_attr($_REQUEST['scope'][1]); ?>" />
	</span>
</div>
<!-- END Date Search -->