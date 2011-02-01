<?php

/**
 * Determines whether to show event page or events page, and saves any updates to the event or events
 * @return null
 */
function em_admin_events_page() {
	//TODO Simplify panel for events, use form flags to detect certain actions (e.g. submitted, etc)
	global $wpdb;
	global $EM_Event;
	$action = ( !empty($_GET ['action']) ) ? $_GET ['action']:'';
	$order = ( !empty($_GET ['order']) ) ? $_GET ['order']:'ASC';
	$limit = ( !empty($_GET['limit']) ) ? $_GET['limit'] : 20;//Default limit
	$page = ( !empty($_GET['p']) ) ? $_GET['p']:1;
	$offset = ( $page > 1 ) ? ($page-1)*$limit : 0;
	$scope_names = array (
		'past' => __ ( 'Past events', 'dbem' ),
		'all' => __ ( 'All events', 'dbem' ),
		'future' => __ ( 'Future events', 'dbem' )
	);
	$scope = ( !empty($_GET ['scope']) && array_key_exists($_GET ['scope'], $scope_names) ) ? $_GET ['scope']:'future';
	$selectedEvents = ( !empty($_GET ['events']) ) ? $_GET ['events']:'';
	
	// DELETE action
	if ( $action == 'deleteEvents' && EM_Object::array_is_numeric($selectedEvents) ) {
		EM_Events::delete( $selectedEvents );
	}
	
	// No action, only showing the events list
	switch ($scope) {
		case "past" :
			$title = __ ( 'Past Events', 'dbem' );
			break;
		case "all" :
			$title = __ ( 'All Events', 'dbem' );
			break;
		default :
			$title = __ ( 'Future Events', 'dbem' );
			$scope = "future";
	}
	$args = array('scope'=>$scope, 'limit'=>0, 'order'=>$order );
	if( !get_option('dbem_permissions_events') && !em_verify_admin() ){
		$args['owner'] = get_current_user_id();
	}	
	$events = EM_Events::get( $args );
	$events_count = count ( $events );
	
	$use_events_end = get_option ( 'dbem_use_event_end' );
	?>
	<div class="wrap">
		<div id="icon-events" class="icon32"><br />
		</div>
		<h2>	
			<?php echo $title; ?>
 	 		<a href="admin.php?page=events-manager-event" class="button add-new-h2"><?php _e('Add New','dbem'); ?></a>
 	 	</h2>
		<?php	
			$link = array ();
			$link ['past'] = "<a href='" . get_bloginfo ( 'wpurl' ) . "/wp-admin/admin.php?page=events-manager&amp;scope=past&amp;order=desc'>" . __ ( 'Past events', 'dbem' ) . "</a>";
			$link ['all'] = " <a href='" . get_bloginfo ( 'wpurl' ) . "/wp-admin/admin.php?page=events-manager&amp;scope=all&amp;order=desc'>" . __ ( 'All events', 'dbem' ) . "</a>";
			$link ['future'] = "  <a href='" . get_bloginfo ( 'wpurl' ) . "/wp-admin/admin.php?page=events-manager&amp;scope=future'>" . __ ( 'Future events', 'dbem' ) . "</a>";
		?> 
		<?php if ( !empty($_GET['error']) ) : ?>
		<div id='message' class='error'>
			<p><?php echo $_GET['error']; ?></p>
		</div>
		<?php endif; ?>
		<?php if ( !empty($_GET['message']) ) : ?>
		<div id='message' class='updated fade'>
			<p><?php echo $_GET['message']; ?></p>
		</div>
		<?php endif; ?>
		<form id="posts-filter" action="" method="get"><input type='hidden' name='page' value='events-manager' />
			<ul class="subsubsub">
				<li><a href='#' class="current"><?php _e ( 'Total', 'dbem' ); ?> <span class="count">(<?php echo (count ( $events )); ?>)</span></a></li>
			</ul>
			<p class="search-box">
				<label class="screen-reader-text" for="post-search-input"><?php _e('Search Events','dbem'); ?>:</label>
				<input type="text" id="post-search-input" name="em_search" value="<?php echo (!empty($_GET['em_search'])) ? $_GET['em_search']:''; ?>" />
				<input type="submit" value="<?php _e('Search Events','dbem'); ?>" class="button" />
			</p>			
			<div class="tablenav">
			
				<div class="alignleft actions">
					<select name="action">
						<option value="-1" selected="selected"><?php _e ( 'Bulk Actions' ); ?></option>
						<option value="deleteEvents"><?php _e ( 'Delete selected','dbem' ); ?></option>
					</select> 
					<input type="submit" value="<?php _e ( 'Apply' ); ?>" name="doaction2" id="doaction2" class="button-secondary action" /> 
					<select name="scope">
						<?php
						foreach ( $scope_names as $key => $value ) {
							$selected = "";
							if ($key == $scope)
								$selected = "selected='selected'";
							echo "<option value='$key' $selected>$value</option>  ";
						}
						?>
					</select> 
					<input id="post-query-submit" class="button-secondary" type="submit" value="<?php _e ( 'Filter' )?>" />
				</div>
				<!--
				<div class="view-switch">
					<a href="/wp-admin/edit.php?mode=list"><img class="current" id="view-switch-list" src="http://wordpress.lan/wp-includes/images/blank.gif" width="20" height="20" title="List View" alt="List View" name="view-switch-list" /></a> <a href="/wp-admin/edit.php?mode=excerpt"><img id="view-switch-excerpt" src="http://wordpress.lan/wp-includes/images/blank.gif" width="20" height="20" title="Excerpt View" alt="Excerpt View" name="view-switch-excerpt" /></a>
				</div>
				-->
				<?php 
				if ( $events_count >= $limit ) {
					$page_link_template = em_add_get_params($_SERVER['REQUEST_URI'], array('p'=>'%PAGE%'));
					$events_nav .= em_admin_paginate( $page_link_template, $events_count, $limit, $page, 5);
					echo $events_nav;
				}
				?>
				<br class="clear" />
			</div>
				
			<?php
			if (empty ( $events )) {
				// TODO localize
				_e ( 'no events','dbem' );
			} else {
			?>
					
			<table class="widefat">
				<thead>
					<tr>
						<th class='manage-column column-cb check-column' scope='col'>
							<input class='select-all' type="checkbox" value='1' />
						</th>
						<th><?php _e ( 'Name', 'dbem' ); ?></th>
						<th>&nbsp;</th>
						<th><?php _e ( 'Location', 'dbem' ); ?></th>
						<th colspan="2"><?php _e ( 'Date and time', 'dbem' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php 
					$rowno = 0;
					$event_count = 0;
					foreach ( $events as $event ) {
						/* @var $event EM_Event */
						if( ($rowno < $limit || empty($limit)) && ($event_count >= $offset || $offset === 0) ) {
							$rowno++;
							$class = ($rowno % 2) ? ' class="alternate"' : '';
							// FIXME set to american
							$localised_start_date = date_i18n('D d M Y', $event->start);
							$localised_end_date = date_i18n('D d M Y', $event->end);
							$style = "";
							$today = date ( "Y-m-d" );
							$location_summary = "<b>" . $event->location->name . "</b><br/>" . $event->location->address . " - " . $event->location->town;
							$category = new EM_Category($event->category_id);
							
							if ($event->start_date < $today && $event->end_date < $today){
								$style = "style ='background-color: #FADDB7;'";
							}							
							?>
							<tr <?php echo "$class $style"; ?>>
				
								<td>
									<input type='checkbox' class='row-selector' value='<?php echo $event->id; ?>' name='events[]' />
								</td>
								<td>
									<strong>
										<a class="row-title" href="<?php bloginfo ( 'wpurl' )?>/wp-admin/admin.php?page=events-manager-event&amp;event_id=<?php echo $event->id ?>&amp;scope=<?php echo $scope ?>&amp;p=<?php echo $page ?>"><?php echo ($event->name); ?></a>
									</strong>
									<?php if( is_object($category) ) : ?>
									<br/><span title='<?php echo __( 'Category', 'dbem' ).": ".$category->name ?>'><?php echo $category->name ?></span>
									<?php endif; ?>
									<?php 
									if( get_option('dbem_rsvp_enabled') == 1 && $event->rsvp == 1 ){
										echo "<br/>";
										echo __("Booked Seats",'dbem').": ". $event->get_bookings()->get_booked_seats()."/".$event->seats;
										if( get_option('dbem_bookings_approval') == 1 ){
											echo " | ". __("Pending",'dbem').": ". $event->get_bookings()->get_pending_seats();
										}
									}
									?>
								</td>
								<td>
									<a href="<?php bloginfo ( 'wpurl' )?>/wp-admin/admin.php?page=events-manager-event&amp;action=duplicate&amp;event_id=<?php echo $event->id; ?>&amp;scope=<?php echo $scope ?>&amp;p=<?php echo $page ?>" title="<?php _e ( 'Duplicate this event', 'dbem' ); ?>">
										<strong>+</strong>
									</a>
								</td>
								<td>
									<?php echo $location_summary; ?>
								</td>
						
								<td>
									<?php echo $localised_start_date; ?>
									<?php echo ($localised_end_date != $localised_start_date) ? " - $localised_end_date":'' ?>
									<br />
									<?php
										//TODO Should 00:00 - 00:00 be treated as an all day event? 
										echo substr ( $event->start_time, 0, 5 ) . " - " . substr ( $event->end_time, 0, 5 ); 
									?>
								</td>
								<td>
									<?php 
									if ( $event->is_recurrence() ) {
										?>
										<strong>
										<?php echo $event->get_recurrence_description(); ?> <br />
										<a href="<?php bloginfo ( 'wpurl' )?>/wp-admin/admin.php?page=events-manager-event&amp;event_id=<?php echo $event->recurrence_id ?>&amp;scope=<?php echo $scope ?>&amp;p=<?php echo $page ?>"><?php _e ( 'Reschedule', 'dbem' ); ?></a>
										</strong>
										<?php
									}
									?>
								</td>
							</tr>
							<?php
						}
						$event_count++;
					}
					?>
				</tbody>
			</table>  
			<?php
			} // end of table
			?>
			<div class='tablenav'>
				<div class="alignleft actions">
				<br class='clear' />
				</div>
				<?php if ( $events_count >= $limit ) : ?>
				<div class="tablenav-pages">
					<?php
					echo $events_nav;
					?>
				</div>
				<?php endif; ?>
				<br class='clear' />
			</div>
		</form>
	</div>
	<?php
}

?>