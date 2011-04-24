<?php
/**
 * Deals with the each ticket booked in a single booking
 * @author marcus
 *
 */
class EM_Tickets_Bookings extends EM_Object implements Iterator{
	
	/**
	 * Array of EM_Ticket_Booking objects for a specific event
	 * @var array
	 */
	var $tickets_bookings = array();
	/**
	 * This object belongs to this booking object
	 * @var EM_Booking
	 */
	var $booking;
	/**
	 * This object belongs to this booking object
	 * @var EM_Ticket
	 */
	var $ticket;
	var $spaces;
	var $price;
	
	/**
	 * Creates an EM_Tickets instance, 
	 * @param EM_Event $event
	 * @return null
	 */
	function EM_Tickets_Bookings( $object = false ){
		global $wpdb;
		if($object){
			if( is_object($object) && get_class($object) == "EM_Booking"){
				$this->booking = $object;
				$sql = "SELECT * FROM ". EM_TICKETS_BOOKINGS_TABLE ." bt LEFT JOIN ". EM_BOOKINGS_TABLE ." b ON bt.booking_id=b.booking_id  WHERE b.booking_id ='{$this->booking->id}'";
			}elseif( is_object($object) && get_class($object) == "EM_Ticket"){
				$this->ticket = $object;
				$sql = "SELECT * FROM ". EM_TICKETS_BOOKINGS_TABLE ." bt LEFT JOIN ". EM_TICKETS_TABLE ." t ON bt.ticket_id=t.ticket_id  WHERE t.ticket_id ='{$this->ticket->id}'";
			}elseif( is_numeric($object) ){
				$sql = "SELECT * FROM ". EM_TICKETS_BOOKINGS_TABLE ." bt LEFT JOIN ". EM_BOOKINGS_TABLE ." t ON bt.booking_id=b.booking_id  WHERE b.booking_id ='{$object}'";
			}
			$tickets_bookings = $wpdb->get_results($sql, ARRAY_A);
			//Get tickets belonging to this tickets booking.
			foreach ($tickets_bookings as $ticket_booking){
				$EM_Ticket_Booking = new EM_Ticket_Booking($ticket_booking);
				$EM_Ticket_Booking->booking = $this->booking; //save some calls
				$this->tickets_bookings[] = $EM_Ticket_Booking;
			}
		}
		do_action('em_tickets_bookings',$this, $object);
	}
	
	/**
	 * Saves the ticket bookings for this booking into the database, whether a new or existing booking
	 * @return boolean
	 */
	function save(){
		global $wpdb;
		do_action('em_tickets_bookings_save_pre',$this);
		foreach( $this->tickets_bookings as $EM_Ticket_Booking ){
			$result = $EM_Ticket_Booking->save();
			if(!$result){
				$this->errors = array_merge($this->errors, $EM_Ticket_Booking->get_errors());
			}
		}
		if( count($this->errors) > 0 ){
			$this->feedback_message = __('There was a problem saving the booking.', 'dbem');
			$this->errors[] = __('There was a problem saving the booking.', 'dbem');
			return apply_filters('em_tickets_bookings_save', false, $this);
		}
		return apply_filters('em_tickets_bookings_save', true, $this);
	}
	
	/**
	 * Add a booking into this event object, checking that there's enough space for the event
	 * @param EM_Ticket_Booking $EM_Ticket_Booking
	 * @return boolean
	 */
	function add( $EM_Ticket_Booking ){
		global $wpdb,$EM_Mailer;
		//Does the ticket we want to book have enough spaeces?
		if( $EM_Ticket_Booking->get_spaces() > 0 ){
			if ( $EM_Ticket_Booking->get_ticket()->get_available_spaces() >= $EM_Ticket_Booking->get_spaces() ) {  
				$this->tickets_bookings[] = $EM_Ticket_Booking;
				$this->get_spaces(true);
				$this->get_price(true);
				return apply_filters('em_tickets_bookings_add',true,$this);
			} else {
				 $this->errors[] = __('Booking cannot be made, not enough spaces available!', 'dbem');
				return apply_filters('em_tickets_bookings_add',false,$this);
			}
		}
		return apply_filters('em_tickets_bookings_add',false,$this);
	}
	
	/**
	 * Smart event locator, saves a database read if possible. 
	 */
	function get_booking(){
		global $EM_Booking;
		$booking_id = $this->get_booking_id();
		if( is_object($this->booking) && get_class($this->booking)=='EM_Booking' && $this->booking->id == $booking_id ){
			return $this->booking;
		}elseif( is_object($EM_Booking) && $EM_Booking->id == $booking_id ){
			$this->booking = $EM_Booking;
		}else{
			if(is_numeric($booking_id)){
				$this->booking = new EM_Booking($booking_id);
			}else{
				$this->booking = new EM_Booking();
			}
		}
		return apply_filters('em_tickets_bookings_get_booking', $this->booking, $this);;
	}
	
	function get_booking_id(){
		if( count($this->tickets_bookings) > 0 ){
			foreach($this->tickets_bookings as $EM_Booking_Ticket){ break; } //get first array item
			return apply_filters('em_tickets_bookings_get_booking_id', $EM_Booking_Ticket->id, $this);
		}
		return apply_filters('em_tickets_bookings_get_booking_id', false, $this);
	}
	
	/**
	 * Delete all ticket bookings
	 * @return boolean
	 */
	function delete(){
		global $wpdb;
		if( is_object($this->get_booking()) && $this->get_booking()->can_manage() ){
			$result = $wpdb->query("DELETE FROM ".EM_TICKETS_BOOKINGS_TABLE." WHERE booking_id='{$this->booking->id}'");
		}else{
			//FIXME ticket bookings
			$ticket_ids = array();
			foreach( $this->tickets_bookings as $EM_Ticket_Booking ){
				if( $EM_Ticket_Booking->can_manage() ){
					$tickets_bookings_ids[] = $EM_Ticket_Booking->id;
				}else{
					$this->errors[] = sprintf(__('You do not have the rights to manage this %s.','dbem'),__('Booking','dbem'));					
				}
			}
			if(count($ticket_ids) > 0){
				$result = $wpdb->query("DELETE FROM ".EM_TICKETS_BOOKINGS_TABLE." WHERE ticket_booking_id IN (".implode(',',$ticket_ids).")");
			}
		}
		return apply_filters('em_tickets_bookings_get_booking_id', ($result == true), $this);
	}
	
	/**
	 * Retrieve multiple ticket info via POST
	 * @return boolean
	 */
	function get_post(){
		//Build Event Array
		do_action('em_tickets_get_post_pre', $this);
		$this->tickets = array(); //clean current tickets out
		if( !empty($_POST['em_tickets']) && is_array($_POST['em_tickets']) ){
			//get all ticket data and create objects
			foreach($_POST['em_tickets'] as $ticket_data){
				$EM_Ticket = new EM_Ticket($ticket_data);
				$this->tickets[] = $EM_Ticket;
			}
		}elseif( is_object($this->booking) ){
			//we create a blank standard ticket
			$EM_Ticket = new EM_Ticket(array(
				'event_id' => $this->booking->id,
				'ticket_name' => __('Standard','dbem')
			));
			$this->tickets[] = $EM_Ticket;
		}
		return apply_filters('em_tickets_bookings_get_post', $this->validate(), $this);
	}
	
	/**
	 * Go through the tickets in this object and validate them 
	 */
	function validate(){
		$errors = array();
		foreach($this->tickets_bookings as $EM_Ticket_Booking){
			$errors[] = $EM_Ticket_Booking->validate();
		}
		return apply_filters('em_tickets_bookings_validate', !in_array(false, $errors), $this);
	}
	
	/**
	 * Get the total number of spaces booked in this booking. Seting $force_reset to true will recheck spaces, even if previously done so.
	 * @param unknown_type $force_refresh
	 * @return mixed
	 */
	function get_spaces( $force_refresh=false ){
		if($force_refresh || $this->spaces == 0){
			$spaces = 0;
			foreach($this->tickets_bookings as $EM_Ticket_Booking){
				$spaces += $EM_Ticket_Booking->get_spaces();
			}
			$this->spaces = $spaces;
		}
		return apply_filters('em_booking_get_spaces',$this->spaces,$this);
	}
	
	/**
	 * Gets the total price for this whole booking by adding up subtotals of booked tickets. Seting $force_reset to true will recheck spaces, even if previously done so.
	 * @param boolean $force_refresh
	 * @return float
	 */
	function get_price( $force_refresh=false, $format = false ){
		$price = 0;
		if($force_refresh || $this->price == 0){
			foreach($this->tickets_bookings as $EM_Ticket_Booking){
				$price += $EM_Ticket_Booking->get_price();
			}
			$this->price = $price;
		}
		if($format){
			return apply_filters('em_tickets_bookings_get_prices', em_get_currency_symbol().number_format($this->price,2),$this);
		}
		return apply_filters('em_tickets_bookings_get_prices',$this->price,$this);
	}
	
	/**
	 * Goes through each ticket and populates it with the bookings made
	 */
	function get_ticket_bookings(){
		foreach( $this->tickets as $EM_Ticket ){
			$EM_Ticket->get_bookings();
		}
	}	
	
	/* Overrides EM_Object method to apply a filter to result
	 * @see wp-content/plugins/events-manager/classes/EM_Object#build_sql_conditions()
	 */
	function build_sql_conditions( $args = array() ){
		$conditions = apply_filters( 'em_tickets_build_sql_conditions', parent::build_sql_conditions($args), $args );
		if( is_numeric($args['status']) ){
			$conditions['status'] = 'ticket_status='.$args['status'];
		}
		return apply_filters('em_tickets_bookings_build_sql_conditions', $conditions, $args);
	}
	
	/* Overrides EM_Object method to apply a filter to result
	 * @see wp-content/plugins/events-manager/classes/EM_Object#build_sql_orderby()
	 */
	function build_sql_orderby( $args, $accepted_fields, $default_order = 'ASC' ){
		return apply_filters( 'em_tickets_bookings_build_sql_orderby', parent::build_sql_orderby($args, $accepted_fields, get_option('dbem_events_default_order')), $args, $accepted_fields, $default_order );
	}
	
	/* 
	 * Adds custom Events search defaults
	 * @param array $array
	 * @return array
	 * @uses EM_Object#get_default_search()
	 */
	function get_default_search( $array = array() ){
		$defaults = array(
			'status' => false,
			'person' => true //to add later, search by person's tickets...
		);	
		$defaults['owner'] = !current_user_can('manage_others_bookings') ? get_current_user_id():false;
		return apply_filters('em_tickets_bookings_get_default_search', parent::get_default_search($defaults,$array), $array, $defaults);
	}

	//Iterator Implementation
    public function rewind(){
        reset($this->tickets_bookings);
    }  
    public function current(){
        $var = current($this->tickets_bookings);
        return $var;
    }  
    public function key(){
        $var = key($this->tickets_bookings);
        return $var;
    }  
    public function next(){
        $var = next($this->tickets_bookings);
        return $var;
    }  
    public function valid(){
        $key = key($this->tickets_bookings);
        $var = ($key !== NULL && $key !== FALSE);
        return $var;
    }	
}
?>