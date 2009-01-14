<?php
function dbem_rsvp_form() {                
	
	
	$destination = "?".$_SERVER['QUERY_STRING'];
	$module = "<h3>RSVP module</h3><br/>";
	
	$booked_places_options = array();
	for ( $i = 1; $i <= 10; $i++) 
		array_push($booked_places_options, "<option value='$i'>$i</option>");
	
	$module  .= "<form name='booking-form' method='post' action='$destination'>
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
	</form>";   
	// $module .= "dati inviati: ";
	//  	$module .= $_POST['bookerName'];  
	//print_r($_SERVER);
	$event_id = $_GET['event_id'];
	
	
	return $module;
	
}

function dbem_catch_rsvp() {

	if (isset($_POST['bookerName'])) { 
		dbem_book_seats();
	//	dbem_email_rsvp_booking();
	  
		
  } 
	dbem_log($_GET['bookings']);
	
}   
add_action('init','dbem_catch_rsvp');  

function dbem_email_rsvp_booking(){
	$booker = array();
	$bookerName = $_POST['bookerName'];
	$bookerEmail = $_POST['bookerEmail'];    
	$bookedSeart = $_POST['bookedSeats'];
	require("phpmailer/class.phpmailer.php") ;
  require("phpmailer/language/phpmailer.lang-en.php") ;
	$mailer = new PHPMailer();
	$mailer->IsSMTP();
	$mailer->Host = 'ssl://smtp.gmail.com:465';
	$mailer->SMTPAuth = TRUE;
	$mailer->Username = 'benini.davide@gmail.com';  
	// Change this to your gmail adress
	$mailer->Password = 'eicdoals';  
	// Change this to your gmail password
	$mailer->From = 'benini.davide@gmail.com';  
	// This HAVE TO be your gmail adress
	$mailer->FromName = 'Dave Abuzzese'; // This is the from name in the email, you can put anything you like here
	$mailer->Body = "$bookerName ($bookerEmail) will attend this event.";
	$mailer->Subject = 'This is the subject of the email';
	$mailer->AddAddress('cno@cnomania.it');  
	// This is where you put the email adress of the person you want to mail
	if(!$mailer->Send()){   
		echo "Message was not sent<br/ >";   
		echo "Mailer Error: " . $mailer->ErrorInfo;
	} else {   
		echo "Message has been sent";
	
	}
}
 
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
	if (dbem_are_seats_available_for($event_id))
		dbem_record_booking($event_id, $booker['person_id'], $bookedSeats);
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

function dbem_add_person($name, $email, $phone = "") {
	global $wpdb; 
	$people_table = $wpdb->prefix.PEOPLE_TBNAME;
	$sql = "INSERT INTO $people_table (person_name, person_email, person_phone) VALUES ('$name', '$email', '$phone');";
	$wpdb->query($sql);
	$new_person = dbem_get_person_by_name_and_email($name, $email);  
	return ($new_person);
}             

function dbem_record_booking($event_id, $person_id, $seats) {
	global $wpdb; 
	$bookings_table = $wpdb->prefix.BOOKINGS_TBNAME;
	if(true) {
		$sql = "INSERT INTO $bookings_table (event_id, person_id, booking_seats) VALUES ($event_id, $person_id, $seats)";
		$wpdb->query($sql);
	}
} 

function dbem_get_available_seats($event_id) {
	global $wpdb; 
	$bookings_table = $wpdb->prefix.BOOKINGS_TBNAME;
	$sql = "SELECT SUM(booking_seats) AS booked_seats FROM $bookings_table WHERE event_id = $event_id"; 
	$seats_row = $wpdb->get_row($sql, ARRAY_A);  
	$booked_seats = $seats_row['booked_seats'];
	$event = dbem_get_event($event_id);
	$available_seats = $event->event_seats -$booked_seats;
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
function dbem_are_seats_available_for($event_id) {     
	$event = dbem_get_event($event_id);   
	$available_seats = dbem_get_available_seats($event_id);
	return ($available_seats > 0);
} 
      
function dbem_bookings_table($event_id) {

	$bookings =  dbem_get_bookings_for($event_id);
	
	$table = "<form id='bookings-filter' method='get' action=''>
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
	$table .= "<tfoot><tr><th scope='row' colspan='4'>Booked seats:</th><td class='booking-result'>$booked_seats</td></tr>            
						 <tr><th scope='row' colspan='4'>Available seats:</th><td class='booking-result'>$available_seats</td></tr></tfoot>
							</table></div>
							<div class='tablenav'>
								<div class=alignleft actions>
								 <input class=button-secondary action' type='submit' name='doaction2' value='Delete'/>
									<br class='clear'/>
								</div>
								<br class='clear'/>
						 	</div>
						</form>";    
	dbem_log($table);
	return $table;
}

function dbem_get_bookings_for($event_id) {  
	global $wpdb; 
	$bookings_table = $wpdb->prefix.BOOKINGS_TBNAME;
	$sql = "SELECT * FROM $bookings_table WHERE event_id = $event_id";
	$bookings = $wpdb->get_results($sql, ARRAY_A);  
	$booking_data = array();
	foreach ($bookings as $booking) {  
		 $booking;
		$person = dbem_get_person($booking['person_id']);
		dbem_log($person);
		$booking['person_name'] = $person['person_name']; 
		$booking['person_email'] = $person['person_email'];   
		$booking['person_phone'] = $person['person_phone'];
		array_push($booking_data, $booking);
	}
	dbem_log($booking_data); 
	return $booking_data;

} 

?>