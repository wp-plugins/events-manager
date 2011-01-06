<?php 
/**
 * Check if there's any admin-related actions to take for bookings. All actions are caught here.
 * @return null
 */
function em_admin_actions_bookings() {
  	global $dbem_form_add_message;   
	global $dbem_form_delete_message; 
	global $wpdb;

	if( current_user_can(EM_MIN_CAPABILITY) && !empty($_REQUEST['page']) && $_REQUEST['page'] == 'events-manager-bookings') {
		if( $_REQUEST['action'] == 'remove_booking' ){
			if( isset($_POST['booking_id']) ){
				$EM_Booking = new EM_Booking($_POST['booking_id']);
				$EM_Booking->delete();
			}
		}
	}	
}
add_action('init','em_admin_actions_bookings');

function em_bookings_page(){
	//First any actions take priority
	if( !empty($_REQUEST['booking_id']) ){
		em_bookings_single();
	}elseif( !empty($_REQUEST['person_id']) ){
		em_bookings_person();
	}elseif( !empty($_REQUEST['event_id']) ){
		em_bookings_event();
	}else{
		em_bookings_dashboard();
	}
}

/**
 * Generates the bookings dashboard, showing information on all events 
 */
function em_bookings_dashboard(){
	?>
	<div class='wrap'>
		<div id='icon-users' class='icon32'>
			<br/>
		</div>
  		<h2>
  			<?php _e('Event Bookings Dashboard', 'dbem'); ?>
  		</h2>
		<h2><?php _e('Pending Bookings','dbem'); ?></h2>
		<?php em_bookings_pending_table(); ?>
		<h2><?php _e('Events With Bookings Enabled','dbem'); ?></h2>		
		<?php em_bookings_events_table(); ?>
	</div>
	<?php		
}

/**
 * Shows all booking data for a single event 
 */
function em_bookings_event(){
	global $EM_Event,$EM_Person;
	$localised_start_date = date_i18n('D d M Y', $EM_Event->start);
	$localised_end_date = date_i18n('D d M Y', $EM_Event->end);
	?>
	<div class='wrap'>
		<div id='icon-users' class='icon32'>
			<br/>
		</div>
  		<h2>
  			<?php echo sprintf(__('Manage %s Bookings', 'dbem'), "'{$EM_Event->name}'"); ?>
  			<a href="admin.php?page=events-manager-events&event_id=<?php echo $EM_Event->id; ?>" class="button add-new-h2"><?php _e('View/Edit Event','dbem') ?></a>
  		</h2>  
		<div>
			<p>
				<strong><?php _e('Event Name','dbem'); ?></strong> : <?php echo ($EM_Event->name); ?>
			</p>
			<p>
				<strong><?php _e('Date','dbem'); ?></strong> : 
				<?php echo $localised_start_date; ?>
				<?php echo ($localised_end_date != $localised_start_date) ? " - $localised_end_date":'' ?>
				<?php echo substr ( $EM_Event->start_time, 0, 5 ) . " - " . substr ( $EM_Event->end_time, 0, 5 ); ?>							
			</p>
			<p>
				<strong><?php _e('Location','dbem'); ?></strong> :
				<a class="row-title" href="<?php bloginfo ( 'wpurl' )?>/wp-admin/admin.php?page=events-manager-locations&amp;location_id=<?php echo $EM_Event->location->id ?>"><?php echo ($EM_Event->location->name); ?></a> 
			</p>
		</div>
		<h2><?php _e('Pending Bookings','dbem'); ?></h2>
		<?php em_bookings_pending_table(); ?>
		<h2><?php _e('Confirmed Bookings','dbem'); ?></h2>
		<?php em_bookings_confirmed_table(); ?>
		<h2><?php _e('Rejected Bookings','dbem'); ?></h2>
		<?php em_bookings_rejected_table(); ?>
	</div>
	<?php
}

/**
 * Shows a single booking for a single person. 
 */
function em_bookings_single(){
	
}

/**
 * Shows all bookings made by one person.
 */
function em_bookings_person(){	
	global $EM_Person;
	?>
	<div class='wrap'>
		<div id='icon-users' class='icon32'>
			<br/>
		</div>
  		<h2>
  			<?php _e('Manage Person\'s Bookings', 'dbem'); ?>
  			<a href="admin.php?page=events-manager-bookings&person_id=<?php echo $EM_Person->id; ?>" class="button add-new-h2"><?php _e('View/Edit Person','dbem') ?></a>
  		</h2>  
		<div>
			<p><strong><?php _e('Name','dbem'); ?></strong> : <?php echo $EM_Person->name; ?></p>
			<p><strong><?php _e('Phone','dbem'); ?></strong> : <?php echo $EM_Person->phone; ?></p>
			<p><strong><?php _e('E-mail','dbem'); ?></strong> : <?php echo $EM_Person->email; ?></p>
		</div>
		<h3><?php _e('Past And Present Bookings','dbem'); ?></h3>
		<?php em_bookings_person_table(); ?>
	</div>
	<?php
		
}
?>