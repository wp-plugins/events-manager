<?php

/**
 * Determines whether to show event page or events page, and saves any updates to the event or events
 * @return null
 */
function dbem_events_subpanel() {
	//TODO Simplify panel for events, use form flags to detect certain actions (e.g. submitted, etc)
	global $wpdb;
	global $EM_Event;
	$action = $_GET ['action'];
	$action2 = $_GET ['action2'];
	$event_ID = $_GET ['event_id'];
	$recurrence_ID = $_GET ['recurrence_id'];
	$scope = ($_GET ['scope'] != '') ? $_GET['scope']:'future';
	$order = $_GET ['order']; //FIXME order not used consistently in admin area
	$selectedEvents = $_GET ['events'];
	
	if ($order == ""){
		$order = "ASC";
	}		
	$event_table_name = $wpdb->prefix . EM_EVENTS_TABLE;	

	// DELETE action
	if ( $action == 'deleteEvents' && EM_Object::array_is_numeric($selectedEvents) ) {
		EM_Events::delete( $selectedEvents );
		dbem_events_table ( EM_Events::get( array('scope'=>$scope) ), "Future events" );
	}
	// UPDATE or CREATE action
	if ($action == 'update_event') {
		
		if( !is_object($EM_Event) ){
			$EM_Event = new EM_Event();
		}
		$validation = $EM_Event->get_post();
		$title = ($EM_Event->is_recurring()) ? __( "Reschedule", 'dbem' )." '{$EM_Event->name}'" : "Edit event {$EM_Event->name}" ;	
		if ( $validation ) { //EM_Event gets the event if submitted via POST and validates it (safer than to depend on JS)
			//Save
			if( $EM_Event->save() ) {
				?>
				<div id='message' class='updated fade'>
					<p><?php echo $EM_Event->feedback_message ?></p>
				</div>
				<?php
				dbem_events_table ( EM_Events::get( array('limit'=>0,'scope'=>$scope) ), "Future events" );
			}else{
				// saving unsuccessful		
				?>
				<div id='message' class='error '>
					<p>
						<?php echo "<strong>" . __( "Ach, there's a problem here:", "dbem" ) . "</strong><br /><br />" .implode('<br />', $EM_Event->errors); ?>
					</p>
				</div>
				<?php
				dbem_event_form ( $title );
			}	
		} else {
			// validation unsuccessful			
			?>
			<div id='message' class='error '>
				<p><?php echo "<strong>" . __( "Ach, there's a problem here:", "dbem" ) . "</strong><br /><br />" . implode('<br />', $EM_Event->errors); ?></p>
			</div>
			<?php			
			dbem_event_form ( $title );		
		}
	}
	
	//Add or Edit Events
	if ($action == 'edit_event') {
		if( !is_object($EM_Event) ){
			$EM_Event = new EM_Event();
			$title = __ ( "Insert New Event", 'dbem' );
		} else {
			$title = __ ( "Edit Event", 'dbem' ) . " '" . $EM_Event->name . "'";
		}		
		//Generate Event Form
		dbem_event_form ( $title );	
	}
	
	//Copy the event
	if ($action == 'duplicate_event') {
		global $EZSQL_ERROR;
		if( $EM_Event->duplicate() ){
			//Now we edit the duplicated item
			$title = __ ( "Edit Event", 'dbem' ) . " '" . $EM_Event->name . "'";
			echo "<div id='message' class='updated below-h2'>You are now editing the duplicated event.</div>";
			dbem_event_form ( $title );
		}else{
			echo "<div class='error'><p>There was an error duplicating the event. Try again maybe?</div>";
			dbem_events_table ( EM_Events::get(array('limit'=>0,'scope'=>$scope)), $title );
		}
	}
	
	if ($action == "-1" || $action == "") {
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
		$events = EM_Events::get( array('scope'=>$scope, 'limit'=>0, 'order'=>$order ) );		
		dbem_events_table ( $events, $title );	
	}
}

function dbem_events_table($events, $title) {
	$limit = ( !empty($_GET['limit']) ) ? $_GET['limit'] : 20;//Default limit
	$page = ( !empty($_GET['p']) ) ? $_GET['p']:1;
	$offset = ( $page > 1 ) ? ($page-1)*$limit : 0;
	$scope_names = array (
		'past' => __ ( 'Past events', 'dbem' ),
		'all' => __ ( 'All events', 'dbem' ),
		'future' => __ ( 'Future events', 'dbem' )
	);
	$scope = ( array_key_exists( $_GET ['scope'], $scope_names) ) ? $_GET ['scope']:'future';
	$events_count = count ( $events );
	
	$use_events_end = get_option ( 'dbem_use_event_end' );
	?>
	<div class="wrap">
		<div id="icon-events" class="icon32"><br />
		</div>
		<h2><?php echo $title; ?></h2>
		<?php
			em_hello_to_new_user ();
				
			$link = array ();
			$link ['past'] = "<a href='" . get_bloginfo ( 'wpurl' ) . "/wp-admin/edit.php?page=events-manager/events-manager.php&amp;scope=past&amp;order=desc'>" . __ ( 'Past events', 'dbem' ) . "</a>";
			$link ['all'] = " <a href='" . get_bloginfo ( 'wpurl' ) . "/wp-admin/edit.php?page=events-manager/events-manager.php&amp;scope=all&amp;order=desc'>" . __ ( 'All events', 'dbem' ) . "</a>";
			$link ['future'] = "  <a href='" . get_bloginfo ( 'wpurl' ) . "/wp-admin/edit.php?page=events-manager/events-manager.php&amp;scope=future'>" . __ ( 'Future events', 'dbem' ) . "</a>";
		?> 
				
		<form id="posts-filter" action="" method="get"><input type='hidden' name='page' value='events-manager/events-manager.php' />
			<ul class="subsubsub">
				<li><a href='edit.php' class="current"><?php _e ( 'Total', 'dbem' ); ?> <span
					class="count">(<?php echo (count ( $events )); ?>)</span></a></li>
			</ul>
			
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
					<?php 
						//Pagination (if needed/requested)
						if( $events_count >= $limit ){
							//Show the pagination links (unless there's less than 10 events
							$page_link_template = preg_replace('/p=\d+/i','',$_SERVER['REQUEST_URI']);
							$page_link_template = em_add_get_params($page_link_template, array('p'=>'%PAGE%'));
							$events_nav = em_paginate( $page_link_template, $events_count, $limit, $page);
							echo $events_nav;
						}
					?>
				</div>
				<div class="clear"></div>
				
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
				  	  	$i = 1;
				  	  	$rowno = 0;
						foreach ( $events as $event ) {
							if( $i >= $offset && $i <= $offset+$limit ) {
								$rowno++;
								$class = ($rowno % 2) ? ' class="alternate"' : '';
								// FIXME set to american
								$localised_start_date = mysql2date ( __ ( 'D d M Y' ), $event->start_date );
								$localised_end_date = mysql2date ( __ ( 'D d M Y' ), $event->end_date );
								$style = "";
								$today = date ( "Y-m-d" );
								$location_summary = "<b>" . $event->location->name . "</b><br/>" . $event->location->address . " - " . $event->location->town;
								$category = EM_Category::get($event->id);
								
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
										<a class="row-title" href="<?php bloginfo ( 'wpurl' )?>/wp-admin/edit.php?page=events-manager/events-manager.php&amp;action=edit_event&amp;event_id=<?php echo $event->id ?>"><?php echo ($event->name); ?></a>
										</strong>
										<?php if($category) : ?>
										<br/><span title='<?php _e( 'Category', 'dbem' ).": ".$category['category_name'] ?>'><?php $category['category_name'] ?></span> 
										<?php endif; ?>
									</td>
									<td>
							 	    	<a href="<?php bloginfo ( 'wpurl' )?>/wp-admin/edit.php?page=events-manager/events-manager.php&amp;action=duplicate_event&amp;event_id=<?php echo $event->id; ?>" title="<?php _e ( 'Duplicate this event', 'dbem' ); ?>">
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
											<a href="<?php bloginfo ( 'wpurl' )?>/wp-admin/edit.php?page=events-manager/events-manager.php&amp;action=edit_event&amp;event_id=<?php echo $event->recurrence_id ?>"><?php _e ( 'Reschedule', 'dbem' ); ?></a>
											</strong>
											<?php
										}
										?>
									</td>
								</tr>
								<?php
							}
							$i ++;
						}
						?>
					</tbody>
				</table>  
				<?php
				} // end of table
				?>
				
				<div class='tablenav'>
					<div class="alignleft actions">
						<?php echo $events_nav; ?>
					<br class='clear' />
					</div>
					<br class='clear' />
				</div>
			</div>
		</form>		
	</div>
	<?php
}

?>