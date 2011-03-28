<?php
	global $EM_Event; 
	if(get_option('dbem_categories_enabled')) :?>
	<?php $categories = EM_Categories::get(array('orderby'=>'category_name')); ?>
	<?php if( count($categories) > 0 ): ?>
		<!-- START Categories -->
		<div class="inside">
			<label for="event_category_id"><?php _e ( 'Category:', 'dbem' ); ?></label>
			<select name="event_category_id">
				<option value="" <?php echo ($EM_Event->category_id == '') ? "selected='selected'":'' ?>><?php _e('no category','dbem') ?></option>	
				<?php
				foreach ( $categories as $EM_Category ){
					$selected = ($EM_Category->id == $EM_Event->category_id) ? "selected='selected'": ''; 
					?>
					<option value="<?php echo $EM_Category->id ?>" <?php echo $selected ?>>
					<?php echo $EM_Category->name ?>
					</option>
					<?php 
				}
				?>
			</select>
		</div>
		<!-- END Categories -->
	<?php endif; ?>
<?php endif; ?>