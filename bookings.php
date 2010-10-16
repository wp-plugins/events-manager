<?php
/* Contains functions to handle save and delete requests on the front-end for bookings */
global $dbem_form_messages_booking_delete;
$dbem_form_messages_booking_delete = array();
global $dbem_form_messages_booking_add;
$dbem_form_messages_booking_add = array();

/**
 * Check if there's any actions to take for bookings
 * @return null
 */
function em_actions_bookings() {
	global $dbem_form_messages_booking_delete, $dbem_form_messages_booking_add;
	global $wpdb;
	global $EM_Event;
	
	if( @get_class($EM_Event) == 'EM_Event' ){
		//ADD/EDIT Booking
		if (isset($_POST['eventAction']) && $_POST['eventAction'] == 'add_booking') {
			//$EM_Event->get_bookings();
			if( $EM_Event->get_bookings()->add( new EM_Booking($_POST) ) ){
				$dbem_form_messages_booking_add['success'] = $EM_Event->get_bookings()->feedback_message;
			}else{
				$dbem_form_messages_booking_add['error'] = implode('<br />', $EM_Event->get_bookings()->errors);
			}		
	  	}
	  	//DELETE Booking
		if (isset($_POST['eventAction']) && $_POST['eventAction'] == 'delete_booking') {
			$EM_Person = new EM_Person();
			if( $EM_Person->get(array('person_name' => $_POST['person_name'], 'person_email' => $_POST['person_email'])) ){
				$deleted = 0;
				foreach($EM_Event->get_bookings()->get_bookings() as $EM_Booking){
					if($EM_Booking->person->id == $EM_Person->id ){
						$EM_Booking->delete();
						$deleted++;
					}
				}
			}
			if($deleted > 0){
				$dbem_form_messages_booking_delete['success'] = __('Booking deleted', 'dbem');
			}else{
				$dbem_form_messages_booking_delete['error'] = __('There are no bookings associated to this name and e-mail', 'dbem');
			}
	  	}
	}
}   
add_action('init','em_actions_bookings');

/**
 * Returns the booking form for the front-end, displayed when using placeholder #_ADDBOOKINGFORM
 * @return string
 */
function em_add_booking_form() {                
	global $dbem_form_messages_booking_add, $EM_Event;
 
	$destination = "?".$_SERVER['QUERY_STRING']."#dbem-rsvp";
	$booked_places_options = array();
	for ( $i = 1; $i <= 10; $i++ ) {
		$booking_seats = ($_POST['booking_seats'] == $i) ? 'selected="selected"':'';
		array_push($booked_places_options, "<option value='$i' $booking_seats>$i</option>");
	}
	ob_start();
	?>
	<div id="dbem-rsvp">
		<a name="dbem-rsvp"></a>
		<h3><?php _e('Book now!','dbem') ?></h3>
		
		<?php if( !empty($dbem_form_messages_booking_add['success']) ) : ?>
		<div class='dbem-rsvp-message-success'><?php echo $dbem_form_messages_booking_add['success'] ?></div>
		<?php elseif( !empty($dbem_form_messages_booking_add['error']) ) : ?>
		<div class='dbem-rsvp-message-error'><?php echo $dbem_form_messages_booking_add['error'] ?></div>
		<?php elseif( !empty($dbem_form_messages_booking_add['message']) ) : ?>
		<div class='dbem-rsvp-message'><?php echo $dbem_form_messages_booking_add['message'] ?></div>
		<?php endif; ?>
		
		<form id='dbem-rsvp-form' name='booking-form' method='post' action='<?php echo $destination ?>'>
				<table class='dbem-rsvp-form'>
					<tr><th scope='row'><?php _e('Name', 'dbem') ?>:</th><td><input type='text' name='person_name' value='<?php echo $_POST['person_name'] ?>'/></td></tr>
					<tr><th scope='row'><?php _e('E-Mail', 'dbem') ?>:</th><td><input type='text' name='person_email' value='<?php echo $_POST['person_email'] ?>'/></td></tr>
					<tr><th scope='row'><?php _e('Phone number', 'dbem') ?>:</th><td><input type='text' name='person_phone' value='<?php echo $_POST['person_phone'] ?>'/></td></tr>
					<tr>
						<th scope='row'><?php _e('Seats', 'dbem') ?>:</th>
						<td>
							<select name='booking_seats' >
								<?php
								foreach($booked_places_options as $option) {
									echo $option ."\n";                  
								}
								?>
							</select>
						</td>
					</tr>
					<tr><th scope='row'><?php _e('Comment', 'dbem') ?>:</th><td><textarea name='booking_comment'><?php echo $_POST['booking_comment'] ?></textarea></td></tr>
			</table>
			<p>
				<input type='submit' value='<?php _e('Send your booking', 'dbem') ?>'/>&nbsp;&nbsp;&nbsp;&nbsp;
			 	<input type='hidden' name='eventAction' value='add_booking'/>
			 	<input type='hidden' name='event_id' value='<?php echo $EM_Event->id; ?>'/>
			</p>  
		</form>
	</div>
	<script type="text/javascript">
		jQuery(document).ready( function($){
			if( $('#dbem-booking-delete').size() > 0 ){
				var triggerText = '<?php ($_POST['eventAction'] == 'delete_booking') ? _e('Hide cancellation form', 'dbem') : _e('Cancel a booking', 'dbem'); ?>';
				$('#dbem-rsvp input[type=submit]').after(' <a href="#" id="dbem-booking-cancel">'+triggerText+'</a>');
				if( $('#dbem-booking-cancel').html() == '<?php _e('Cancel a booking', 'dbem'); ?>' ) { $('#dbem-booking-delete').hide(); }
				$('#dbem-booking-cancel').click( function(event){
					event.preventDefault();
					if( $('#dbem-booking-cancel').html() == '<?php _e('Cancel a booking', 'dbem'); ?>' ){
						$('#dbem-booking-cancel').html('<?php _e('Hide cancellation form', 'dbem'); ?>');
						$('#dbem-booking-delete').slideDown();
					}else{
						$('#dbem-booking-cancel').html('<?php _e('Cancel a booking', 'dbem'); ?>');
						$('#dbem-booking-delete').slideUp();
					}
				});
			}
		});
	</script>
	<?php
	return ob_get_clean();	
}

/**
 * Booking removal in front end, called by placeholder #_REMOVEBOOKINGFORM
 * @return string
 */
function em_delete_booking_form() {   
	global $dbem_form_messages_booking_delete, $EM_Event;	
	$destination = "?".$_SERVER['QUERY_STRING'];
	ob_start();
	?>
	<div id="dbem-booking-delete">
		<a name="dbem-booking-delete"></a>
		<h3><?php _e('Cancel your booking', 'dbem') ?></h3>
		
		<?php if( !empty($dbem_form_messages_booking_delete['success']) ) : ?>
		<div class='dbem-rsvp-message-success'><?php echo $dbem_form_messages_booking_delete['success'] ?></div>
		<?php elseif( !empty($dbem_form_messages_booking_delete['error']) ) : ?>
		<div class='dbem-rsvp-message-error'><?php echo $dbem_form_messages_booking_delete['error'] ?></div>
		<?php elseif( !empty($dbem_form_messages_booking_delete['message']) ) : ?>
		<div class='dbem-rsvp-message'><?php echo $dbem_form_messages_booking_delete['message'] ?></div>
		<?php endif; ?>
		
		<form name='booking-delete-form' method='post' action='<?php echo $destination ?>#dbem-booking-delete'>
			<table class='dbem-rsvp-form'>
				<tr>
					<th scope='row'><?php _e('Name', 'dbem') ?>:</th><td><input type='text' name='person_name' value='<?php echo $_POST['person_name'] ?>'/></td>
				</tr>
			  	<tr>
			  		<th scope='row'><?php _e('E-Mail', 'dbem') ?>:</th><td><input type='text' name='person_email' value='<?php echo $_POST['person_email'] ?>'/></td>
			  	</tr>
			</table>
			<input type='hidden' name='eventAction' value='delete_booking'/>
			 <input type='hidden' name='event_id' value='<?php echo $EM_Event->id; ?>'/>
			<input type='submit' value='<?php _e('Cancel your booking', 'dbem') ?>'/>
		</form>
	</div>
	<?php
	return ob_get_clean();	
}
?>