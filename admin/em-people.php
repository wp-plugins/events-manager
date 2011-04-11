<?php
/**
 * Check for flags to save personal data 
 */
function em_person_actions(){
	global $EM_Person;
	if( !empty($_REQUEST['action']) && is_object($EM_Person) ){
		if( $_REQUEST['action'] == 'edit_person' ){
			$validation = $EM_Person->get_post();
			if ( $validation ) { //EM_Event gets the event if submitted via POST and validates it (safer than to depend on JS)
				//Save
				if( $EM_Person->save() ) {
					function em_person_save_notification(){
						global $EM_Person;
						?><div class="updated"><p><strong><?php echo $EM_Person->feedback_message; ?></strong></p></div><?php
					}		
				}else{
					function em_person_save_notification(){
						global $EM_Person;
						?><div class="error"><p><strong><?php echo $EM_Person->feedback_message; ?></strong></p></div><?php
					}
				}
			}else{
				//TODO make errors clearer when saving person
				function em_person_save_notification(){
					global $EM_Person;
					?><div class="error"><p><strong><?php echo $EM_Person->feedback_message; ?></strong></p></div><?php
				}
			}
			add_action ( 'admin_notices', 'em_person_save_notification' );
		}
		if( $_REQUEST['action'] == 'person_delete' ){
			if( $EM_Person->delete() ){
				//TODO delete person needs confirmation
				wp_redirect( get_bloginfo('wpurl').'/wp-admin/admin.php?page=events-manager-bookings');
				exit();
			}else{
				function em_person_delete_notification(){
					global $EM_Person;
					?><div class="error"><p><strong><?php echo $EM_Person->feedback_message; ?></strong></p></div><?php
				}
			}
			add_action ( 'admin_notices', 'em_person_delete_notification' );
		}		
	}
}
add_action('admin_init','em_person_actions');

/**
 * Generate an edit form for person details. 
 */
function em_person_edit_form(){
	global $EM_Person;
	?>
	<form action="" method="post" id="em-person-form">
		<table>
			<tr><td><strong><?php _e('Name','dbem'); ?></strong></td><td><input type="text" name="person_name" size="60" value="<?php echo $EM_Person->name; ?>" /></td></tr>
			<tr><td><strong><?php _e('Phone','dbem'); ?></strong></td><td><input type="text" name="person_phone" size="60" value="<?php echo $EM_Person->phone; ?>" /></td></tr>
			<tr><td><strong><?php _e('E-mail','dbem'); ?></strong></td><td><input type="text" name="person_email" size="60" value="<?php echo $EM_Person->email; ?>" /></td></tr>
		</table>
		<p class="submit">
			<input type="submit" name="events_update" value="<?php _e ( 'Save' ); ?> &raquo;" />
		</p>
		<input type="hidden" name="action" value="person_edit" />
		<input type="hidden" name="person_id" value="<?php echo $EM_Person->id; ?>" />
	</form>
	<?php
}

/**
 * Depreciated page... for now at least. 
 */
function em_admin_people_page() {
	?> 
	<div class='wrap'> 
		<div id="icon-users" class="icon32"><br/></div>
		<h2>People</h2>
		<?php
		$EM_People = EM_People::get();
		if (count($EM_People) < 1 ) {
			_e("No people have responded to your events yet!", 'dbem');
		} else { 
			?>
			<p><?php _e('This table collects the data about the people who responded to your events', 'dbem') ?></p>	
			<table id='dbem-people-table' class='widefat post fixed'>
				<thead>
					<tr>
						<th class='manage-column column-cb check-column' scope='col'>&nbsp;</th>
						<th class='manage-column ' scope='col'>Name</th>
						<th scope='col'>E-mail</th>
						<th scope='col'>Phone number</th>
				 </tr>
				</thead>
				<tfoot>
					<tr>
						<th class='manage-column column-cb check-column' scope='col'>&nbsp;</th>
						<th class='manage-column ' scope='col'>Name</th>
						<th scope='col'>E-mail</th>
						<th scope='col'>Phone number</th>
				 </tr>
				</tfoot>
				<?php foreach ($EM_People as $EM_Person): ?>
					<tr> 
						<td>&nbsp;</td>
						<td><?php echo $EM_Person->name ?></td>
						<td><?php echo $EM_Person->email ?></td>
						<td><?php echo $EM_Person->phone ?></td>
					</tr>
				<?php endforeach; ?>
			</table>
			<?php   
		}
		?>
	</div>
	<?php
} 

function em_printable_booking_report() {
	global $EM_Event;
	//check that user can access this page
	if( isset($_GET['page']) && $_GET['page']=='events-manager-bookings' && isset($_GET['action']) && $_GET['action'] == 'bookings_report' && is_object($EM_Event)){
		if( is_object($EM_Event) && !$EM_Event->can_manage() ){
			?>
			<div class="wrap"><h2><?php _e('Unauthorized Access','dbem'); ?></h2><p><?php _e('You do not have the rights to manage this event.','dbem'); ?></p></div>
			<?php
			return false;
		}
		?>
		<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
			"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
		<html>
		<head>
			<meta http-equiv="Content-type" content="text/html; charset=utf-8" />
			<title>Bookings for <?php echo $EM_Event->name; ?></title>
			<link rel="stylesheet" href="<?php echo bloginfo('wpurl') ?>/wp-content/plugins/events-manager/includes/css/events_manager.css" type="text/css" media="screen" />
		</head>
		<body id="printable">
			<div id="container">
			<h1>Bookings for <?php echo $EM_Event->name; ?></h1> 
			<p><?php echo $EM_Event->output("#d #M #Y"); ?></p>
			<p><?php echo $EM_Event->output("#_LOCATION, #_ADDRESS, #_TOWN"); ?></p>   
			<h2><?php _e('Bookings data', 'dbem');?></h2>
			<table id="bookings-table">
				<tr>
					<th scope='col'><?php _e('Name', 'dbem')?></th>
					<th scope='col'><?php _e('E-mail', 'dbem')?></th>
					<th scope='col'><?php _e('Phone number', 'dbem')?></th> 
					<th scope='col'><?php _e('Seats', 'dbem')?></th>
					<th scope='col'><?php _e('Comment', 'dbem')?></th>
				</tr> 
				<?php foreach($EM_Event->get_bookings()->bookings as $EM_Booking) {       ?>
				<tr>
					
					<td><?php echo $EM_Booking->person->name ?></td> 
					<td><?php echo $EM_Booking->person->email ?></td>
					<td><?php echo $EM_Booking->person->phone ?></td>
					<td class='seats-number'><?php echo $EM_Booking->seats ?></td>
					<td><?php echo $EM_Booking->comment ?></td> 
				</tr>
			   	<?php } ?>
			  	<tr id='booked-seats'>
					<td colspan='3'>&nbsp;</td>
					<td class='total-label'><?php _e('Booked', 'dbem')?>:</td>
					<td class='seats-number'><?php echo $EM_Event->get_bookings()->get_booked_seats(); ?></td>
				</tr>
				<tr id='available-seats'>
					<td colspan='3'>&nbsp;</td> 
					<td class='total-label'><?php _e('Available', 'dbem')?>:</td>  
					<td class='seats-number'><?php echo $EM_Event->get_bookings()->get_available_seats(); ?></td>
				</tr>
			</table>  
			</div>
		</body>
		</html>
		<?php
		die();
	}
} 
add_action('admin_init', 'em_printable_booking_report');

/**
 * Adds phone number to contact info of users, compatible with previous phone field method
 * @param $array
 * @return array
 */
function em_contact_methods($array){
	$array['dbem_phone'] = __('Phone','dbem') . ' <span class="description">('. __('Events Manager','dbem') .')</span>';
	return $array;
}
add_filter( 'user_contactmethods' , 'em_contact_methods' , 10 , 1 );

?>