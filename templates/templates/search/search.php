<?php $args = !empty($args) ? $args:array(); /* @var $args array */ ?>
<!-- START General Search -->
<div class="em-search-text em-search-field">
	<?php 
		/* This general search will find matches within event_name, event_notes, and the location_name, address, town, state and country. */
		$s_default = esc_attr($args['search_term_label']);
		$s = !empty($args['search']) ? esc_attr($args['search']):$s_default;
	?>
	<input type="text" name="em_search" class="em-events-search-text em-search-text" value="<?php echo $s; ?>" onfocus="if(this.value=='<?php echo $s_default; ?>')this.value=''" onblur="if(this.value=='')this.value='<?php echo $s_default; ?>'" />
</div>
<!-- END General Search -->