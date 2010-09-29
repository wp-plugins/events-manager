<?php 
/**
 * Check if there's any actions to take for bookings
 * @return null
 */
function dbem_admin_actions_bookings() {
  	global $dbem_form_add_message;   
	global $dbem_form_delete_message; 
	global $wpdb;
		
  	//DELETE Bookings
	if( $_POST['secondaryAction'] == 'delete_bookings' ){
		if( EM_Object::array_is_numeric($_GET['bookings']) && count($_GET['bookings']) > 0 ){
			$bookings = $_GET['bookings'];
			$sql = "DELETE FROM ". $wpdb->prefix.BOOKINGS_TBNAME ." WHERE booking_id = ". implode(' OR booking_id = ', $bookings);
			$wpdb->query($sql);
			$dbem_form_delete_message = __('Bookings deleted', 'dbem');
		}
	}
}
add_action('init','dbem_admin_actions_bookings');

/**
 * Shows table of bookings for an event
 * @return null
 */
function dbem_bookings_table() {
	global $EM_Event; 
	?>
	<form id='bookings-filter' method='get' action='<?php bloginfo('wpurl') ?>/wp-admin/edit.php'>
		<input type='hidden' name='page' value='events-manager/events-manager.php'/>
		<input type='hidden' name='action' value='edit_event'/>
		<input type='hidden' name='event_id' value='<?php echo $EM_Event->id ?>'/>
		<input type='hidden' name='secondaryAction' value='delete_bookings'/>
		<div class='wrap'>
			<h2>Bookings</h2>
			<table id='dbem-bookings-table' class='widefat post fixed'>
				<thead>
					<tr>
						<th class='manage-column column-cb check-column' scope='col'>&nbsp;</th>
						<th class='manage-column ' scope='col'>Booker</th>
						<th scope='col'>E-mail</th>
						<th scope='col'>Phone number</th>
						<th scope='col'>Seats</th>
					</tr>
				</thead>
				<tbody>
					<?php 
					foreach ($EM_Event->bookings->bookings as $EM_Booking) {
						?>
						<tr>
							<td><input type='checkbox' value='<?php echo $EM_Booking->id ?>' name='bookings[]'/></td>
							<td><?php echo $EM_Booking->name ?></td>
							<td><?php echo $EM_Booking->email ?></td>
							<td><?php echo $EM_Booking->phone ?></td>
							<td><?php echo $EM_Booking->seats ?></td>
						</tr>
						<?php
					}
					?>
				</tbody>
				<tfoot>
					<tr>
						<th scope='row' colspan='4'>Booked seats:</th>
						<td class='booking-result' id='booked-seats'><?php echo $EM_Event->bookings->get_booked_seats() ?></td>
					</tr>            
					<tr>
						<th scope='row' colspan='4'>Available seats:</th>
						<td class='booking-result' id='available-seats'><?php echo $EM_Event->bookings->get_booked_seats() ?></td>
					</tr>
				</tfoot>
			</table>
		</div>
		<div class='tablenav'>
			<div class='alignleft actions'>
			 <input class=button-secondary action' type='submit' name='doaction2' value='Delete'/>
				<br class='clear'/>
			</div>
			<br class='clear'/>
	 	</div>
	</form>
	<?php
}
?>