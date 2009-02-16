<?php
function dbem_people_page() {
	// Managing AJAX booking removal
 	if(isset($_GET['action']) && $_GET['action'] == 'remove_booking') {
		if(isset($_POST['booking_id']))
			dbem_delete_booking($_POST['booking_id']);   
      
	}   
	?>  
	
	<div class='wrap'>
	<h2>People</h2>
	<p><?php _e('This table collects the data about the people who responded to your events'); ?>.
	<?php dbem_people_table(); ?>
	</div> 

	<?php
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
	
	if(isset($_GET['query']) && $_GET['query'] == 'GlobalMapData') { 
		dbem_global_map_json($_GET['eventful']);		
	 	die();   
 	}

   
}   

function dbem_global_map_json($eventful = false) {

	$json = '{"venues":[';
	$venues = dbem_get_venues($eventful);
	$json_venues = array();
	foreach($venues as $venue) {

		$json_venue = array();
		foreach($venue as $key => $value) {
		 	$json_venue[] = '"'.$key.'":"'.$value.'"';
		}
		$json_venues[] = "{".implode(",",$json_venue)."}";
	}        
	$json .= implode(",", $json_venues); 
	$json .= "]}" ;
	echo $json;
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

function dbem_people_table() {
	$people = dbem_get_people();
	
	$table =" <table id='dbem-people-table' class='widefat post fixed'>\n
							<thead>
								<tr>
									<th class='manage-column column-cb check-column' scope='col'>&nbsp;</th>\n
									<th class='manage-column ' scope='col'>Name</th>\n
									<th scope='col'>E-mail</th>\n
									<th scope='col'>Phone number</th>\n
							 </tr>\n
							</thead>\n
							<tfoot>
								<tr>
									<th class='manage-column column-cb check-column' scope='col'>&nbsp;</th>\n
									<th class='manage-column ' scope='col'>Name</th>\n
									<th scope='col'>E-mail</th>\n
									<th scope='col'>Phone number</th>\n
							 </tr>\n
							</tfoot>\n
			" ;
foreach ($people as $person) {
$table .= "<tr> <td>&nbsp;</td>
						<td>".$person['person_name']."</td>
						<td>".$person['person_email']."</td>
						<td>".$person['person_phone']."</td></tr>";
				}

$table .= "</table>";
	echo $table;
} 

function dbem_get_person_by_name_and_email($name, $email) {
	global $wpdb; 
	$people_table = $wpdb->prefix.PEOPLE_TBNAME;
	$sql = "SELECT person_id, person_name, person_email, person_phone FROM $people_table WHERE person_name = '$name' AND person_email = '$email' ;" ;
	$result = $wpdb->get_row($sql, ARRAY_A);
	return $result;
}

function dbem_get_person($person_id) {
	global $wpdb; 
	$people_table = $wpdb->prefix.PEOPLE_TBNAME;
	$sql = "SELECT person_id, person_name, person_email, person_phone FROM $people_table WHERE person_id = '$person_id';" ;
	$result = $wpdb->get_row($sql, ARRAY_A);
	return $result;
}

function dbem_get_people() {
	global $wpdb; 
	$people_table = $wpdb->prefix.PEOPLE_TBNAME;
	$sql = "SELECT *  FROM $people_table";    
	$result = $wpdb->get_results($sql, ARRAY_A);
	return $result;
}

function dbem_add_person($name, $email, $phone = "") {
	global $wpdb; 
	$people_table = $wpdb->prefix.PEOPLE_TBNAME;
	$sql = "INSERT INTO $people_table (person_name, person_email, person_phone) VALUES ('$name', '$email', '$phone');";
	$wpdb->query($sql);
	$new_person = dbem_get_person_by_name_and_email($name, $email);  
	return ($new_person);
}

?>