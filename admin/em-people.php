<?php

function em_printable_booking_report() {
	global $EM_Event;
	//check that user can access this page
	if( isset($_GET['page']) && $_GET['page']=='events-manager-bookings' && isset($_GET['action']) && $_GET['action'] == 'bookings_report' && is_object($EM_Event)){
		if( is_object($EM_Event) && !$EM_Event->can_manage('edit_events','edit_others_events') ){
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
					<th scope='col'><?php _e('Spaces', 'dbem')?></th>
					<th scope='col'><?php _e('Comment', 'dbem')?></th>
				</tr> 
				<?php foreach($EM_Event->get_bookings()->bookings as $EM_Booking) {       ?>
				<tr>
					
					<td><?php echo $EM_Booking->person->display_name ?></td> 
					<td><?php echo $EM_Booking->person->user_email ?></td>
					<td><?php echo $EM_Booking->person->phone ?></td>
					<td class='spaces-number'><?php echo $EM_Booking->get_spaces() ?></td>
					<td><?php echo $EM_Booking->comment ?></td> 
				</tr>
			   	<?php } ?>
			  	<tr id='booked-spaces'>
					<td colspan='3'>&nbsp;</td>
					<td class='total-label'><?php _e('Booked', 'dbem')?>:</td>
					<td class='spaces-number'><?php echo $EM_Event->get_bookings()->get_booked_spaces(); ?></td>
				</tr>
				<tr id='available-spaces'>
					<td colspan='3'>&nbsp;</td> 
					<td class='total-label'><?php _e('Available', 'dbem')?>:</td>  
					<td class='spaces-number'><?php echo $EM_Event->get_bookings()->get_available_spaces(); ?></td>
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