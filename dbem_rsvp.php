<?php
$form_add_message = "";        
$form_delete_message = "";
function dbem_add_booking_form() {                
	global $form_add_message;
	//$message = dbem_catch_rsvp();
 
	$destination = "?".$_SERVER['QUERY_STRING']."#dbem-rsvp-form";
	$module = "<h3>RSVP module</h3><br/>";
	if(!empty($form_add_message))
		$module .= "<div class='dbem-rsvp-message'>$form_add_message</div>";
	$booked_places_options = array();
	for ( $i = 1; $i <= 10; $i++) 
		array_push($booked_places_options, "<option value='$i'>$i</option>");
	
	$module  .= "<form id='dbem-rsvp-form' name='booking-form' method='post' action='$destination'>
			<table class='dbem-rsvp-form'>
				<tr><th scope='row'>Name:</th><td><input type='text' name='bookerName' value='John Doe'/></td></tr>
		  	<tr><th scope='row'>E-mail:</th><td><input type='text' name='bookerEmail' value='jondoe@donjoe.com'/></td></tr>
		  	<tr><th scope='row'>Phone number:</th><td><input type='text' name='bookerPhone' value='000 000 000'/></td></tr>
				<tr><th scope='row'>How many seats?</th><td><select name='bookedSeats' >";
		  foreach($booked_places_options as $option) {
				$module .= $option."\n";                  
			}
	 	$module .= "</select></td></tr>
		</table>
		<input type='submit' value='Send your booking'/>   
		 <input type='hidden' name='eventAction' value='add_booking'/>  
	</form>";   
	// $module .= "dati inviati: ";
	//  	$module .= $_POST['bookerName'];  
	//print_r($_SERVER);
 
	//$module .= dbem_delete_booking_form();
	 
	return $module;
	
}

function dbem_delete_booking_form() {                
	global $form_delete_message;
	
	$destination = "?".$_SERVER['QUERY_STRING'];
	$module = "<h3>Delete module</h3><br/>";       
	
	if(!empty($form_delete_message))
		$module .= "<div class='dbem-rsvp-message'>$form_delete_message</div>";

	
	$module  .= "<form name='booking-delete-form' method='post' action='$destination'>
			<table class='dbem-rsvp-form'>
				<tr><th scope='row'>Name:</th><td><input type='text' name='bookerName' value='John Doe'/></td></tr>
		  	<tr><th scope='row'>E-mail:</th><td><input type='text' name='bookerEmail' value='jondoe@donjoe.com'/></td></tr>
		  	<input type='hidden' name='eventAction' value='delete_booking'/>
		</table>
		<input type='submit' value='Delete your booking'/>
	</form>";   
	// $module .= "dati inviati: ";
	//  	$module .= $_POST['bookerName'];  

	
	
	return $module;
	
}


function dbem_catch_rsvp() {
  global $form_add_message;   
	global $form_delete_message;
	if (isset($_POST['eventAction']) && $_POST['eventAction'] == 'add_booking') { 
		$result = dbem_book_seats();
		$form_add_message = $result;
	  
		
  } 

	if (isset($_POST['eventAction']) && $_POST['eventAction'] == 'delete_booking') { 
		
		$bookerName = $_POST['bookerName'];
		$bookerEmail = $_POST['bookerEmail'];
		$booker = dbem_get_person_by_name_and_email($bookerName, $bookerEmail); 
	  if ($booker) {
			$booker_id = $booker['person_id'];
			dbem_log("cancellare: ".$booker_id);  
			$booking = dbem_get_booking_by_person_id($booker_id);
			dbem_log($booking);
			$result = dbem_delete_booking($booking['booking_id']);
		} else {
			$result = __('There are no bookings associated to this name and e-mail', 'dbem');
		}
		$form_delete_message = $result; 
  } 
	
	return $result;
	
}   
add_action('init','dbem_catch_rsvp');  


 
function dbem_book_seats() {
	$bookerName = $_POST['bookerName'];
	$bookerEmail = $_POST['bookerEmail'];
	$bookerPhone = $_POST['bookerPhone']; 
	$bookedSeats = $_POST['bookedSeats'];   
	$event_id = $_GET['event_id'];
	$booker = dbem_get_person_by_name_and_email($bookerName, $bookerEmail); 
	if (!$booker) {
   	$booker = dbem_add_person($bookerName, $bookerEmail, $bookerPhone);
	}
	if (dbem_are_seats_available_for($event_id, $bookedSeats)) {
		dbem_record_booking($event_id, $booker['person_id'], $bookedSeats);
		
		$result = __('Your booking has been recorded','dbem');  
		$mailing_is_active = get_option('dbem_rsvp_mail_notify_is_active');
		if($mailing_is_active) {
			dbem_log("Ecco, mail in  fase di invio"); 
			dbem_email_rsvp_booking();
		} 
		
	}	else {
		 $result = __('Sorry, there aren\'t so many seats available!', 'dbem');
	}  
	return $result;
}

         

function dbem_get_booking_by_person_id($person_id) {
	global $wpdb; 
	$bookings_table = $wpdb->prefix.BOOKINGS_TBNAME;
	$sql = "SELECT * FROM $bookings_table WHERE person_id = '$person_id';" ;
	$result = $wpdb->get_row($sql, ARRAY_A);
	return $result;
}

function dbem_record_booking($event_id, $person_id, $seats) {
	global $wpdb;        
	$bookings_table = $wpdb->prefix.BOOKINGS_TBNAME;
	// checking whether the booker has already booked places
	$sql = "SELECT * FROM $bookings_table WHERE event_id = '$event_id' and person_id = '$person_id'; ";       
	echo $sql;
	$previously_booked = $wpdb->get_row($sql);
	if ($previously_booked) {  
		  
		$total_booked_seats = $previously_booked->booking_seats + $seats;
		$where = array();
		$where['booking_id'] =$previously_booked->booking_id;
		$fields['booking_seats'] = $total_booked_seats;
	 	$wpdb->update($bookings_table, $fields, $where);
		
	} else {
		if(true) {
			$sql = "INSERT INTO $bookings_table (event_id, person_id, booking_seats) VALUES ($event_id, $person_id, $seats)";
			$wpdb->query($sql);
			echo $sql;
		}  
	}
} 
function dbem_delete_booking($booking_id) {
	global $wpdb;
	$bookings_table = $wpdb->prefix.BOOKINGS_TBNAME; 
	$sql = "DELETE FROM $bookings_table WHERE booking_id = $booking_id";
	$wpdb->query($sql);   
	dbem_log('booking deleted!!!');    
	return __('Booking deleted', 'dbem');
}

function dbem_get_available_seats($event_id) {
	global $wpdb; 
	$bookings_table = $wpdb->prefix.BOOKINGS_TBNAME;
	$sql = "SELECT SUM(booking_seats) AS booked_seats FROM $bookings_table WHERE event_id = $event_id"; 
	$seats_row = $wpdb->get_row($sql, ARRAY_A);  
	$booked_seats = $seats_row['booked_seats'];
	$event = dbem_get_event($event_id);
	$available_seats = $event['event_seats'] - $booked_seats;
	return ($available_seats);  
}  
function dbem_get_booked_seats($event_id) {
	global $wpdb; 
	$bookings_table = $wpdb->prefix.BOOKINGS_TBNAME;
	$sql = "SELECT SUM(booking_seats) AS booked_seats FROM $bookings_table WHERE event_id = $event_id"; 
	$seats_row = $wpdb->get_row($sql, ARRAY_A);  
	$booked_seats = $seats_row['booked_seats'];
	return $booked_seats;  
}  
function dbem_are_seats_available_for($event_id, $seats) {     
	$event = dbem_get_event($event_id);   
	$available_seats = dbem_get_available_seats($event_id);  
	$remaning_seats = $available_seats - $seats;
	return ($remaning_seats >= 0);
} 
      
function dbem_bookings_table($event_id) {

	$bookings =  dbem_get_bookings_for($event_id);
	$destination = get_bloginfo('url')."/wp-admin/edit.php"; 
	$table = "<form id='bookings-filter' method='get' action='$destination'>
						<input type='hidden' name='page' value='events-manager/events-manager.php'/>
						<input type='hidden' name='action' value='edit_event'/>
						<input type='hidden' name='event_id' value='$event_id'/>
						<input type='hidden' name='secondaryAction' value='delete_bookings'/>
						<div class='wrap'>
							<h2>Bookings</h2>\n
						<table id='dbem-bookings-table' class='widefat post fixed'>\n";
	$table .="<thead>\n
							<tr><th class='manage-column column-cb check-column' scope='col'>&nbsp;</th><th class='manage-column ' scope='col'>Booker</th><th scope='col'>E-mail</th><th scope='col'>Phone number</th><th scope='col'>Seats</th></tr>\n
						</thead>\n" ;
	foreach ($bookings as $booking) {
		$table .= "<tr> <td><input type='checkbox' value='".$booking['booking_id']."' name='bookings[]'/></td>
										<td>".$booking['person_name']."</td>
										<td>".$booking['person_email']."</td>
										<td>".$booking['person_phone']."</td>
										<td>".$booking['booking_seats']."</td></tr>";
	}
	$available_seats = dbem_get_available_seats($event_id);
	$booked_seats = dbem_get_booked_seats($event_id);
	$table .= "<tfoot><tr><th scope='row' colspan='4'>Booked seats:</th><td class='booking-result' id='booked-seats'>$booked_seats</td></tr>            
						 <tr><th scope='row' colspan='4'>Available seats:</th><td class='booking-result' id='available-seats'>$available_seats</td></tr></tfoot>
							</table></div>
							<div class='tablenav'>
								<div class=alignleft actions>
								 <input class=button-secondary action' type='submit' name='doaction2' value='Delete'/>
									<br class='clear'/>
								</div>
								<br class='clear'/>
						 	</div>

						</form>";    
  echo $table;
}

function dbem_bookings_compact_table($event_id) {

	$bookings =  dbem_get_bookings_for($event_id);
	$destination = get_bloginfo('url')."/wp-admin/edit.php"; 
	$available_seats = dbem_get_available_seats($event_id);
	$booked_seats = dbem_get_booked_seats($event_id);   
	$printable_address = get_bloginfo('url')."/wp-admin/admin.php?page=people&action=printable&event_id=$event_id";
	if (count($bookings)>0) { 
		$table = 
		"<div class='wrap'>
				<h4>".__('Reservations')."</h4>\n  
			  
				<table id='dbem-bookings-table-$event_id' class='widefat post fixed'>\n
					<thead>\n
						<tr>
							<th class='manage-column column-cb check-column' scope='col'>&nbsp;</th>\n
							<th class='manage-column ' scope='col'>".__('Responder', 'dbem')."</th>\n
							<th scope='col'>Seats</th>\n
					 	</tr>\n
						</thead>\n
						<tfoot>
							<tr>
								<th scope='row' colspan='2'>Booked seats:</th><td class='booking-result' id='booked-seats'>$booked_seats</td></tr>            
					 			<tr><th scope='row' colspan='2'>Available seats:</th><td class='booking-result' id='available-seats'>$available_seats</td>
							</tr>
						</tfoot>
						<tbody>" ;
			foreach ($bookings as $booking) {
				$table .= 
				"<tr id='booking-".$booking['booking_id']."'> 
					<td><a id='booking-check-".$booking['booking_id']."' class='bookingdelbutton'>X</a></td>
					<td><a title='".$booking['person_email']." - ".$booking['person_phone']."'>".$booking['person_name']."</a></td>
					<td>".$booking['booking_seats']."</td>
				 </tr>";
			}
	 
			$table .=  "</tbody>\n
									
		 			</table>
		 		</div>
		 		
		 	    <br class='clear'/>
		 		 	<div id='major-publishing-actions'>  
					<div id='publishing-action'> 
					<a id='printable'  target='' href='$printable_address'>".__('Printable view','dbem')."</a>
					<br class='clear'/>             
	        
					 
		 			</div>
		<br class='clear'/>    
		 </div> ";                                                        
		 } else {
			$table .= "<p><em>".__('No bookings yet!')."</em></p>";
		 } 
		    
  echo $table;
}

function dbem_get_bookings_for($event_id) {  
	global $wpdb; 
	$bookings_table = $wpdb->prefix.BOOKINGS_TBNAME;
	$sql = "SELECT * FROM $bookings_table WHERE event_id = $event_id";
	$bookings = $wpdb->get_results($sql, ARRAY_A);  
	$booking_data = array();
	if ($bookings) {
		foreach ($bookings as $booking) {  
			$booking;
			$person = dbem_get_person($booking['person_id']);
			$booking['person_name'] = $person['person_name']; 
			$booking['person_email'] = $person['person_email'];   
			$booking['person_phone'] = $person['person_phone'];
			array_push($booking_data, $booking);
		}
 		return $booking_data;
  } else {
	return null;
	}
}       

function dbem_intercept_bookings_delete() {
	//dbem_email_rsvp_booking();
	$bookings = $_GET['bookings'];  
	
	if ($bookings) {
		foreach($bookings as $booking_id) {
			dbem_delete_booking($booking_id);
		}
	}
}
add_action('init', 'dbem_intercept_bookings_delete');   

function dbem_email_rsvp_booking(){  
	dbem_log("mail...");
	$booker = array();
	$bookerName = $_POST['bookerName'];
	$bookerEmail = $_POST['bookerEmail'];    
	$bookedSeats = $_POST['bookedSeats'];      
	
	$subject = "New booking!";
 	$body = "$bookerName ($bookerEmail) will attend this event. He wants to reserve $bookedSeats seats.";
	dbem_send_mail($subject, $body);
	// 
	// $mailer = new PHPMailer();
	// $mailer->IsSMTP();
	// $mailer->Host = 'ssl://smtp.gmail.com:465';
	// $mailer->SMTPAuth = TRUE;
	// $mailer->Username = get_option('dbem_smtp_username');  
	// // Change this to your gmail adress
	// $mailer->Password = get_option('dbem_smtp_password');  
	// // Change this to your gmail password
	// $mailer->From = get_option('dbem_mail_sender_address');  
	// // This HAVE TO be your gmail adress
	// $mailer->FromName = 'Events Manager Abuzzese'; // This is the from name in the email, you can put anything you like here
	// $mailer->Body = "$bookerName ($bookerEmail) will attend this event.";
	// $mailer->Subject = 'Hey hey, people\'s booking!';
	// $mailer->AddAddress(get_option('dbem_mail_receiver_address'));  
	// // This is where you put the email adress of the person you want to mail
	// if(!$mailer->Send()){   
	// 	echo "Message was not sent<br/ >";   
	// 	echo "Mailer Error: " . $mailer->ErrorInfo;
	//  // print_r($mailer);
	// } else {   
	// 	echo "Message has been sent";
	// 
	// }
}
?>