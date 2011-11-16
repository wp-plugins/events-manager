<?php
/**
 * Get a recurring event in a db friendly way, by checking globals and passed variables to avoid extra class instantiations
 * @param mixed $id
 * @return EM_Event_Recurring
 */
function em_get_event_recurring($id = false) {
	global $EM_Event;
	//check if it's not already global so we don't instantiate again
	if( is_object($EM_Event) && get_class($EM_Event) == 'EM_Event_Recurring' ){
		if( is_numeric($id) && $EM_Event->post_id == $id ){
			return $EM_Event;
		}elseif( is_object($id) && $EM_Event->post_id == $id->ID ){
			return $EM_Event;
		}
	}
	if( is_object($id) && get_class($id) == 'EM_Event_Recurring' ){
		return $id;
	}else{
		return new EM_Event_Recurring($id);
	}
}
class EM_Event_Recurring extends EM_Event {
	/* Recurring Specific Values */
	var $recurrence_interval;
	var $recurrence_freq;
	var $recurrence_byday;
	var $recurrence_days;
	var $recurrence_byweekno;
	var $recurrence_tickets;
	/* Kepp an array of fields we will never save to an event */
	var $recurrence_fields = array(
		'recurrence_interval' => array( 'name'=>'interval', 'type'=>'%d', 'null'=>true ), //every x day(s)/week(s)/month(s)
		'recurrence_freq' => array( 'name'=>'freq', 'type'=>'%s', 'null'=>true ), //daily,weekly,monthly?
		'recurrence_days' => array( 'name'=>'days', 'type'=>'%d', 'null'=>true ), //daily,weekly,monthly?
		'recurrence_byday' => array( 'name'=>'byday', 'type'=>'%s', 'null'=>true ), //if weekly or monthly, what days of the week?
		'recurrence_byweekno' => array( 'name'=>'byweekno', 'type'=>'%d', 'null'=>true ), //if monthly which week (-1 is last)
		'recurrence_tickets' => array( 'name'=>'tickets', 'type'=>'%d', 'null'=>true ), //if monthly which week (-1 is last)
	);
	
	function __construct( $post_id = false ){
		$this->fields = $this->fields + $this->recurrence_fields; //add recurrence fields for most functions, remove them when saving events themselves
		parent::__construct($post_id, 'post_id');
		$this->recurrence_tickets = (!is_object($this->recurrence_tickets)) ? unserialize($this->recurrence_tickets):$this->recurrence_tickets;
	}
	
	function get_post_meta( $validate = true ){
		parent::get_post_meta(false);
		//Recurrence data
		$this->recurrence_freq = ( !empty($_REQUEST['recurrence_freq']) && in_array($_REQUEST['recurrence_freq'], array('daily','weekly','monthly')) ) ? $_REQUEST['recurrence_freq']:'daily';
		if( !empty($_REQUEST['recurrence_bydays']) && $this->recurrence_freq == 'weekly' && self::array_is_numeric($_REQUEST['recurrence_bydays']) ){
			$this->recurrence_byday = implode( ",", $_REQUEST['recurrence_bydays'] );
		}elseif( !empty($_REQUEST['recurrence_byday']) && $this->recurrence_freq == 'monthly' ){
			$this->recurrence_byday = $_REQUEST['recurrence_byday'];
		}
		$this->recurrence_interval = ( !empty($_REQUEST['recurrence_interval']) && is_numeric($_REQUEST['recurrence_interval']) ) ? $_REQUEST['recurrence_interval']:1;
		$this->recurrence_byweekno = ( !empty($_REQUEST['recurrence_byweekno']) ) ? $_REQUEST['recurrence_byweekno']:'';
		$this->recurrence_days = ( !empty($_REQUEST['recurrence_days']) && is_numeric($_REQUEST['recurrence_days']) ) ? $_REQUEST['recurrence_days']:1;
		//we handle tickets differently here, so run it after everything
		if( !empty($_REQUEST['event_rsvp']) && $_REQUEST['event_rsvp'] ){
			$this->get_tickets()->get_post();
		}
		$result = $validate ? $this->validate_meta():null; //post returns null
		return apply_filters('em_event_recurrence_get_post', $result, $this);
	}
	
	function validate_meta(){
		parent::validate_meta();
		if( $this->event_rsvp && !$this->get_tickets()->validate() ){
			$this->add_error($this->get_tickets()->get_errors());
		}
		if ( $this->event_end_date == "" || $this->event_end_date == $this->event_start_date ){
			$this->add_error( __( 'Since the event is repeated, you must specify an event end date greater than the start date.', 'dbem' ));
		}
		return apply_filters('em_event_recurrence_validate_meta', count($this->errors) == 0, $this );
	}
	
	function save_meta(){
		parent::save_meta(); //since we added the fields to the $this->fields array, our recurrence meta should save nicely
		//Recurrence master event saved, now Save Events & check errors
		$result = count($this->errors) == 0;
		//build recurrences if needed
		if( !defined('DOING_AUTOSAVE') && $result && $this->post_status == 'publish' ){ //don't save during auto-save
		 	if( !$this->save_events() ){ //only save if post is 'published'
				$this->add_error(__ ( 'Something went wrong with the recurrence update...', 'dbem' ). __ ( 'There was a problem saving the recurring events.', 'dbem' ));
		 	}
		}
		return apply_filters('em_event_recurrence_save_meta', count($this->errors) == 0, $this);		
	}
	
	function delete($force_delete = false){
		global $wpdb;
		do_action('em_event_delete_pre', $this);
		
		$result = wp_delete_post($this->post_id,$force_delete);
		$result_meta = $this->delete_meta();
		return apply_filters('em_event_delete', $result !== false && $result_meta, $this);
	}
	
	function delete_meta(){
		//Delete the recurrences then this recurrence event
		if( $this->can_manage( 'delete_events','delete_others_events' ) ){
			$result = $this->delete_events(); //was true at this point, so false if fails
		}
		return apply_filters('em_event_recurrence_delete', $result !== false, $this);		
	}
	
	/**
	 * Saves events and replaces old ones. Returns true if sucecssful or false if not.
	 * @return boolean
	 */
	function save_events() {
		global $wpdb;
		if( $this->can_manage('edit_events','edit_others_events') && $this->post_status == 'publish' ){
			do_action('em_event_save_events_pre', $this); //actions/filters only run if event is recurring
			//Make template event index, post, and meta (and we just change event dates)
			$event = $this->to_array(true); //event template - for index
			$event['event_attributes'] = serialize($event['event_attributes']);
			$post_fields = $wpdb->get_row('SELECT * FROM '.$wpdb->posts.' WHERE ID='.$this->post_id, ARRAY_A); //post to copy
			$post_name = $post_fields['post_name']; //save post slug since we'll be using this 
			$post_fields['post_type'] = 'event'; //make sure we'll save events, not recurrence templates
			$meta_fields_map = $wpdb->get_results('SELECT meta_key,meta_value FROM '.$wpdb->postmeta.' WHERE post_id='.$this->post_id, ARRAY_A);
			$meta_fields = array();
			//convert meta_fields into a cleaner array
			foreach($meta_fields_map as $meta_data){
				$meta_fields[$meta_data['meta_key']] = $meta_data['meta_value'];
			}
			//remove id and we have a event template to feed to wpdb insert
			unset($event['event_id']); 
			unset($post_fields['ID']);
			//remove recurrence meta info we won't need in events
			foreach( array_keys($this->recurrence_fields) as $recurrence_field){
				unset($event[$recurrence_field]);
				unset($meta_fields['_'.$recurrence_field]);
			}		
			$event['event_date_created'] = current_time('mysql'); //since the recurrences are recreated
			unset($event['event_date_modified']);
			//Set the recurrence ID
			$event['recurrence_id'] = $meta_fields['_recurrence_id'] = $this->post_id;
			//Let's start saving!
			$this->delete_events(); //Delete old events beforehand, this will change soon
			$event_saves = array();
			$event_ids = array();
			$post_ids = array();
			$matching_days = $this->get_recurrence_days(); //Get days where events recur
			/*
			echo 'tickets';
			echo "<pre>"; print_r($this->recurrence_tickets); echo "</pre>";
			echo '$meta_fields';
			echo "<pre>"; print_r($meta_fields); echo "</pre>";
			echo '$post_fields';
			echo "<pre>"; print_r($post_fields); echo "</pre>";
			echo '$event';
			echo "<pre>"; print_r($event); echo "</pre>";
			echo '$matching_days';
			echo "<pre>"; print_r($matching_days); echo "</pre>";
			die('i got here and we have '.count($this->errors).' errors');
			*/
			if( count($matching_days) > 0 ){
				//first save event post data
				foreach( $matching_days as $day ) {
					//rewrite post fields if needed
					$post_fields['post_name'] = $post_name.'-'.date("Y-m-d", $day);
					//adjust certain meta information
					$event['event_start_date'] = $meta_fields['_event_start_date'] = date("Y-m-d", $day);
					$meta_fields['_start_ts'] = $day;
					if($this->recurrence_days > 1){
						$meta_fields['_end_ts'] = $day + ($this->recurrence_days * 60*60*24);
						$event['event_end_date'] = $meta_fields['_event_end_date'] = date("Y-m-d", $meta_fields['_end_ts']);
					}else{
						$meta_fields['_end_ts'] = $day;
						$event['event_end_date'] = $meta_fields['_event_end_date'] = $event['event_start_date'];
					}	
					//create the event
					if( $wpdb->insert($wpdb->posts, $post_fields ) ){
						$event['post_id'] = $post_id = $post_ids[] = $wpdb->insert_id; //post id saved into event and also as a var for later user
						// Set GUID as per wp_insert_post
						$wpdb->update( $wpdb->posts, array( 'guid' => get_permalink( $post_id ) ), array('ID'=>$post_id) );
				 		//insert into events index table
						$event_saves[] = $wpdb->insert(EM_EVENTS_TABLE, $event);
						$event_ids[] = $event_id = $wpdb->insert_id;
				 		//create the meta inserts for each event
				 		$meta_fields['_event_id'] = $event_id;
				 		foreach($meta_fields as $meta_key => $meta_val){
				 			$meta_inserts[] = $wpdb->prepare("(%d, '%s', '%s')", array($post_id, $meta_key, $meta_val));
				 		}
					}else{
						$event_saves[] = false;
					}
					//if( EM_DEBUG ){ echo "Entering recurrence " . date("D d M Y", $day)."<br/>"; }
			 	}
			 	//insert the metas in one go, faster than one by one
			 	if( count($meta_inserts) > 0 ){
				 	$result = $wpdb->query("INSERT INTO ".$wpdb->postmeta." (post_id,meta_key,meta_value) VALUES ".implode(',',$meta_inserts));
				 	if($result === false){
				 		$this->add_error('There was a problem adding custom fields to your recurring events.','dbem');
				 	}
			 	}
			 	//copy the event tags and categories
			 	$categories = get_the_terms( $this->post_id, EM_TAXONOMY_CATEGORY );
		 		$cat_slugs = array();
		 		if( is_array($categories) ){
					foreach($categories as $category){
						if( !empty($category->slug) ) $cat_slugs[] = $category->slug; //save of category will soft-fail if slug is empty
					}
		 		}
				$cat_slugs_count = count($cat_slugs);
			 	$tags = get_the_terms( $this->post_id, EM_TAXONOMY_TAG);
		 		$tax_slugs = array();
		 		if( is_array($tags) ){
					foreach($tags as $tag){
						if( !empty($tag->slug) ) $tax_slugs[] = $tag->slug; //save of category will soft-fail if slug is empty
					}
		 		}
				$tax_slugs_count = count($tax_slugs);
			 	foreach($post_ids as $post_id){
					if( $cat_slugs_count > 0 ){
						wp_set_object_terms($post_id, $cat_slugs, EM_TAXONOMY_CATEGORY);
					}
					if( $tax_slugs_count > 0 ){
						wp_set_object_terms($post_id, $tax_slugs, EM_TAXONOMY_TAG);
					}
			 	}
			 	//now, save booking info for each event
			 	if( $this->event_rsvp ){
			 		$meta_inserts = array();
			 		foreach($this->get_tickets() as $EM_Ticket){
			 			/* @var $EM_Ticket EM_Ticket */
			 			//get array, modify event id and insert
			 			$ticket = $EM_Ticket->to_array();
			 			unset($ticket['ticket_id']);
			 			//clean up ticket values
			 			foreach($ticket as $k => $v){
			 				if( empty($v) && $k != 'ticket_name' ){ 
			 					$ticket[$k] = 'NULL';
			 				}else{
			 					$ticket[$k] = "'$v'";
			 				}
			 			}
			 			foreach($event_ids as $event_id){
			 				$ticket['event_id'] = $event_id;
			 				$meta_inserts[] = "(".implode(",",$ticket).")";
			 			}
			 		}
			 		$keys = "(".implode(",",array_keys($ticket)).")";
			 		$values = implode(',',$meta_inserts);
			 		$sql = "INSERT INTO ".EM_TICKETS_TABLE." $keys VALUES $values";
			 		$result = $wpdb->query($sql);
			 	}
			}else{
		 		$this->add_error('You have not defined a date range long enough to create a recurrence.','dbem');
		 		$result = false;
		 	}
		 	return apply_filters('em_event_save_events', !in_array(false, $event_saves) && $result !== false, $this, $event_ids);
		}
		return apply_filters('em_event_save_events', false, $this, $event_ids);
	}
	
	/**
	 * Removes all reoccurring events.
	 * @param $recurrence_id
	 * @return null
	 */
	function delete_events(){
		global $wpdb;
		do_action('em_event_delete_events_pre', $this);
		//So we don't do something we'll regret later, we could just supply the get directly into the delete, but this is safer
		$result = false;
		if( $this->can_manage('delete_events', 'delete_others_events') ){
			//delete events from em_events table
			$events_array = EM_Events::get( array('recurrence_id'=>$this->post_id, 'scope'=>'all', 'status'=>false ) );
			foreach($events_array as $EM_Event){
				/* @var $EM_Event EM_Event */
				if($EM_Event->recurrence_id == $this->post_id){
					$EM_Event->delete(true);
				}
			}			
		}
		return apply_filters('delete_events', $result, $this, $events_array);
	}
	
	/**
	 * Returns the days that match the recurrance array passed (unix timestamps)
	 * @param array $recurrence
	 * @return array
	 */
	function get_recurrence_days(){			
		$start_date = strtotime($this->event_start_date);
		$end_date = strtotime($this->event_end_date);
				
		$weekdays = explode(",", $this->recurrence_byday); //what days of the week (or if monthly, one value at index 0)
		 
		$matching_days = array(); 
		$aDay = 86400;  // a day in seconds
		$aWeek = $aDay * 7;		 
			
		//TODO can this be optimized?
		switch ( $this->recurrence_freq ){
			case 'daily':
				//If daily, it's simple. Get start date, add interval timestamps to that and create matching day for each interval until end date.
				$current_date = $start_date;
				while( $current_date <= $end_date ){
					$matching_days[] = $current_date;
					$current_date = $current_date + ($aDay * $this->recurrence_interval);
				}
				break;
			case 'weekly':
				//sort out week one, get starting days and then days that match time span of event (i.e. remove past events in week 1)
				$start_of_week = get_option('start_of_week'); //Start of week depends on wordpress
				//first, get the start of this week as timestamp
				$event_start_day = date('w', $start_date);
				$offset = 0;
				if( $event_start_day > $start_of_week ){
					$offset = $event_start_day - $start_of_week; //x days backwards
				}elseif( $event_start_day < $start_of_week ){
					$offset = $start_of_week;
				}
				$start_week_date = $start_date - ( ($event_start_day - $start_of_week) * $aDay );
				//then get the timestamps of weekdays during this first week, regardless if within event range
				$start_weekday_dates = array(); //Days in week 1 where there would events, regardless of event date range
				for($i = 0; $i < 7; $i++){
					$weekday_date = $start_week_date+($aDay*$i); //the date of the weekday we're currently checking
					$weekday_day = date('w',$weekday_date); //the day of the week we're checking, taking into account wp start of week setting
					if( in_array( $weekday_day, $weekdays) ){
						$start_weekday_dates[] = $weekday_date; //it's in our starting week day, so add it
					}
				}					
				//for each day of eventful days in week 1, add 7 days * weekly intervals
				foreach ($start_weekday_dates as $weekday_date){
					//Loop weeks by interval until we reach or surpass end date
					while($weekday_date <= $end_date){
						if( $weekday_date >= $start_date && $weekday_date <= $end_date ){
							$matching_days[] = $weekday_date;
						}
						$weekday_date = $weekday_date + ($aWeek *  $this->recurrence_interval);
					}
				}//done!
				break;  
			case 'monthly':
				//loop months starting this month by intervals
				$current_arr = getdate($start_date);
				$end_arr = getdate($end_date);
				$end_month_date = strtotime( date('Y-m-t', $end_date) ); //End date on last day of month
				$current_date = strtotime( date('Y-m-1', $start_date) ); //Start date on first day of month
				while( $current_date <= $end_month_date ){
					$last_day_of_month = date('t', $current_date);
					//Now find which day we're talking about
					$current_week_day = date('w',$current_date);
					$matching_month_days = array();
					//Loop through days of this years month and save matching days to temp array
					for($day = 1; $day <= $last_day_of_month; $day++){
						if($current_week_day == $this->recurrence_byday){
							$matching_month_days[] = $day;
						}
						$current_week_day = ($current_week_day < 6) ? $current_week_day+1 : 0;							
					}
					//Now grab from the array the x day of the month
					$matching_day = ($this->recurrence_byweekno > 0) ? $matching_month_days[$this->recurrence_byweekno-1] : array_pop($matching_month_days);
					$matching_date = strtotime(date('Y-m',$current_date).'-'.$matching_day);
					if($matching_date >= $start_date && $matching_date <= $end_date){
						$matching_days[] = $matching_date;
					}
					//add the number of days in this month to make start of next month
					$current_arr['mon'] += $this->recurrence_interval;
					if($current_arr['mon'] > 12){
						//FIXME this won't work if interval is more than 12
						$current_arr['mon'] = $current_arr['mon'] - 12;
						$current_arr['year']++;
					}
					$current_date = strtotime("{$current_arr['year']}-{$current_arr['mon']}-1"); 
				}
				break;
		}	
		sort($matching_days);
		return $matching_days;
	}
	
	function set_status_events($status){
		global $wpdb;
		if($status !== null){
			$status = $status ? 1:0;			
		}
		if( $status === 1 ){
			$wpdb->query("UPDATE {$wpdb->posts} SET post_status='publish' WHERE ID='{$this->post_id}'");
			$this->post_status = 'publish';
		}elseif($status === 0){
			$wpdb->query("UPDATE {$wpdb->posts} SET post_status='pending' WHERE ID='{$this->post_id}'");
			$this->post_status = 'pending';		
		}elseif($status === null){
			$wpdb->query("UPDATE {$wpdb->posts} SET post_status='draft' WHERE ID='{$this->post_id}'");
			$this->post_status = 'draft';	
		}
		//give sub events same status
		$events_array = EM_Events::get( array('recurrence_id'=>$this->post_id, 'scope'=>'all', 'status'=>false ) );
		foreach($events_array as $EM_Event){
			/* @var $EM_Event EM_Event */
			if($EM_Event->recurrence_id == $this->post_id){
				$EM_Event->set_status($status);
			}
		}
		$wpdb->query("UPDATE {$wpdb->posts} SET post_status='publish' WHERE ID='{$this->post_id}'");
	}
	
	/**
	 * Get the tickets related to this event. Overrides the default because tickets are saved into post meta rather than into the ticket table, as they are used as templates for creating real event tickets.
	 * @return EM_Tickets
	 */
	function get_tickets(){
		if( !is_object($this->recurrence_tickets) ){
			$this->recurrence_tickets = new EM_Tickets();
		}
		return apply_filters('em_events_recurring_get_tickets', $this->recurrence_tickets, $this);
	}
	
	/**
	 * Depreciated, returns false as EM_Event is now only a single event. Use EM_Event_Recurring
	 * @return boolean
	 */
	function is_recurring(){
		return true;
	}
}