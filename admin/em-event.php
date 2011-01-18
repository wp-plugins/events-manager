<?php
function em_admin_event_actions(){
	if( current_user_can(EM_MIN_CAPABILITY) && !empty($_GET['page']) && $_GET['page'] == 'events-manager-event' && !empty($_REQUEST ['action']) ){
		global $wpdb;
		global $EM_Event;

		//if dealing with new event, we still want an event object
		if( !is_object($EM_Event) ){
			$EM_Event = new EM_Event();
		}

		// UPDATE or CREATE action
		if ($_REQUEST['action'] == 'save') {
			$validation = $EM_Event->get_post();
			if ( $validation ) { //EM_Event gets the event if submitted via POST and validates it (safer than to depend on JS)
				//Save
				if( $EM_Event->save() ) {
					$page = !empty($_REQUEST['p']) ? $_REQUEST['p']:'';
					$scope = !empty($_REQUEST['scope']) ? $_REQUEST['scope']:'';
					wp_redirect( get_bloginfo('wpurl').'/wp-admin/admin.php?page=events-manager&p='.$page.'&scope='.$scope.'&message='.urlencode($EM_Event->feedback_message));
				}
			}//errors added automatically to event global object
		}
		
		//Copy the event
		if ($_REQUEST['action'] == 'duplicate') {
			global $EZSQL_ERROR;
			$EM_Event = $EM_Event->duplicate();
			if( $EM_Event === false ){
				$redirect_url = em_add_get_params($_SERVER['HTTP_REFERER'], array('error' => __('There was an error duplicating the event. Try again maybe?', 'dbem'), 'message'=>''), false);
				wp_redirect($redirect_url);
			}else{
				$page = !empty($_REQUEST['p']) ? $_REQUEST['p']:'';
				$scope = !empty($_REQUEST['scope']) ? $_REQUEST['scope']:'';
				wp_redirect( get_bloginfo('wpurl').'/wp-admin/admin.php?page=events-manager-event&event_id='.$EM_Event->id.'&p='.$page.'&scope='.$scope.'&message='.urlencode($EM_Event->feedback_message));
			}
		}
	}
}
add_action('admin_init', 'em_admin_event_actions');

/**
 * Generates Event Admin page, for adding and updating a single (or recurring) event.
 * @param $title
 * @return null
 */
function em_admin_event_page() {
	global $EM_Event, $current_user;
	global $localised_date_formats; 
	
	//check that user can access this page
	if( is_object($EM_Event) && !$EM_Event->can_manage() ){
		?>
		<div class="wrap"><h2><?php _e('Unauthorized Access','dbem'); ?></h2><p><?php _e('You do not have the rights to manage this event.','dbem'); ?></p></div>
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
		$EM_Event = new EM_Event();
		$title = __ ( "Insert New Event", 'dbem' );
	}
	
	$use_select_for_locations = get_option('dbem_use_select_for_locations');
	// change prefix according to event/recurrence
	$pref = "event_";	
	
	$locale_code = substr ( get_locale (), 0, 2 );
	$localised_date_format = $localised_date_formats[$locale_code];
	
	//FIXME time useage is very flimsy imho
	$hours_locale_regexp = "H:i";
	// Setting 12 hours format for those countries using it
	if (preg_match ( "/en|sk|zh|us|uk/", $locale_code ))
		$hours_locale_regexp = "h:iA";
	
	$days_names = array (1 => __ ( 'Mon' ), 2 => __ ( 'Tue' ), 3 => __ ( 'Wed' ), 4 => __ ( 'Thu' ), 5 => __ ( 'Fri' ), 6 => __ ( 'Sat' ), 0 => __ ( 'Sun' ) );
	?>
	<?php if ( count($EM_Event->errors) > 0 || !empty($_GET['error']) ) : ?>
	<div id='message' class='error '>
		<p>
			<?php if( count($EM_Event->errors) ){ ?>
			<strong><?php echo __( "Ach, there's a problem here:", "dbem" ) ?></strong><br /><br />
			<?php echo implode('<br />', $EM_Event->errors); ?>
			<?php } else { echo $_GET['error']; } ?>
		</p>
	</div>
	<?php endif; ?>
	<?php if ( !empty($EM_Event->feedback_message) || !empty($_GET['message']) ) : ?>
	<div id='message' class='updated fade'>
		<p><?php echo !empty($EM_Event->feedback_message) ? $EM_Event->feedback_message : $_GET['message']; ?></p>
	</div>
	<?php endif; ?>
	<form id="eventForm" method="post" 	action="">
		<div class="wrap">
			<div id="icon-events" class="icon32"><br /></div>
			<h2><?php echo $title; ?></h2>
			<?php if ( $EM_Event->is_recurrence() || $EM_Event->is_recurring() ) : ?>
			<p id='recurrence_warning'>
				<?php
					//TODO better warning system when changing a recurring event (e.g. when removing recurrences).
					if ( $EM_Event->is_recurring() ) {
						_e ( 'WARNING: This is a recurring event.', 'dbem' );
						echo "<br />";
						_e ( 'Modifying these data all the events linked to this recurrence will be rescheduled', 'dbem' );
						echo " ";
						_e ( 'and all booking information will be deleted!', 'dbem' );
					} elseif ( $EM_Event->is_recurrence() ) {
						//TODO Terminology confusing with methods, maybe worth changing?
						_e ( 'WARNING: This is a recurrence.', 'dbem' );
						echo "<br />";
						_e ( 'If you change these data and save, this will become an independent event.', 'dbem' );
					}
				?>
			</p>
			<?php endif; ?>              
			<div id="poststuff" class="metabox-holder has-right-sidebar">
				<!-- SIDEBAR -->
				<div id="side-info-column" class='inner-sidebar'>
					<div id='side-sortables'>       
						<?php if(get_option('dbem_recurrence_enabled')) : ?>
							<!-- START recurrence postbox -->
							<div class="postbox ">
								<div class="handlediv" title="Fare clic per cambiare."><br />
								</div>
								<h3 class='hndle'><span>
									<?php _e ( "Recurrence", 'dbem' ); ?>
									</span></h3>
									<div class="inside">
									<?php //TODO add js warning if rescheduling, since all bookings are deleted ?>
									<?php if ( !$EM_Event->id || $EM_Event->is_recurring() ) : ?>
										<p>
											<input id="event-recurrence" type="checkbox" name="repeated_event" value="1" <?php echo ( $EM_Event->is_recurring() ) ? 'checked="checked"':'' ; ?> />
											<?php _e ( 'Repeated event', 'dbem' ); ?>
										</p>
										<div id="event_recurrence_pattern">
											<p>
												Frequency:
												<select id="recurrence-frequency" name="recurrence_freq">
													<?php
														$freq_options = array ("daily" => __ ( 'Daily', 'dbem' ), "weekly" => __ ( 'Weekly', 'dbem' ), "monthly" => __ ( 'Monthly', 'dbem' ) );
														em_option_items ( $freq_options, $EM_Event->freq ); 
													?>
												</select>
											</p>
											<p>
												<?php _e ( 'Every', 'dbem' )?>
												<input id="recurrence-interval" name='recurrence_interval' size='2' value='<?php echo $EM_Event->interval ; ?>' />
												<span class='interval-desc' id="interval-daily-singular">
												<?php _e ( 'day', 'dbem' )?>
												</span> <span class='interval-desc' id="interval-daily-plural">
												<?php _e ( 'days', 'dbem' ) ?>
												</span> <span class='interval-desc' id="interval-weekly-singular">
												<?php _e ( 'week', 'dbem' )?>
												</span> <span class='interval-desc' id="interval-weekly-plural">
												<?php _e ( 'weeks', 'dbem' )?>
												</span> <span class='interval-desc' id="interval-monthly-singular">
												<?php _e ( 'month', 'dbem' )?>
												</span> <span class='interval-desc' id="interval-monthly-plural">
												<?php _e ( 'months', 'dbem' )?>
												</span> 
											</p>
											<p class="alternate-selector" id="weekly-selector">
												<?php
													$saved_bydays = ($EM_Event->is_recurring()) ? explode ( ",", $EM_Event->byday ) : array(); 
													em_checkbox_items ( 'recurrence_bydays[]', $days_names, $saved_bydays ); 
												?>
											</p>
											<p class="alternate-selector" id="monthly-selector">
												<?php _e ( 'Every', 'dbem' )?>
												<select id="monthly-modifier" name="recurrence_byweekno">
													<?php
														$weekno_options = array ("1" => __ ( 'first', 'dbem' ), '2' => __ ( 'second', 'dbem' ), '3' => __ ( 'third', 'dbem' ), '4' => __ ( 'fourth', 'dbem' ), '-1' => __ ( 'last', 'dbem' ) ); 
														em_option_items ( $weekno_options, $EM_Event->byweekno  ); 
													?>
												</select>
												<select id="recurrence-weekday" name="recurrence_byday">
													<?php em_option_items ( $days_names, $EM_Event->byday  ); ?>
												</select>
												&nbsp;
											</p>
										</div>
										<p id="recurrence-tip">
											<?php _e ( 'Check if your event happens more than once according to a regular pattern', 'dbem' )?>
										</p>
									<?php elseif( $EM_Event->is_recurrence() ) : ?>
											<p>
												<?php echo $EM_Event->get_recurrence_description(); ?>
												<br />
												<a href="<?php bloginfo ( 'wpurl' )?>/wp-admin/edit.php?page=events-manager&amp;action=edit_event&amp;event_id=<?php echo $EM_Event->recurrence_id; ?>">
												<?php _e ( 'Reschedule', 'dbem' ); ?>
												</a>
												<input type="hidden" name="recurrence_id" value="<?php echo $EM_Event->recurrence_id; ?>" />
											</p>
									<?php else : ?>
										<p><?php _e ( 'This is\'t a recurrent event', 'dbem' ) ?></p>
									<?php endif; ?>
								</div>
							</div> 
							<!-- END recurrence postbox -->   
						<?php endif; ?>          
						<?php if(get_option('dbem_rsvp_enabled')) : ?>
							<!-- START RSVP -->
							<div class="postbox ">
								<div class="handlediv" title="Fare clic per cambiare."><br />
								</div>
								<h3 class='hndle'><span>
									<?php _e ( 'Contact Person', 'dbem' ); ?>
									</span></h3>
								<div class="inside">
									<p><?php _e('Contact','dbem'); ?>
										<?php
											if( em_verify_admin() ){
												wp_dropdown_users ( array ('name' => 'event_contactperson_id', 'show_option_none' => __ ( "Select...", 'dbem' ), 'selected' => $EM_Event->contactperson_id  ) );
											}else{
												?>
												: <?php echo $current_user->display_name ?>
												<input type="hidden" name="event_contactperson_id" value="<?php get_current_user_id() ?>" />
												<br />
												<i><?php _e('You can only change the contact person if you a events administrator.','dbem'); ?></i>
												<?php
											}
										?>
									</p>
								</div>
							</div>
							<div class="postbox ">
								<div class="handlediv" title="Fare clic per cambiare."><br />
								</div>
								<h3 class='hndle'><span><?php _e('RSVP','dbem'); ?></span></h3>
								<div class="inside">
									<p>
										<input id="rsvp-checkbox" name='event_rsvp' value='1' type='checkbox' <?php echo ($EM_Event->rsvp) ? 'checked="checked"' : ''; ?> />
										<?php _e ( 'Enable registration for this event', 'dbem' )?>
									</p>
									<div id='rsvp-data'>
										<?php 
										if ($EM_Event->contactperson_id  != NULL){
											$selected = $EM_Event->contactperson_id;
										}else{
											$selected = '0';
										} 
										?>
										<p>
											<?php _e ( 'Spaces','dbem' ); ?> :
											<input id="seats-input" type="text" name="event_seats" size='5' value="<?php echo $EM_Event->seats ?>" />
										</p>
										<!-- START RSVP Stats -->
										<?php
											if ($EM_Event->rsvp ) {
												$available_seats = $EM_Event->get_bookings()->get_available_seats();
												$booked_seats = $EM_Event->get_bookings()->get_booked_seats();
													
												if ( count($EM_Event->get_bookings()->bookings) > 0 ) {
													?>
													<div class='wrap'>
														<p><strong><?php echo __('Available Spaces','dbem').': '.$EM_Event->get_bookings()->get_available_seats(); ?></strong></p>
														<p><strong><?php echo __('Confirmed Spaces','dbem').': '.$EM_Event->get_bookings()->get_booked_seats(); ?></strong></p>
														<p><strong><?php echo __('Pending Spaces','dbem').': '.$EM_Event->get_bookings()->get_pending_seats(); ?></strong></p>
												 	</div>
													 		
											 	    <br class='clear'/>
											 	    
											 	 	<div id='major-publishing-actions'>  
														<div id='publishing-action'> 
															<a id='printable' href='<?php echo get_bloginfo('wpurl') . "/wp-admin/admin.php?page=events-manager-bookings&event_id=".$EM_Event->id ?>'><?php _e('manage bookings','dbem')?></a><br />
															<a id='printable' target='_blank' href='<?php echo get_bloginfo('wpurl') . "/wp-admin/admin.php?page=events-manager-bookings&action=bookings_report&event_id=".$EM_Event->id ?>'><?php _e('printable view','dbem')?></a>
															<br class='clear'/>             
												        </div>
														<br class='clear'/>    
													</div>
													<?php                                                     
												} else {
													?>
													<p><em><?php _e('No responses yet!')?></em></p>
													<?php
												} 
											}
										?>
										<!-- END RSVP Stats -->
									</div>
								</div>
							</div>
							<!-- END RSVP -->
						<?php endif; ?>  
						<?php if(get_option('dbem_categories_enabled')) :?>
							<!-- START Categories -->
							<div class="postbox ">
								<div class="handlediv" title="Fare clic per cambiare."><br />
								</div>
								<h3 class='hndle'><span>
									<?php _e ( 'Category', 'dbem' ); ?>
									</span></h3>
								<div class="inside">
									<p><?php _e ( 'Category:', 'dbem' ); ?> 
										<select name="event_category_id">
											<?php 
											$categories = EM_Categories::get(array('owner'=>false, 'orderby'=>'category_name'));
											foreach ( $categories as $EM_Category ){
												$selected = ($EM_Category->id == $EM_Event->category_id) ? "selected='selected'": ''; 
												?>
												<option value="<?php echo $EM_Category->id ?>" <?php echo $selected ?>>
												<?php echo $EM_Category->name ?>
												</option>
												<?php 
											} ?>
										</select>
									</p>
								</div>
							</div> 
							<!-- END Categories -->
						<?php endif; ?>
					</div>
				</div>
				<!-- END OF SIDEBAR -->
				<div id="post-body">
					<div id="post-body-content">
						<div id="event_name" class="stuffbox">
							<h3>
								<?php _e ( 'Name', 'dbem' ); ?>
							</h3>
							<div class="inside">
								<input type="text" name="event_name" value="<?php echo htmlspecialchars($EM_Event->name,ENT_QUOTES); ?>" />
								<br />
								<?php _e ( 'The event name. Example: Birthday party', 'dbem' )?>
							</div>
						</div>
						<div id="event_start_date" class="stuffbox">
							<h3 id='event-date-title'>
								<?php _e ( 'Event date', 'dbem' ); ?>
							</h3>
							<h3 id='recurrence-dates-title'>
								<?php _e ( 'Recurrence dates', 'dbem' ); ?>
							</h3>
							<div class="inside">
								<input id="localised-date" type="text" name="localised_event_date" style="display: none;" />
								<input id="date-to-submit" type="text" name="event_start_date" value="<?php echo $EM_Event->start_date ?>" style="background: #FCFFAA" />
								<input id="localised-end-date" type="text" name="localised_event_end_date" style="display: none;" />
								<input id="end-date-to-submit" type="text" name="event_end_date" value="<?php echo $EM_Event->end_date ?>" style="background: #FCFFAA" />
								<br />
								<span id='event-date-explanation'>
								<?php
									_e ( 'The event date.', 'dbem' );
									/* Marcus Begin Edit */
									echo " ";
									_e ( 'When not reoccurring, this event spans between the beginning and end date.', 'dbem' );
									/* Marcus End Edit */
								?>
								</span>
								<span id='recurrence-dates-explanation'>
									<?php _e ( 'The recurrence beginning and end date.', 'dbem' ); ?>
								</span>
							</div>
						</div>
						<div id="event_end_day" class="stuffbox">
							<h3>
								<?php _e ( 'Event time', 'dbem' ); ?>
							</h3>
							<div class="inside">
								<input id="start-time" type="text" size="8" maxlength="8" name="event_start_time" value="<?php echo date( $hours_locale_regexp, strtotime($EM_Event->start_time) ); ?>" />
								-
								<input id="end-time" type="text" size="8" maxlength="8" name="event_end_time" value="<?php echo date( $hours_locale_regexp, strtotime($EM_Event->end_time) ); ?>" />
								<br />
								<?php _e ( 'The time of the event beginning and end', 'dbem' )?>. 
							</div>
						</div>
						<div id="location_coordinates" class="stuffbox" style='display: none;'>
							<h3>
								<?php _e ( 'Coordinates', 'dbem' ); ?>
							</h3>
							<div class="inside">
								<input id='location-latitude' name='location_latitude' type='text' value='<?php echo $EM_Event->latitude; ?>' size='15' />
								-
								<input id='location-longitude' name='location_longitude' type='text' value='<?php echo $EM_Event->longitude; ?>' size='15' />
							</div>
						</div>
						<div id="location_info" class="stuffbox">
							<h3>
								<?php _e ( 'Location', 'dbem' ); ?>
							</h3>
							<div class="inside">
								<table id="dbem-location-data">     
									<tr>
										<td style="padding-right:20px">
											<table>
												<?php if($use_select_for_locations) : ?> 
												<tr>
													<th><?php _e('Location:','dbem') ?></th>
													<td> 
														<select name="location-select-id" id='location-select-id' size="1">  
															<?php 
															$locations = EM_Locations::get();
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
														<p><?php _e ( 'The name of the location where the event takes place. You can use the name of a venue, a square, etc', 'dbem' )?></p>
													</td>
												</tr>
												<?php else : ?>
												<tr>
													<th><?php _e ( 'Name:' )?></th>
													<td>
														<input id="location-name" type="text" name="location_name" value="<?php echo htmlspecialchars($EM_Event->location->name, ENT_QUOTES); ?>" />													
					                            		<p><?php _e ( 'Select a location for your event', 'dbem' )?></p>
					                            	</td>
										 		</tr>
												<tr>
													<th><?php _e ( 'Address:' )?>&nbsp;</th>
													<td>
														<input id="location-address" type="text" name="location_address" value="<?php echo htmlspecialchars($EM_Event->location->address, ENT_QUOTES); ; ?>" />
														<p><?php _e ( 'The address of the location where the event takes place. Example: 21, Dominick Street', 'dbem' )?></p>
													</td>
												</tr>
												<tr>
													<th><?php _e ( 'Town:' )?>&nbsp;</th>
													<td>
														<input id="location-town" type="text" name="location_town" value="<?php echo htmlspecialchars($EM_Event->location->town, ENT_QUOTES); ?>" />
														<p><?php _e ( 'The town where the location is located. If you\'re using the Google Map integration and want to avoid geotagging ambiguities include the country in the town field. Example: Verona, Italy.', 'dbem' )?></p>
													</td>
												</tr>
												<?php endif; ?>
											</table>
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
					</div>
					<div id="event_notes" class="postbox">
						<h3>
							<?php _e ( 'Details', 'dbem' ); ?>
						</h3>
						<div class="inside">
							<div id="<?php echo user_can_richedit() ? 'postdivrich' : 'postdiv'; ?>" class="postarea">
								<?php the_editor($EM_Event->notes ); ?>
							</div>
							<br />
							<?php _e ( 'Details about the event', 'dbem' )?>
						</div>
					</div>
					
					<?php if(get_option('dbem_attributes_enabled')) : ?>
						<div id="event_attributes" class="postbox">
							<h3>
								<?php _e ( 'Attributes', 'dbem' ); ?>
							</h3>
							<div class="inside">
								<?php
								//We also get a list of attribute names and create a ddm list (since placeholders are fixed)
								$formats = 
									get_option ( 'dbem_event_list_item_format' ).
									get_option ( 'dbem_event_page_title_format' ).
									get_option ( 'dbem_full_calendar_event_format' ).
									get_option ( 'dbem_location_baloon_format' ).
									get_option ( 'dbem_location_event_list_item_format' ).
									get_option ( 'dbem_location_page_title_format' ).
									get_option ( 'dbem_map_text_format' ).
									get_option ( 'dbem_rss_description_format' ).
									get_option ( 'dbem_rss_title_format' ).
									get_option ( 'dbem_single_event_format' ).
									get_option ( 'dbem_single_location_format' );
								//We now have one long string of formats, get all the attribute placeholders
								preg_match_all('/#_ATT\{.+?\}(\{.+?\})?/', $formats, $placeholders);
								//Now grab all the unique attributes we can use in our event.
								$attributes = array();
								foreach($placeholders[0] as $result) {
									$attribute = substr( substr($result, 0, strpos($result, '}')), 6 );
									if( !in_array($attribute, $attributes) ){			
										$attributes[] = $attribute ;
									}
								}
								?>
								<div class="wrap">
									<?php if( count( $attributes ) > 0 ) : ?>
										<h2>Attributes</h2>
										<p>Add attributes here</p>
										<table class="form-table">
											<thead>
												<tr valign="top">
													<td><strong>Attribute Name</strong></td>
													<td><strong>Value</strong></td>
												</tr>
											</thead>    
											<tfoot>
												<tr valign="top">
													<td colspan="3"><a href="#" id="mtm_add_tag">Add new tag</a></td>
												</tr>
											</tfoot>
											<tbody id="mtm_body">
												<?php
												$count = 1;
												if( is_array($EM_Event->attributes) and count($EM_Event->attributes) > 0){
													foreach( $EM_Event->attributes as $name => $value){
														?>
														<tr valign="top" id="mtm_<?php echo $count ?>">
															<td scope="row">
																<select name="mtm_<?php echo $count ?>_ref">
																	<?php
																	if( !in_array($name, $attributes) ){
																		echo "<option value='$name'>$name (".__('Not defined in templates', 'dbem').")</option>";
																	}
																	foreach( $attributes as $attribute ){
																		if( $attribute == $name ) {
																			echo "<option selected='selected'>$attribute</option>";
																		}else{
																			echo "<option>$attribute</option>";
																		}
																	}
																	?>
																</select>
																<a href="#" rel="<?php echo $count ?>">Remove</a>
															</td>
															<td>
																<input type="text" name="mtm_<?php echo $count ?>_name" value="<?php echo htmlspecialchars($value, ENT_QUOTES); ?>" />
															</td>
														</tr>
														<?php
														$count++;
													}
												}else{
													?>
													<tr valign="top" id="mtm_<?php echo $count ?>">
														<td scope="row">
															<select name="mtm_<?php echo $count ?>_ref">
																<?php
																foreach( $attributes as $attribute ){
																	echo "<option>$attribute</option>";
																}
																?>
															</select>
															<a href="#" rel="<?php echo $count ?>">Remove</a>
														</td>
														<td>
															<input type="text" name="mtm_<?php echo $count ?>_name" />
														</td>
													</tr>
													<?php
												}
												?>
											</tbody>
										</table>
									<?php else : ?>
										<p>
										<?php _e('In order to use attributes, you must define some in your templates, otherwise they\'ll never show. Go to Events > Settings to add attribute placeholders.', 'dbem'); ?>
										</p> 
										<script>
											jQuery(document).ready(function($){ $('#event_attributes').addClass('closed'); });
										</script>
									<?php endif; ?>
								</div>
							</div>
						</div>
						<?php endif; ?>
					</div>
					<p class="submit">
						<input type="submit" name="events_update" value="<?php _e ( 'Submit Event', 'dbem' ); ?> &raquo;" />
					</p>
					<input type="hidden" name="p" value="<?php echo ( !empty($_REQUEST['p']) ) ? $_REQUEST['p']:''; ?>" /><a>
					<input type="hidden" name="scope" value="<?php echo ( !empty($_REQUEST['scope']) ) ? $_REQUEST['scope']:'' ?>" /></a>
					<input type="hidden" name="action" value="save" />
				</div>
			</div>
		</div>
	</form>
	<script type="text/javascript">
		jQuery(document).ready( function($) {
			<?php if( $EM_Event->is_recurring() ): ?>
			//Recurrence Warnings
			$('#eventForm').submit( function(event){
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
					confirmation = confirm('<?php _e('Are you sure you want to disable bookings? If you do this and save, you will lose all previous bookings. If you wish to prevent further bookings, reduce the number of seats available to the amount of bookings you currently have', 'dbem'); ?>');
					if( confirmation == false ){
						event.preventDefault();
					}
				}
			});
			<?php endif; ?>
		});		
	</script>
<?php
}
?>