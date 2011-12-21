<?php
global $EM_Location, $EM_Notices;
//check that user can access this page
if( is_object($EM_Location) && !$EM_Location->can_manage('edit_locations','edit_others_locations') ){
	?>
	<div class="wrap"><h2><?php _e('Unauthorized Access','dbem'); ?></h2><p><?php echo sprintf(__('You do not have the rights to manage this %s.','dbem'),__('location','dbem')); ?></p></div>
	<?php
	return false;
}
$required = "<i>(".__('required','dbem').")</i>";
echo $EM_Notices;
?>
<form enctype='multipart/form-data' id='location-form' method='post' action=''>
	<input type='hidden' name='action' value='location_save' />
	<input type='hidden' name='_wpnonce' value='<?php echo wp_create_nonce('location_save'); ?>' />
	<input type='hidden' name='location_id' value='<?php echo $EM_Location->location_id ?>'/>
 	
	<?php global $EM_Notices; echo $EM_Notices; ?>
	<?php do_action('em_front_location_form_header'); ?>
	<h4>
		<?php _e ( 'Location Name', 'dbem' ); ?>
	</h4>
	<div class="inside">
		<input name='location_name' id='location-name' type='text' value='<?php echo htmlspecialchars($EM_Location->location_name, ENT_QUOTES); ?>' size='40'  />
		<br />
		<?php _e('The name of the location', 'dbem') ?>
	</div>

	<h4>
		<?php _e ( 'Location', 'dbem' ); ?>
	</h4>
	<div class="inside">
		<?php em_locate_template('forms/location/where.php','dbem'); ?>
	</div>

	<h4>
		<?php _e ( 'Details', 'dbem' ); ?>
	</h4>
	<div class="inside">
		<textarea name="content"><?php echo $EM_Location->post_content; ?></textarea>
		<?php _e ( 'Details about the location', 'dbem' )?>
	</div>
	
	<h4>
		<?php _e ( 'Location image', 'dbem' ); ?>
	</h4>
	<div class="inside" style="padding:10px;">
			<?php if ($EM_Location->get_image_url() != '') : ?> 
				<img src='<?php echo $EM_Location->get_image_url('medium'); ?>' alt='<?php echo $EM_Location->location_name ?>'/>
			<?php else : ?> 
				<?php _e('No image uploaded for this location yet', 'dbem') ?>
			<?php endif; ?>
			<br /><br />
			<label for='location_image'><?php _e('Upload/change picture', 'dbem') ?></label> <input id='location-image' name='location_image' id='location_image' type='file' size='40' />
			<br />
			<label for='location_image_delete'><?php _e('Delete Image?', 'dbem') ?></label> <input id='location-image-delete' name='location_image_delete' id='location_image_delete' type='checkbox' value='1' />
	</div>
	<?php do_action('em_front_location_form_footer'); ?>
	<p class='submit'><input type='submit' class='button-primary' name='submit' value='<?php _e('Update location', 'dbem') ?>' /></p>
</form>