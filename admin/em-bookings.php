<?php 
/**
 * Check if there's any admin-related actions to take for bookings. All actions are caught here.
 * @return null
 */
function em_admin_actions_bookings() {
  	global $dbem_form_add_message;   
	global $dbem_form_delete_message; 
	global $wpdb, $EM_Booking;
	
	if( current_user_can(EM_MIN_CAPABILITY) && is_object($EM_Booking) && !empty($_REQUEST['action']) ) {
		if( $_REQUEST['action'] == 'bookings_delete' ){
			//Delete
			if( isset($_POST['booking_id']) ){
				$EM_Booking = new EM_Booking($_POST['booking_id']);
				$EM_Booking->delete();
			}
		}elseif( $_REQUEST['action'] == 'bookings_edit' ){
			//Edit Booking
			$validation = $EM_Booking->get_post();
			if ( $validation ) { //EM_Event gets the event if submitted via POST and validates it (safer than to depend on JS)
				//Save
				if( $EM_Booking->save() ) {
					function em_booking_save_notification(){ global $EM_Booking; ?><div class="updated"><p><strong><?php echo $EM_Booking->feedback_message; ?></strong></p></div><?php }		
				}else{
					function em_booking_save_notification(){ global $EM_Booking; ?><div class="error"><p><strong><?php echo $EM_Booking->feedback_message; ?></strong></p></div><?php }
				}
			}else{
				//TODO make errors clearer when saving person
				function em_booking_save_notification(){ global $EM_Booking; ?><div class="error"><p><strong><?php echo $EM_Booking->feedback_message; ?></strong></p></div><?php }
			}
			add_action ( 'admin_notices', 'em_booking_save_notification' );
		}elseif( $_REQUEST['action'] == 'bookings_approve' || $_REQUEST['action'] == 'bookings_reject' || $_REQUEST['action'] == 'bookings_unapprove' ){
			//Booking Approvals
			$status_array = array('bookings_unapprove' => 0,'bookings_approve' => 1,'bookings_reject' => 2, 'bookings_cancel' => 3);
			if( $EM_Booking->set_status( $status_array[$_REQUEST['action']] ) ) {
				function em_booking_save_notification(){ global $EM_Booking; ?><div class="updated"><p><strong><?php echo $EM_Booking->feedback_message; ?></strong></p></div><?php }		
			}else{
				function em_booking_save_notification(){ global $EM_Booking; ?><div class="error"><p><strong><?php echo $EM_Booking->feedback_message; ?></strong></p></div><?php }
			}
			add_action ( 'admin_notices', 'em_booking_save_notification' );
		}
	}	
}
add_action('admin_init','em_admin_actions_bookings',100);

/**
 * Decide what content to show in the bookings section. 
 */
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
	//check that user can access this page
	if( is_object($EM_Event) && !$EM_Event->can_manage() ){
		?>
		<div class="wrap"><h2><?php _e('Unauthorized Access','dbem'); ?></h2><p><?php _e('You do not have the rights to manage this event.','dbem'); ?></p></div>
		<?php
		return false;
	}
	$localised_start_date = date_i18n('D d M Y', $EM_Event->start);
	$localised_end_date = date_i18n('D d M Y', $EM_Event->end);
	?>
	<div class='wrap'>
		<div id='icon-users' class='icon32'>
			<br/>
		</div>
  		<h2>
  			<?php echo sprintf(__('Manage %s Bookings', 'dbem'), "'{$EM_Event->name}'"); ?>
  			<a href="admin.php?page=events-manager-event&event_id=<?php echo $EM_Event->id; ?>" class="button add-new-h2"><?php _e('View/Edit Event','dbem') ?></a>
  		</h2>  
		<div>
			<p><strong><?php _e('Event Name','dbem'); ?></strong> : <?php echo ($EM_Event->name); ?></p>
			<p><strong>Availability :</strong> <?php echo $EM_Event->get_bookings()->get_booked_seats() . '/'. $EM_Event->seats ." ". __('Seats confirmed','dbem'); ?></p>
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
		<h2><?php _e('Cancelled Bookings','dbem'); ?></h2>
		<?php em_bookings_cancelled_table(); ?>
	</div>
	<?php
}

/**
 * Shows a single booking for a single person. 
 */
function em_bookings_single(){
	global $EM_Booking;
	//check that user can access this page
	if( is_object($EM_Booking) && !$EM_Booking->can_manage() ){
		?>
		<div class="wrap"><h2><?php _e('Unauthorized Access','dbem'); ?></h2><p><?php _e('You do not have the rights to manage this event.','dbem'); ?></p></div>
		<?php
		return false;
	}
	?>
	<div class='wrap'>
		<div id='icon-users' class='icon32'>
			<br/>
		</div>
  		<h2>
  			<?php _e('Edit Booking', 'dbem'); ?>
  		</h2>
  		<div id="poststuff" class="metabox-holder has-right-sidebar">
	  		<div id="post-body">
				<div id="post-body-content">
					<div id="event_name" class="stuffbox">
						<h3>
							<?php _e ( 'Booking Details', 'dbem' ); ?>
						</h3>
						<div class="inside">
							<?php em_bookings_edit_form(); ?>
						</div>
					</div> 
				</div>
			</div>
		</div>
		<br style="clear:both;" />
	</div>
	<?php
	
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
  			<?php _e('Manage Person\'s Booking', 'dbem'); ?>
  			<a href="admin.php?page=events-manager-bookings&action=person_delete&person_id=<?php echo $EM_Person->id; ?>" onclick="if( !confirm('<?php _e('Are you sure you want to delete this person? All bookings corresponding to this person will be deleted and this is not reversible.','dbem') ?>') ){ return false; }" class="button add-new-h2"><?php _e('Delete Person','dbem') ?></a>
  		</h2>
  		<div id="poststuff" class="metabox-holder has-right-sidebar">
	  		<div id="post-body">
				<div id="post-body-content">
					<div id="event_name" class="stuffbox">
						<h3>
							<?php _e ( 'Personal Details', 'dbem' ); ?>
						</h3>
						<div class="inside">
							<?php em_person_edit_form(); ?>
						</div>
					</div> 
				</div>
			</div>
		</div>
		<br style="clear:both;" />
		<h3><?php _e('Past And Present Bookings','dbem'); ?></h3>
		<?php em_bookings_person_table(); ?>
	</div>
	<?php
}

function em_bookings_edit_form(){
	global $EM_Booking;
	$EM_Event = new EM_Event($EM_Booking->event_id);
	$localised_start_date = date_i18n('D d M Y', $EM_Event->start);
	$localised_end_date = date_i18n('D d M Y', $EM_Event->end);
	?>
	<form action="" method="post" id="em-person-form">
		<h4>Event Details</h4>
		<table>
			<tr><td><strong><?php _e('Name','dbem'); ?></strong></td><td><a class="row-title" href="<?php bloginfo ( 'wpurl' )?>/wp-admin/admin.php?page=events-manager-bookings&amp;event_id=<?php echo $EM_Event->id ?>"><?php echo ($EM_Event->name); ?></a></td></tr>
			<tr>
				<td><strong><?php _e('Date/Time','dbem'); ?>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</strong></td>
				<td>
					<?php echo $localised_start_date; ?>
					<?php echo ($localised_end_date != $localised_start_date) ? " - $localised_end_date":'' ?>
					<?php echo substr ( $EM_Event->start_time, 0, 5 ) . " - " . substr ( $EM_Event->end_time, 0, 5 ); ?>
				</td>
			</tr>
		</table>
		<h4>Personal Details</h4>
		<table>
			<tr><td><strong><?php _e('Name','dbem'); ?> </strong></td><td><a href="<?php bloginfo ( 'wpurl' )?>/wp-admin/admin.php?page=events-manager-bookings&amp;person_id=<?php echo $EM_Booking->person_id; ?>"><?php echo $EM_Booking->person->name ?></a></td></tr>
			<tr><td><strong><?php _e('Phone','dbem'); ?> </strong></td><td><?php echo $EM_Booking->person->phone; ?></td></tr>
			<tr><td><strong><?php _e('E-mail','dbem'); ?>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</strong></td><td><?php echo $EM_Booking->person->email; ?></td></tr>
		</table>
		<h4>Booking Details</h4>
		<table>
			<tr><td><strong><?php _e('Spaces','dbem'); ?> </strong></td><td><input type="text" name="booking_seats" value="<?php echo $EM_Booking->seats; ?>" /></td></tr>
			<tr><td><strong><?php _e('Comment','dbem'); ?>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</strong></td><td><textarea name="booking_comment"><?php echo $EM_Booking->comment; ?></textarea></td></tr>
		</table>		
		<p class="submit">
			<input type="submit" name="events_update" value="<?php _e ( 'Save' ); ?> &raquo;" />
		</p>
		<input type="hidden" name="action" value="bookings_edit" />
		<input type="hidden" name="booking_id" value="<?php echo $EM_Booking->id; ?>" />
	</form>
	<?php
}
?>