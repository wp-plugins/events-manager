<?php
// TODO make person details more secure and integrate with WP user data 
class EM_Person extends WP_User{
	
	function EM_Person( $person_id = false, $username = false ){
		if( is_array($person_id) ){
			if( array_key_exists('person_id',$person_id) ){
				$person_id = $person_id['person_id'];
			}elseif ( array_key_exists('user_id',$person_id) ){
				$person_id = $person_id['user_id'];
			}else{
				$person_id = $person_id['ID'];
			}
		}elseif( is_object($person_id) && get_class($person_id) == 'WP_User'){
			$person_id = $person_id->ID; //create new object if passed a wp_user
		}
		if($username){
			parent::WP_User($person_id, $username);
		}elseif($person_id === 0){
			$this->ID = 0;
			$this->display_name = 'Unregistered Booking';
			$this->user_email = 'n/a';
		}else{
			parent::WP_User($person_id);
		}
		$this->phone = get_metadata('user', $this->ID, 'dbem_phone', true); //extra field for EM
		do_action('em_person',$this, $person_id, $username);
	}
	
	function get_bookings(){
		global $wpdb;
		$results = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix.EM_BOOKINGS_TABLE." WHERE person_id={$this->id}",ARRAY_A);
		$bookings = array();
		foreach($results as $booking_data){
			$bookings[] = new EM_Booking($booking_data);
		}
		return new EM_Bookings($bookings);
	}

	/**
	 * @return EM_Events
	 */
	function get_events(){
		global $wpdb;
		$events = array();
		foreach( $this->get_bookings()->get_bookings() as $EM_Booking ){
			$events[$EM_Booking->event_id] = $EM_Booking->get_event();
		}
		return $events;
	}
	
	function display_summary(){
		ob_start();
		?>
		<table>
			<tr>
				<td><?php echo get_avatar($this->ID); ?></td>
				<td style="padding-left:10px; vertical-align: top;">
					<strong>Name</strong> : <?php echo $this->display_name; ?><br /><br />
					<strong>Email</strong> : <?php echo $this->user_email; ?><br /><br />
					<strong>Phone</strong> : <?php echo $this->phone; ?>
				</td>
			</tr>
		</table>
		<?php
		return ob_get_clean();
	}
}
?>