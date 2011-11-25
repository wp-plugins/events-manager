<?php

	//TODO Simplify panel for events, use form flags to detect certain actions (e.g. submitted, etc)
	global $wpdb, $bp, $EM_Event, $EM_Notices;
	
	$url = $bp->events->link . 'my-events/'; //url to this page
	$order = ( !empty($_REQUEST ['order']) ) ? $_REQUEST ['order']:'ASC';
	$limit = ( !empty($_REQUEST['limit']) ) ? $_REQUEST['limit'] : 20;//Default limit
	$page = ( !empty($_REQUEST['pno']) ) ? $_REQUEST['pno']:1;
	$offset = ( $page > 1 ) ? ($page-1)*$limit : 0;
	$search = ( !empty($_REQUEST['em_search']) ) ? $_REQUEST['em_search']:'';
	$scope_names = em_get_scopes();
	$scope = ( !empty($_REQUEST ['scope']) && array_key_exists($_REQUEST ['scope'], $scope_names) ) ? $_REQUEST ['scope']:'future';
	if( array_key_exists('status', $_REQUEST) ){
		$status = ($_REQUEST['status']) ? 1:0;
	}else{
		$status = false;
	}
	$args = array(
		'scope' => $scope, 
		'limit' => 0, 
		'order' => $order, 
		'search' => $search,
		'owner' => get_current_user_id(),
		'status' => $status
	);
	$EM_Events = EM_Events::get( $args );
	$events_count = count ( $EM_Events );
	$future_count = EM_Events::count( array('status'=>1, 'owner' =>get_current_user_id(), 'scope' => 'future'));
	$pending_count = EM_Events::count( array('status'=>0, 'owner' =>get_current_user_id(), 'scope' => 'all') );
	$use_events_end = get_option('dbem_use_event_end');
	?>
	<div class="wrap">
		<?php echo $EM_Notices; ?>
		<form id="posts-filter" action="<?php echo $url; ?>" method="get">
 	 		<?php if(current_user_can('edit_events')): ?><a href="<?php echo $url?>edit/" class="button add-new-h2"><?php _e('Add New','dbem'); ?></a><?php endif; ?>
			<div class="subsubsub">
				<a href='<?php echo $url; ?>' <?php echo ( !isset($_GET['status']) ) ? 'class="current"':''; ?>><?php _e ( 'Upcoming', 'dbem' ); ?> <span class="count">(<?php echo $future_count; ?>)</span></a> &nbsp;|&nbsp; 
				<?php if( !current_user_can('publish_events') ): ?>
				<a href='<?php echo $url ?>?status=0' <?php echo ( isset($_GET['status']) && $_GET['status']=='0' ) ? 'class="current"':''; ?>><?php _e ( 'Pending', 'dbem' ); ?> <span class="count">(<?php echo $pending_count; ?>)</span></a> &nbsp;|&nbsp; 
				<?php endif; ?>
				<a href='<?php echo $url; ?>?scope=past' <?php echo ( !empty($_REQUEST['scope']) && $_REQUEST['scope'] == 'past' ) ? 'class="current"':''; ?>><?php _e ( 'Past Events', 'dbem' ); ?></a>
			</div>
			<p class="search-box">
				<label class="screen-reader-text" for="post-search-input"><?php _e('Search Events','dbem'); ?>:</label>
				<input type="text" id="post-search-input" name="em_search" value="<?php echo (!empty($_REQUEST['em_search'])) ? $_REQUEST['em_search']:''; ?>" />
				<input type="submit" value="<?php _e('Search Events','dbem'); ?>" class="button" />
			</p>			
			<div class="tablenav">
				<?php
				if ( $events_count >= $limit ) {
					$events_nav = em_admin_paginate( $events_count, $limit, $page);
					echo $events_nav;
				}
				?>
				<br class="clear" />
			</div>
				
			<?php
			if (empty ( $EM_Events )) {
				// TODO localize
				_e ( 'no events','dbem' );
			} else {
			?>
					
			<table class="widefat events-table">
				<thead>
					<tr>
						<?php /* 
						<th class='manage-column column-cb check-column' scope='col'>
							<input class='select-all' type="checkbox" value='1' />
						</th>
						*/ ?>
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
					foreach ( $EM_Events as $event ) {
						/* @var $event EM_Event */
						if( ($rowno < $limit || empty($limit)) && ($event_count >= $offset || $offset === 0) ) {
							$rowno++;
							$class = ($rowno % 2) ? 'alternate' : '';
							// FIXME set to american
							$localised_start_date = date_i18n('D d M Y', $event->start);
							$localised_end_date = date_i18n('D d M Y', $event->end);
							$style = "";
							$today = date ( "Y-m-d" );
							$location_summary = "<b>" . $event->location->name . "</b><br/>" . $event->location->address . " - " . $event->location->town;
							
							if ($event->start_date < $today && $event->end_date < $today){
								$class .= " past";
							}
							//Check pending approval events
							if ( !$event->status ){
								$class .= " pending";
							}					
							?>
							<tr class="event <?php echo trim($class); ?>" <?php echo $style; ?> id="event_<?php echo $event->id ?>">
								<?php /*
								<td>
									<input type='checkbox' class='row-selector' value='<?php echo $event->id; ?>' name='events[]' />
								</td>
								*/ ?>
								<td>
									<strong>
										<a class="row-title" href="<?php echo $url; ?>edit/?event_id=<?php echo $event->id ?>"><?php echo ($event->name); ?></a>
									</strong>
									<?php 
									if( get_option('dbem_rsvp_enabled') == 1 && $event->rsvp == 1 ){
										?>
										<br/>
										<a href="<?php echo $url ?>bookings/?event_id=<?php echo $event->id ?>"><?php echo __("Bookings",'dbem'); ?></a> &ndash;
										<?php _e("Booked",'dbem'); ?>: <?php echo $event->get_bookings()->get_booked_spaces()."/".$event->get_spaces(); ?>
										<?php if( get_option('dbem_bookings_approval') == 1 ): ?>
											| <?php _e("Pending",'dbem') ?>: <?php echo $event->get_bookings()->get_pending_spaces(); ?>
										<?php endif;
									}
									?>
									<div class="row-actions">
										<?php if( current_user_can('delete_events')) : ?>
										<span class="trash"><a href="<?php echo $url ?>?action=event_delete&amp;event_id=<?php echo $event->id ?>" class="em-event-delete"><?php _e('Delete','dbem'); ?></a></span>
										<?php endif; ?>
									</div>
								</td>
								<td>
									<a href="<?php echo $url ?>edit/?action=event_duplicate&amp;event_id=<?php echo $event->id ?>" title="<?php _e ( 'Duplicate this event', 'dbem' ); ?>">
										<strong>+</strong>
									</a>
								</td>
								<td>
									<?php echo $location_summary; ?>
									<?php if( is_object($category) && !empty($category->name) ) : ?>
									<br/><span class="category"><strong><?php _e( 'Category', 'dbem' ); ?>: </strong><?php echo $category->name ?></span>
									<?php endif; ?>
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
										$recurrence_delete_confirm = __('WARNING! You will delete ALL recurrences of this event, including booking history associated with any event in this recurrence. To keep booking information, go to the relevant single event and save it to detach it from this recurrence series.','dbem');
										?>
										<strong>
										<?php echo $event->get_recurrence_description(); ?> <br />
										<a href="<?php echo $url ?>edit/?event_id=<?php echo $event->recurrence_id ?>"><?php _e ( 'Reschedule', 'dbem' ); ?></a>
										<?php if( current_user_can('delete_events')) : ?>
										<span class="trash"><a href="<?php echo $url ?>?action=event_delete&amp;event_id=<?php echo $event->recurrence_id ?>" class="em-event-rec-delete" onclick ="if( !confirm('<?php echo $recurrence_delete_confirm; ?>') ){ return false; }"><?php _e('Delete','dbem'); ?></a></span>
										<?php endif; ?>										
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