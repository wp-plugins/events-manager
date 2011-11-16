<?php 
/* 
 * By modifying this in your theme folder within plugins/events-manager/templates/event-form.php, you can change the way the search form will look.
 * To ensure compatability, it is recommended you maintain class, id and form name attributes, unless you now what you're doing. 
 * You also must keep the _wpnonce hidden field in this form too.
 */
	global $EM_Event, $current_user, $localised_date_formats, $EM_Notices, $bp;
	//Success notice
	if( !empty($_REQUEST['successful']) ){
		echo get_option('dbem_events_anonymous_result_success');
		return false;
	}
	//check that user can access this page
	if( is_object($EM_Event) && !$EM_Event->can_manage('edit_events','edit_others_events') && !(!is_user_logged_in() && get_option('dbem_events_anonymous_submissions') && empty($EM_Event->event_id)) ){
		?>
		<div class="wrap"><h2><?php _e('Unauthorized Access','dbem'); ?></h2><p><?php echo sprintf(__('You do not have the rights to manage this %s.','dbem'),__('Event','dbem')); ?></p></div>
		<?php
		return false;
	}
	
	if( empty($EM_Event->event_id) ){
		$EM_Event = ( is_object($EM_Event) && get_class($EM_Event) == 'EM_Event') ? $EM_Event : new EM_Event();
		$title = __ ( "Insert New Event", 'dbem' );
		//Give a default location & category
		$default_cat = get_option('dbem_default_category');
		$default_loc = get_option('dbem_default_location');
		if( is_numeric($default_cat) && $default_cat > 0 && !empty($EM_Event->get_categories->categories) ){
			$EM_Category = new EM_Category($default_cat);
			$EM_Event->get_categories()->categories[] = $EM_Category;
		}
		if( is_numeric($default_loc) && $default_loc > 0 && ( empty($EM_Event->location->id) && empty($EM_Event->location->name) && empty($EM_Event->location->address) && empty($EM_Event->location->town) ) ){
			$EM_Event->location_id = $default_loc;
			$EM_Event->location = new EM_Location($default_loc);
		}
	}
	
	// change prefix according to event/recurrence
	$pref = "event_";
	
	$locale_code = substr ( get_locale (), 0, 2 );
	$localised_date_format = $localised_date_formats[$locale_code];
	
	//FIXME time useage is very flimsy imho
	$hours_locale_regexp = "H:i";
	// Setting 12 hours format for those countries using it
	if (preg_match ( "/en|sk|zh|us|uk/", $locale_code ))
		$hours_locale_regexp = "h:iA";
		
	//echo "<pre>"; print_r($EM_Event); echo "</pre>";
	$required = '*';
	?>

	<?php echo $EM_Notices; ?>	
	<form enctype='multipart/form-data' id="event-form" method="post" action="">
		<div class="wrap">
			<?php do_action('em_front_event_form_header'); ?>
			
			<h4 class="event-form-name"><?php _e ( 'Event Name', 'dbem' ); ?></h4>
			<div class="inside event-form-name">
				<input type="text" name="event_name" id="event-name" value="<?php echo htmlspecialchars($EM_Event->event_name,ENT_QUOTES); ?>" /><?php echo $required; ?>
				<br />
				<?php _e ( 'The event name. Example: Birthday party', 'dbem' )?>
				<?php if( empty($EM_Event->group_id) ): ?>
					<?php 
					$user_groups = array();
					if( !empty($bp->groups) ){
						$group_data = groups_get_user_groups(get_current_user_id());
						foreach( $group_data['groups'] as $group_id ){
							if( groups_is_user_admin(get_current_user_id(), $group_id) ){
								$user_groups[] = groups_get_group( array('group_id'=>$group_id)); 
							}
						}
					} 
					?>
					<?php if( count($user_groups) > 0 ): ?>
					<p>
						<select name="group_id">
							<option value="<?php echo $BP_Group->id; ?>">Not a Group Event</option>
						<?php
						foreach($user_groups as $BP_Group){
							?>
							<option value="<?php echo $BP_Group->id; ?>"><?php echo $BP_Group->name; ?></option>
							<?php
						} 
						?>
						</select>
						<br />
						<?php _e ( 'Select a group you admin to attach this event to it. Note that all other admins of that group can modify the booking, and you will not be able to unattach the event without deleting it.', 'dbem' )?>
					</p>
					<?php endif; ?>
				<?php endif; ?>
			</div>
						
			<h4 class="event-form-when"><?php _e ( 'When', 'dbem' ); ?></h4>
			<?php if( empty($EM_Event->event_id) ):?>
				<p><?php _e('This is a recurring event.', 'dbem'); ?> <input type="checkbox" id="em-recurrence-checkbox" name="recurring" value="1" <?php if($EM_Event->is_recurring()) echo 'checked' ?> /></p>
				<?php em_locate_template('forms/event/when-with-recurring.php',true); ?>
				<script type="text/javascript">
				//<![CDATA[
				jQuery(document).ready( function($) {
					$("#start-time").timeEntry({spinnerImage: '', show24Hours: false });
					$("#end-time").timeEntry({spinnerImage: '', show24Hours: false});
					$('#em-recurrence-checkbox').change(function(){
						if( $('#em-recurrence-checkbox').is(':checked') ){
							$('.em-recurring-text').show();
							$('.em-event-text').hide();
						}else{
							$('.em-recurring-text').hide();
							$('.em-event-text').show();						
						}
					});
					$('#em-recurrence-checkbox').trigger('change');
				});
				//]]>
				</script>
			<?php elseif( !$EM_Event->is_recurring() ): ?>
				<?php em_locate_template('forms/event/when.php',true); ?>
			<?php elseif( empty($EM_Event->event_id) || $EM_Event->is_recurring() ): ?>
				<?php em_locate_template('forms/event/recurring-when.php',true); ?>
			<?php endif; ?>
			<script type='text/javascript' src='<?php echo WP_PLUGIN_URL; ?>/events-manager/includes/js/timeentry/jquery.timeentry.js'></script>
			
			<h4 class="event-form-where"><?php _e ( 'Where', 'dbem' ); ?></h4>
			<?php em_locate_template('forms/event/location.php',true); ?>
			
			<h4 class="event-form-details"><?php _e ( 'Details', 'dbem' ); ?></h4>
			<div class="inside event-form-details">
				<div>
					<textarea name="content" rows="10" style="width:100%"><?php echo $EM_Event->post_content ?></textarea>
					<br />
					<?php _e ( 'Details about the event.', 'dbem' )?><?php _e ( 'HTML Allowed.', 'dbem' )?>
				</div>
				<div>
				<?php if(get_option('dbem_categories_enabled')) :?>
					<?php $categories = EM_Categories::get(array('orderby'=>'category_name')); ?>
					<?php if( count($categories) > 0 ): ?>
						<!-- START Categories -->
						<label for="event_categories[]"><?php _e ( 'Category:', 'dbem' ); ?></label>
						<select name="event_categories[]" multiple size="10">
							<?php
							foreach ( $categories as $EM_Category ){
								$selected = ($EM_Event->get_categories()->has($EM_Category->term_id)) ? "selected='selected'": '';
								?>
								<option value="<?php echo $EM_Category->term_id ?>" <?php echo $selected ?>>
								<?php echo $EM_Category->name ?>
								</option>
								<?php 
							}
							?>
						</select>						
						<!-- END Categories -->
					<?php endif; ?>
				<?php endif; ?>	
				</div>
			
				<?php if(get_option('dbem_attributes_enabled')) : ?>
					<?php
					$attributes = em_get_attributes();
					$has_depreciated = false;
					?>
					<?php if( count( $attributes['names'] ) > 0 ) : ?>
						<?php foreach( $attributes['names'] as $name) : ?>
						<div>
							<label for="em_attributes[<?php echo $name ?>]"><?php echo $name ?></label>
							<?php if( count($attributes['values'][$name]) > 0 ): ?>
							<select name="em_attributes[<?php echo $name ?>]">
								<?php foreach($attributes['values'][$name] as $attribute_val): ?>
									<?php if( is_array($EM_Event->event_attributes) && array_key_exists($name, $EM_Event->event_attributes) && $EM_Event->event_attributes[$name]==$attribute_val ): ?>
										<option selected="selected"><?php echo $attribute_val; ?></option>
									<?php else: ?>
										<option><?php echo $attribute_val; ?></option>
									<?php endif; ?>
								<?php endforeach; ?>
							</select>
							<?php else: ?>
							<input type="text" name="em_attributes[<?php echo $name ?>]" value="<?php echo array_key_exists($name, $EM_Event->event_attributes) ? htmlspecialchars($EM_Event->event_attributes[$name], ENT_QUOTES):''; ?>" />
							<?php endif; ?>
						</div>
						<?php endforeach; ?>
					<?php endif; ?>
				<?php endif; ?>
			</div>
			
			<h4><?php _e ( 'Event Image', 'dbem' ); ?></h4>
			<div class="inside" style="padding:10px;">
					<?php if ($EM_Event->get_image_url() != '') : ?> 
						<img src='<?php echo $EM_Event->get_image_url('medium'); ?>' alt='<?php echo $EM_Event->event_name ?>'/>
					<?php else : ?> 
						<?php _e('No image uploaded for this event yet', 'dbem') ?>
					<?php endif; ?>
					<br /><br />
					<label for='event_image'><?php _e('Upload/change picture', 'dbem') ?></label> <input id='event-image' name='event_image' id='event_image' type='file' size='40' />
					<br />
					<label for='event_image_delete'><?php _e('Delete Image?', 'dbem') ?></label> <input id='event-image-delete' name='event_image_delete' id='event_image_delete' type='checkbox' value='1' />
			</div>
			
			<?php if( get_option('dbem_rsvp_enabled') && $EM_Event->can_manage('manage_bookings','manage_others_bookings') ) : ?>
				<!-- START Bookings -->
				<h4><span><?php _e('Bookings/Registration','dbem'); ?></span></h4>
				<?php em_locate_template('forms/event/bookings.php',true); ?>
				<!-- END Bookings -->
			<?php endif; ?>
			<?php do_action('em_front_event_form_footer'); ?>
		</div>
		<p class="submit">
			<input type="submit" name="events_update" value="<?php _e ( 'Submit Event', 'dbem' ); ?> &raquo;" />
		</p>
		<input type="hidden" name="event_id" value="<?php echo $EM_Event->event_id; ?>" />
		<input type="hidden" name="_wpnonce" value="<?php echo wp_create_nonce('wpnonce_event_save'); ?>" />
		<input type="hidden" name="action" value="event_save" />
	</form>
	<?php em_locate_template('forms/tickets-form.php', true); //put here as it can't be in the add event form ?>
	<script type="text/javascript">
		jQuery(document).ready( function($) {
			<?php if( $EM_Event->is_recurring() ): ?>
			//Recurrence Warnings
			$('#event_form').submit( function(event){
				confirmation = confirm('<?php _e('Are you sure you want to reschedule this recurring event? If you do this, you will lose all booking information and the old recurring events will be deleted.', 'dbem'); ?>');
				if( confirmation == false ){
					event.preventDefault();
				}
			});
			<?php endif; ?>
			//RSVP Warning
			$('#bookings-checkbox').click( function(event){
				if( !this.checked ){
					confirmation = confirm('<?php _e('Are you sure you want to disable bookings? If you do this and save, you will lose all previous bookings. If you wish to prevent further bookings, reduce the number of spaces available to the amount of bookings you currently have', 'dbem'); ?>');
					if( confirmation == false ){
						event.preventDefault();
					}else{
						$("div#bookings-data").hide();
					}
				}else{
					$("div#bookings-data").fadeIn();
				}
			});
			if($('input#bookings-checkbox').attr("checked")) {
				$("div#bookings-data").fadeIn();
			} else {
				$("div#bookings-data").hide();
			}
		});		
	</script>