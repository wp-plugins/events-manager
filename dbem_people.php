<?php
function dbem_people_page() {
	// Managing AJAX booking removal
 	if(isset($_GET['action']) && $_GET['action'] == 'remove_booking') {
		if(isset($_POST['booking_id']))
			dbem_delete_booking($_POST['booking_id']);   
      
	}
	  
	// if(isset($_GET['dbem_ajax_action']) && $_GET['dbem_ajax_action'] == 'booking_data') {
	// 	if(isset($_GET['event_id']))   
	//      echo "[ {bookedSeats:".dbem_get_booked_seats($_GET['event_id']).", availableSeats:".dbem_get_available_seats($_GET['event_id'])."}]";   
	// }   
}    



add_action('init','dbem_ajax_actions'); 
function dbem_ajax_actions() {
 	if(isset($_GET['dbem_ajax_action']) && $_GET['dbem_ajax_action'] == 'booking_data') {
		if(isset($_GET['id']))   
     echo "[ {bookedSeats:".dbem_get_booked_seats($_GET['id']).", availableSeats:".dbem_get_available_seats($_GET['id'])."}]"; 
	die();  
	}  
	if(isset($_GET['action']) && $_GET['action'] == 'printable'){
		if(isset($_GET['event_id']))
			dbem_printable_booking_report($_GET['event_id']);
	}  
		
	  
}   

function dbem_printable_booking_report($event_id) {
	$event = dbem_get_event($event_id);
	$bookings =  dbem_get_bookings_for($event_id);
	$available_seats = dbem_get_available_seats($event_id);
	$booked_seats = dbem_get_booked_seats($event_id);   
	$stylesheet = get_bloginfo('url')."/wp-content/plugins/events-manager/events_manager.css";
	?>
		<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
			"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
		<html>
		<head>
			<meta http-equiv="Content-type" content="text/html; charset=utf-8">
			<title>Bookings for <?php echo $event['event_name'];?></title>
			 <link rel="stylesheet" href="<?php echo $stylesheet; ?>" type="text/css" media="screen" />
			
		</head>
		<body id="printable">
			<div id="container">
			<h1>Bookings for <?php echo $event['event_name'];?></h1> 
			<p><?php echo dbem_replace_placeholders("#d #M #Y", $event)?></p>
			<p><?php echo dbem_replace_placeholders("#_VENUE, #_ADDRESS, #_TOWN", $event)?></p>   
			<h2><?php _e('Bookings data', 'dbem');?></h2>
			<table id="bookings-table">
				<tr>
					<th scope='col'><?php _e('Name', 'dbem')?></th>
					<th scope='col'><?php _e('E-mail', 'dbem')?></th>
					<th scope='col'><?php _e('Phone number', 'dbem')?></th> 
					<th scope='col'><?php _e('Seats', 'dbem')?></th> 
				<?php
				foreach($bookings as $booking) {       ?>
				<tr>
					
					<td><?php echo $booking['person_name']?></td> 
					<td><?php echo $booking['person_email']?></td>
					<td><?php echo $booking['person_phone']?></td>
					<td class='seats-number'><?php echo $booking['booking_seats']?></td> 
				</tr>
			   <?php } ?>
			  	<tr id='booked-seats'>
					<td colspan='2'>&nbsp;</td>
					<td class='total-label'><?php _e('Booked', 'dbem')?>:</td>
					<td class='seats-number'><?php echo $booked_seats; ?></td>
				</tr>
				<tr id='available-seats'>
					<td colspan='2'>&nbsp;</td> 
					<td class='total-label'><?php _e('Available', 'dbem')?>:</td>  
					<td class='seats-number'><?php echo $available_seats; ?></td>
				</tr>
			</table>  
			</div>
		</body>
		</html>
		<?php
		die();
 		
}
?>