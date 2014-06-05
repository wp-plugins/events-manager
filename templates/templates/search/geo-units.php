<?php $args = !empty($args) ? $args:array(); /* @var $args array */ ?>
<!-- START Geo Units Search -->
<div class="em-search-geo-units em-search-field" <?php if( empty($args['geo']) || empty($args['near']) ): ?>style="display:none;"<?php endif; /* show location fields if no geo search is made */ ?>>
	<label><?php echo esc_html($args['geo_units_label']); ?></label>
	<select name="near_distance" class="em-search-geo-distance">
		<option value="5">5</option>
		<option value="10" <?php if($args['near_distance'] == 10) echo 'selected="selected"' ?>>10</option>
		<option value="25" <?php if($args['near_distance'] == 25) echo 'selected="selected"' ?>>25</option>
		<option value="50" <?php if($args['near_distance'] == 50) echo 'selected="selected"' ?>>50</option>
		<option value="100" <?php if($args['near_distance'] == 100) echo 'selected="selected"' ?>>100</option>
	</select>
	<select name="near_unit" class="em-search-geo-unit">
		<option value="mi">mi</option>
		<option value="km" <?php if($args['near_unit'] == 'km') echo 'selected="selected"' ?>>km</option>
	</select>
</div>
<!-- END Geo Units Search -->