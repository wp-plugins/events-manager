<?php
	global $EM_Event, $current_user, $localised_date_formats, $EM_Notices;
	
	//check that user can access this page
	if( is_object($EM_Event) && !$EM_Event->can_manage('edit_events','edit_others_events') ){
		?>
		<div class="wrap"><h2><?php _e('Unauthorized Access','dbem'); ?></h2><p><?php echo sprintf(__('You do not have the rights to manage this %s.','dbem'),__('Event','dbem')); ?></p></div>
		<?php
		return false;
	}
	
	if( is_object($EM_Event) && $EM_Event->id > 0 ){
		if($EM_Event->is_recurring()){
			$title = __( "Reschedule", 'dbem' )." '{$EM_Event->name}'";
		}else{
			$title = __ ( "Edit Event", 'dbem' ) . " '" . $EM_Event->name . "'";
		}
	} else {
		$EM_Event = ( is_object($EM_Event) && get_class($EM_Event) == 'EM_Event') ? $EM_Event : new EM_Event();
		$title = __ ( "Insert New Event", 'dbem' );
		//Give a default location & category
		$default_cat = get_option('dbem_default_category');
		$default_loc = get_option('dbem_default_location');
		if( is_numeric($default_cat) && $default_cat > 0 ){
			$EM_Event->category_id = $default_cat;
			$EM_Event->category = new EM_Category($default_cat);
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
	?>

	<?php echo $EM_Notices; ?>	
	<form id="event-form" method="post" action="">
		<div class="wrap">			
			<?php if ( count($EM_Event->warnings) > 0 ) : ?>
				<?php foreach($EM_Event->warnings as $warning): ?>
				<p class="warning"><?php echo $warning; ?></p>
				<?php endforeach; ?>
			<?php endif; ?>        
			
			<h4><?php _e ( 'Event Name', 'dbem' ); ?></h4>
			<div class="inside">
				<input type="text" name="event_name" id="event-name" value="<?php echo htmlspecialchars($EM_Event->name,ENT_QUOTES); ?>" />
				<br />
				<?php _e ( 'The event name. Example: Birthday party', 'dbem' )?>
			</div>
						
			<h4 id='event-date-title'><?php _e ( 'When', 'dbem' ); ?></h4>
			<div class="inside">
				<div>
					<?php _e ( 'Starts on ', 'dbem' ); ?>					
					<input id="localised-date" type="text" name="localised_event_date" style="display: none;" />
					<input id="date-to-submit" type="text" name="event_start_date" value="<?php echo $EM_Event->start_date ?>" style="background: #FCFFAA" />
					<?php _e('from','dbem'); ?>
					<input id="start-time" type="text" size="8" maxlength="8" name="event_start_time" value="<?php echo date( $hours_locale_regexp, strtotime($EM_Event->start_time) ); ?>" />
					<?php _e('to','dbem'); ?>
					<input id="end-time" type="text" size="8" maxlength="8" name="event_end_time" value="<?php echo date( $hours_locale_regexp, strtotime($EM_Event->end_time) ); ?>" />
					<?php _e('and ends on','dbem'); ?>
					<input id="localised-end-date" type="text" name="localised_event_end_date" style="display: none;" />
					<input id="end-date-to-submit" type="text" name="event_end_date" value="<?php echo $EM_Event->end_date ?>" style="background: #FCFFAA" />
				</div>			
				<div>
					<span id='event-date-explanation'>
					<?php _e( 'This event spans every day between the beginning and end date, with start/end times applying to each day.', 'dbem' ); ?>
					</span>
					<span id='recurrence-dates-explanation'>
						<?php _e( 'For a recurring event, a one day event will be created on each recurring date within this date range.', 'dbem' ); ?>
					</span>
				</div> 
			</div>  
			<?php if( get_option('dbem_recurrence_enabled') && ($EM_Event->is_recurrence() || $EM_Event->is_recurring() || $EM_Event->id == '') ) : //for now we don't need to show recurrences for single saved events, as backend doesn't allow either ?>
				<!-- START recurrence postbox -->
				<div class="inside">
				<?php em_locate_template('forms/events/recurrence-box.php', true); ?>
				</div>
				<!-- END recurrence postbox -->   
			<?php endif; ?>
			
			
			<h4><?php _e ( 'Where', 'dbem' ); ?></h4>
			<div class="inside">
				<table id="dbem-location-data">     
					<tr>
						<td style="padding-right:20px; vertical-align:top;">
							<?php
								$args = array();
								$args['owner'] = current_user_can('read_others_locations') ? false:get_current_user_id(); 
								$locations = EM_Locations::get($args); 
							?>
							<?php  if( count($locations) > 0): ?>
							<select name="location_id" id='location-select-id' size="1">  
								<?php 
								foreach($locations as $location) {    
									$selected = "";  
									if( is_object($EM_Event->location) )  {
										if ($EM_Event->location->id == $location->id) 
											$selected = "selected='selected' ";
									}
							   		?>          
							    	<option value="<?php echo $location->id ?>" title="<?php echo "{$location->latitude},{$location->longitude}" ?>" <?php echo $selected ?>><?php echo $location->name; ?></option>
							    	<?php
								}
								?>
							</select>
							<?php endif; ?>
							<p><?php _e ( 'Choose from one of your locations', 'dbem' )?> <?php echo sprintf(__('or <a href="%s">add a new location</a>','dbem'),'#'); ?></p>
						</td>
						<?php if ( get_option ( 'dbem_gmap_is_active' ) ) : ?>
						<td width="400">
							<div id='em-map-404' style='width: 400px; vertical-align:middle; text-align: center;'>
								<p><em><?php _e ( 'Location not found', 'dbem' ); ?></em></p>
							</div>
							<div id='em-map' style='width: 400px; height: 300px; display: none;'></div>
						</td>
						<?php endif; ?>
					</tr>
				</table>
			</div>
			
			<h4><?php _e ( 'Details', 'dbem' ); ?></h4>
			<div class="inside">
				<textarea name="content" rows="10" style="width:100%"><?php echo $EM_Event->notes ?></textarea>
				<br />
				<?php _e ( 'Details about the event.', 'dbem' )?><?php _e ( 'HTML Allowed.', 'dbem' )?>
			</div>
			<br/>
			<div class="inside">
				<?php em_locate_template('forms/events/categories-box.php', true); ?>	
			</div>
		
			<?php if(get_option('dbem_attributes_enabled')) : ?>
				<?php em_locate_template('forms/events/attributes-box.php', true); ?>
			<?php endif; ?>
	
			<?php if(get_option('dbem_rsvp_enabled')) : ?>
				<!-- START RSVP -->
				<h4 class='hndle'><span><?php _e('Bookings/Registration','dbem'); ?></span></h4>
				<div class="inside">
					<?php em_locate_template('forms/events/bookings-box.php', true); ?>
				</div>
				<!-- END RSVP -->
			<?php endif; ?>				
		</div>
		<p class="submit">
			<input type="submit" name="events_update" value="<?php _e ( 'Submit Event', 'dbem' ); ?> &raquo;" />
		</p>
		<input type="hidden" name="event_id" value="<?php echo $EM_Event->id; ?>" />
		<input type="hidden" name="_wpnonce" value="<?php echo wp_create_nonce('wpnonce_event_save'); ?>" />
		<input type="hidden" name="action" value="event_save" />
	</form>
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
			<?php if( $EM_Event->rsvp == 1 ): ?>
			//RSVP Warning
			$('#rsvp-checkbox').click( function(event){
				if( !this.checked ){
					confirmation = confirm('<?php _e('Are you sure you want to disable bookings? If you do this and save, you will lose all previous bookings. If you wish to prevent further bookings, reduce the number of spaces available to the amount of bookings you currently have', 'dbem'); ?>');
					if( confirmation == false ){
						event.preventDefault();
					}else{
						$('#event_tickets').hide();
						$("div#rsvp-data").hide();
					}
				}else{
					$('#event_tickets').show();
					$("div#rsvp-data").fadeIn();
				}
			});
			  
			if($('input#rsvp-checkbox').attr("checked")) {
				$("div#rsvp-data").fadeIn();
			} else {
				$("div#rsvp-data").hide();
			}
			<?php endif; ?>
		});		
	</script>