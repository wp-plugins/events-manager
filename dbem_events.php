<?php


function dbem_new_event_page() {

	$title=__("Insert New Event", 'dbem');
	$event = dbem_get_event($element);
	dbem_event_form($event, $title, $element);
	
}

function dbem_events_subpanel() {         
  global $wpdb;
	$action=$_GET['action']; 
	$action2 =$_GET['action2'];
	$event_ID=$_GET['event_id'];
	$recurrence_ID=$_GET['recurrence_id'];
	$scope=$_GET['scope']; 
	$offset=$_GET['offset'];
	$order=$_GET['order'];
	$selectedEvents = $_GET['events'];
	
	// Disable Hello to new user if requested
	if(isset($_GET['disable_hello_to_user']) && $_GET['disable_hello_to_user'] == 'true')
		update_option('dbem_hello_to_user', 0);
	
	if ($order == "")
	 $order = "ASC";
	if ($offset=="")
	 $offset = "0";
	$event_table_name = $wpdb->prefix.EVENTS_TBNAME;
	// Debug code, to make sure I get the correct page

  


	// DELETE action
	if ($action == 'deleteEvents') {
	  //  $sql="DELETE FROM ".$event_table_name." WHERE event_id='"."$event_ID"."'";
    	
		// TODO eventual error if ID in non-existant
		//$wpdb->query($sql);
    foreach($selectedEvents as $event_ID) {
		 	dbem_delete_event($event_ID);
		}

		$events = dbem_get_events("", "future");
		dbem_events_table($events, 10,"Future events");
	}
	// UPDATE or CREATE action
	if ($action == 'update_event' || $action == 'update_recurrence') {

		$event = array();   
		$location = array();                        
	  
		$event['event_name']=stripslashes($_POST[event_name]); 
		// Set event end time to event time if not valid
		// if (!_dbem_is_date_valid($event['event_end_date']))
		// 	$event['event_end_date'] = $event['event-date'];  
	  $event['event_start_date'] = $_POST[event_date];
		$event['event_end_date'] = $_POST[event_end_date];
		//$event['event_start_time'] = $_POST[event_hh].":".$_POST[event_mm].":00";
		//$event['event_end_time'] = $_POST[event_end_hh].":".$_POST[event_end_mm].":00";         
    $event['event_start_time'] = date("G:i:00", strtotime($_POST['event_start_time'])); 
		$event['event_end_time'] = date("G:i:00", strtotime($_POST['event_end_time']));  
		$recurrence['recurrence_name'] = $event['event_name'];
		$recurrence['recurrence_start_date'] = $event['event_start_date'];
		$recurrence['recurrence_end_date'] = $event['event_end_date'];
		$recurrence['recurrence_start_time'] = $event['event_start_time'];
		$recurrence['recurrence_end_time'] = $event['event_end_time'];
		$recurrence['recurrence_id'] = $_POST[recurrence_id];          
		$recurrence['recurrence_freq'] = $_POST[recurrence_freq];
		$recurrence['recurrence_freq'] == 'weekly' 
			? $recurrence[recurrence_byday] = implode(",", $_POST['recurrence_bydays']) 
			: 	$recurrence['recurrence_byday'] = $_POST[recurrence_byday];
		$_POST['recurrence_interval'] == "" ? $recurrence['recurrence_interval'] = 1 : $recurrence['recurrence_interval'] = $_POST['recurrence_interval'];
		$recurrence['recurrence_byweekno'] = $_POST[recurrence_byweekno];
		
		
		$event['event_rsvp'] = $_POST['event_rsvp'];
    $event['event_seats'] = $_POST['event_seats']; 
    
		if(isset($_POST['event_contactperson_id']) && $_POST['event_contactperson_id'] != '' && $_POST['event_contactperson_id'] != '-1') {  
			
			$event['event_contactperson_id'] = $_POST['event_contactperson_id'];
			$recurrence['event_contactperson_id'] = $_POST['event_contactperson_id'];
		}

		if(!_dbem_is_time_valid($event_end_time))
	 		$event_end_time = $event_time;
		
		$location['location_name']=$_POST[location_name];
		$location['location_address']=$_POST[location_address];
		$location['location_town']=$_POST[location_town];
		$location['location_latitude']=$_POST[location_latitude];
		$location['location_longitude']=$_POST[location_longitude];
		$location['location_description']="";
		$event['event_notes']=stripslashes($_POST[event_notes]);
		$recurrence['recurrence_notes']=$event['event_notes'];
		$validation_result = dbem_validate_event($event);
		
		
		if ($validation_result == "OK") { 
			// validation successful  
			
			$related_location = dbem_get_identical_location($location); 
		 // print_r($related_location); 
			if ($related_location)  {
				$event['location_id'] = $related_location['location_id'];
				$recurrence['location_id'] = $related_location['location_id'];      
			}
			else {          
				
				$new_location = dbem_insert_location($location);   
			 	$event['location_id']= $new_location['location_id'];
				$recurrence['location_id'] = $new_location['location_id']; 
				//print_r($new_location);
				
			}     
			   			 		
		  if(!$event_ID && !$recurrence_ID ) {
      	// there isn't anything
		  	if ($_POST['repeated_event']) {
				
		  			//insert new recurrence
		  		  dbem_insert_recurrent_event($event,$recurrence);
		 				$feedback_message = __('New recurrent event inserted!','dbem'); 
		  	} else {
		  		// INSERT new event 
		  		$wpdb->insert($event_table_name, $event);
		 			$feedback_message = __('New event successfully inserted!','dbem');  
		   	}
		  } else { 
		 		// something exists
		  	if($recurrence_ID) {  
		  		// UPDATE old recurrence
		  		$recurrence['recurrence_id'] = $recurrence_ID;
		  		//print_r($recurrence); 
		  		if(dbem_update_recurrence($recurrence))
						$feedback_message = __('Recurrence updated!','dbem');
					else
					  $feedback_message = __('Something went wrong with the recurrence update...','dbem');   
		  	
		  	} else {
		  	   // UPDATE old event
		      // unlink from recurrence in case it was generated by one
	    		$event['recurrence_id'] = null;
		  		$where['event_id'] = $event_ID;
		      	$wpdb->update($event_table_name, $event, $where); 
		  		$feedback_message = "'".$event['event_name']."' ".__('updated','dbem')."!";   
		  	}
	  	}
			
	   

					
				//$wpdb->query($sql); 
				echo "<div id='message' class='updated fade'>
						<p>$feedback_message</p>
					  </div>";
				$events = dbem_get_events("", "future");  
				dbem_events_table($events, 10, "Future events"); 
			} else {
			  // validation unsuccessful
			
				echo "<div id='message' class='error '>
						<p>".__("Ach, there's a problem here:","dbem")." $validation_result</p>
					  </div>";	
				dbem_event_form($event,"Edit event $event_ID" ,$event_ID);
				 
			}
		}  
		if ($action == 'edit_event') {
				if (!$event_ID) {
				$title=__("Insert New Event", 'dbem');
				} else {
					$event = dbem_get_event($event_ID);
					$title=__("Edit Event", 'dbem')." '".$event['event_name']."'";
				}
		 
					//$event=$wpdb->get_row($sql, ARRAY_A);
					// Enter new events and updates old ones
					// DEBUG: echo"Nome: $event->event_name";

					
					dbem_event_form($event, $title, $event_ID);
				    
	  }
	 	if ($action == 'edit_recurrence') {
			
				$event_ID = $_GET['recurrence_id'];
		 		$recurrence = dbem_get_recurrence($event_ID);
				$title=__("Reschedule", 'dbem'). " '" .$recurrence['recurrence_name']."'";
				dbem_event_form($recurrence, $title, $event_ID);
				    
	  }
	  
		if ($action == 'update_recurrence') {  
			//print_r($recurrence);
			//die('update recurrence!');
		}
	
	 if ($action == "-1" || $action ==""){
			// No action, only showing the events list

				
			 
			switch ($scope) {
				case "past":
					$title = __('Past Events','dbem');
					break;
				case "all":
					$title = __('All Events','dbem'); 
					break;
			  default:
					$title = __('Future Events','dbem'); 
					$scope = "future";   
			}
		  $limit = 20;
		  $events = dbem_get_events($limit, $scope, $order,$offset);  
			
		  dbem_events_table($events, $limit, $title);
			
	}
	
                                                
}         
// array of all pages, bypasses the filter I set up :)
function dbem_get_all_pages() {
	global $wpdb;
	$query = "SELECT id, post_title FROM ".$wpdb->prefix."posts WHERE post_type = 'page';";
	$pages = $wpdb->get_results($query, ARRAY_A);
	$output = array();
	foreach($pages as $page) { 
		$output[$page['id']] = $page['post_title'];
	}
	return $output;
}

// Function composing the options subpanel
function dbem_options_subpanel() {
 // dbem_options_register();
	
   ?>
	<div class="wrap">
	 
		<div id='icon-options-general' class='icon32'>
			<br/>
		</div>
		<h2><?php _e('Event Manager Options','dbem'); ?></h2>
			<form id="dbem_options_form" method="post" action="options.php">
				<h3><?php _e('Events page', 'dbem');?></h3> 
				<table class="form-table">  
 					<?php dbem_options_select(__('Events page'), 'dbem_events_page', dbem_get_all_pages(), __('This option allows you to select which page to use as an events page')) ;
					dbem_options_radio_binary(__('Display calendar in events page?', 'dbem'), 'dbem_display_calendar_in_events_page', __('This options allows to display the calendar in the events page, instead of the default list. It is recommended not to display both the calendar widget and a calendar page.'))?>
	      </table>
				<h3><?php _e('Events format', 'dbem');?></h3> 
				<table class="form-table">
 						<?php
						dbem_options_textarea(__('Default event list format', 'dbem'), 'dbem_event_list_item_format', __('The format of any events in a list.<br/>Insert one or more of the following placeholders: <code>#_NAME</code>, <code>#_LOCATION</code>, <code>#_ADDRESS</code>, <code>#_TOWN</code>, <code>#_NOTES</code>.<br/> Use <code>#_LINKEDNAME</code> for the event name with a link to the given event page.<br/> Use <code>#_EVENTPAGEURL</code> to print the event page URL and make your own customised links.<br/> Use <code>#_LOCATIONPAGEURL</code> to print the location page URL and make your own customised links.<br/>To insert date and time values, use <a href="http://www.php.net/manual/en/function.date.php">PHP time format characters</a>  with a <code>#</code> symbol before them, i.e. <code>#m</code>, <code>#M</code>, <code>#j</code>, etc.<br/> For the end time, put <code>#@</code> in front of the character, ie. <code>#@h</code>, <code>#@i</code>, etc.<br/> Feel free to use HTML tags as <code>li</code>, <code>br</code> and so on.', 'dbem'));
						dbem_options_input_text(__('Single event page title format','dbem'), 'dbem_event_page_title_format', __('The format of a single event page title. Follow the previous formatting instructions.','dbem'));
						dbem_options_textarea(__('Default single event format','dbem'), 'dbem_single_event_format',__('The format of a single event page.<br/>Follow the previous formatting instructions. <br/>Use <code>#_MAP</code> to insert a map.<br/>Use <code>#_CONTACTNAME</code>, <code>#_CONTACTEMAIL</code>, <code>#_CONTACTPHONE</code> to insert respectively the name, e-mail address and phone number of the designated contact person. <br/>Use <code>#_ADDBOOKINGFORM</code> to insert a form to allow the user to respond to your events reserving one or more places (RSVP).<br/> Use <code>#_REMOVEBOOKINGFORM</code> to insert a form where users, inserting their name and e-mail address, can remove their bookings.','dbem'));
						dbem_options_radio_binary(__('Show events page in lists?','dbem'), 'dbem_list_events_page', __('Check this option if you want the events page to appear together with other pages in pages lists.','dbem'));
						dbem_options_input_text(__('Events page title','dbem'),'dbem_events_page_title',__('The title on the multiple events page.','dbem'));
						dbem_options_input_text(__('No events message','dbem'), 'dbem_no_events_message',__('The message displayed when no events are available.','dbem'));
						dbem_options_textarea(__('Map text format','dbem'),'dbem_map_text_format',__('The format the text appearing in the event page map cloud.<br/>Follow the previous formatting instructions.','dbem'));
						?>
			</table> 
		 		    
			<h3><?php _e('Locations format', 'dbem');?></h3>   
				<table class="form-table"><?php 
						dbem_options_input_text(__('Single location page title format','dbem'),'dbem_location_page_title_format',__('The format of a single location page title.<br/>Follow the previous formatting instructions.','dbem')); 
			      dbem_options_textarea(__('Default single location page format','dbem'),'dbem_single_location_format',__('The format of a single location page.<br/>Insert one or more of the following placeholders: <code>#_NAME</code>, <code>#_ADDRESS</code>, <code>#_TOWN</code>, <code>#_DESCRIPTION</code>.<br/> Use <code>#_MAP</code> to display a map of the event location, and <code>#_IMAGE</code> to display an image of the location.<br/> Use <code>#_NEXTEVENTS</code> to insert a list of the upcoming events, <code>#_PASTEVENTS</code> for a list of past events, <code>#_ALLEVENTS</code> for a list of all events taking place in this location.','dbem'));
						dbem_options_textarea(__('Default location baloon format','dbem'),'dbem_location_baloon_format', __('The format of of the text appearing in the baloon describing the location in the map.<br/>Insert one or more of the following placeholders: <code>#_NAME</code>, <code>#_ADDRESS</code>, <code>#_TOWN</code>, <code>#_DESCRIPTION</code> or <code>#_IMAGE</code>.','dbem'));
						dbem_options_textarea(__('Default location event list format','dbem'), 'dbem_location_event_list_item_format', __('The format of the events the list inserted in the location page through the <code>#_NEXTEVENTS</code>, <code>#_PASTEVENTS</code> and <code>#_ALLEVENTS</code> element. <br/> Follow the events formatting instructions','dbem'));
						dbem_options_textarea(__('Default no events message', 'dbem'),'dbem_location_no_events_message', __('The message to be displayed in the list generated by <code>#_NEXTEVENTS</code>, <code>#_PASTEVENTS</code> and <code>#_ALLEVENTS</code> when no events are available.','dbem'));
						
						?>
				</table>
			
		<h3><?php _e('RSS feed format', 'dbem');?></h3>   
			<table class="form-table"><?php			
    
					dbem_options_input_text(__('RSS main title','dbem'),'dbem_rss_main_title',__('The main title of your RSS events feed.','dbem'));
					dbem_options_input_text(__('RSS main description','dbem'),'dbem_rss_main_description',__('The main description of your RSS events feed.','dbem'));
					dbem_options_input_text(__('RSS title format','dbem'),'dbem_rss_title_format',__('The format of the title of each item in the events RSS feed.','dbem'));
					dbem_options_input_text(__('RSS description format','dbem'),'dbem_rss_description_format',__('The format of the description of each item in the events RSS feed. Follow the previous formatting instructions.','dbem')); ?>
		</table>
				
			<h3><?php _e('Maps and geotagging', 'dbem');?></h3>
				<table class='form-table'> 
				    <?php $gmap_is_active = get_option('dbem_gmap_is_active'); ?>
					 
				   	<tr valign="top">
				   		<th scope="row"><?php _e('Enable Google Maps integration?','dbem'); ?></th>
				   		<td>
							<input id="dbem_gmap_is_active_yes" name="dbem_gmap_is_active" type="radio" value="1" <?php if($gmap_is_active) echo "checked='checked'"; ?> /><?php _e('Yes'); ?> <br />
							<input name="dbem_gmap_is_active" type="radio" value="0" <?php if(!$gmap_is_active) echo "checked='checked'"; ?> /> <?php _e('No'); ?>  <br />
							<?php _e('Check this option to enable Goggle Map integration.','dbem')?>
						</td>
				   	</tr>
					 <?php 
				   dbem_options_input_text(__('Google Maps API Key','dbem'), 'dbem_gmap_key', sprintf (__("To display Google Maps you need a Google Maps API key. Don't worry, it's free, you can get one <a href='%s'>here</a>.",'dbem'), 'http://code.google.com/apis/maps/signup.html'));
					  ?>       
				</table>
				
				<h3><?php _e('RSVP and bookings', 'dbem');?></h3>
				<table  class='form-table'>
				   <?php
					 	dbem_options_select(__('Default contact person','dbem'), 'dbem_default_contact_person', dbem_get_indexed_users(), __('Select the default contact person. This user will be employed whenever a contact person is not explicitly specified for an event','dbem'));
						dbem_options_radio_binary(__('Enable the RSVP e-mail notifications?','dbem'),'dbem_rsvp_mail_notify_is_active', __('Check this option if you want to receive an email when someone books places for your events.','dbem') );
					  dbem_options_textarea(__('Contact person email format','dbem'),'dbem_contactperson_email_body',__('The format or the email which will be sent to  the contact person. Follow the events formatting instructions. <br/>Use <code>#_RESPNAME</code>, <code>#_RESPEMAIL</code> and <code>#_RESPPHONE</code> to display respectively the name, e-mail, address and phone of the respondent.<br/>Use <code>#_SPACES</code> to display the number of spaces reserved by the respondent.<br/> Use <code>#_BOOKEDSEATS</code> and <code>#_AVAILABLESEATS</code> to display respectively the number of booked and available seats.','dbem'));   
					  dbem_options_textarea(__('Contact person email format','dbem'),'dbem_respondent_email_body',__('The format or the email which will be sent to reposdent. Follow the events formatting instructions. <br/>Use <code>#_RESPNAME</code> to display the name of the respondent.<br/>Use <code>#_CONTACTNAME</code> and <code>#_CONTACTMAIL</code> a to display respectively the name and e-mail of the contact person.<br/>Use <code>#_SPACES</code> to display the number of spaces reserved by the respondent.','dbem'));
						dbem_options_input_text(__('Notification sender name','dbem'),'dbem_mail_sender_name', __("Insert the display name of the notification sender.",'dbem'));
						dbem_options_input_text(__('Notification sender address','dbem'),'dbem_mail_sender_address', __("Insert the address of the notification sender. It must corresponds with your gmail account user",'dbem'));
						dbem_options_input_text(__('Default notification receiver address','dbem'),'dbem_mail_receiver_address',__("Insert the address of the receiver of your notifications",'dbem'));
						dbem_options_input_text('Mail sending port','dbem_rsvp_mail_port',__("The port through which you e-mail notifications will be sent. Make sure the firewall doesn't block this port",'dbem'));  
						dbem_options_select(__('Mail sending method','dbem'), 'dbem_rsvp_mail_send_method', array('smtp'=>'SMTP','mail'=>__('PHP mail function', 'dbem'), 'sendmail' => 'Sendmail', 'qmail' => 'Qmail'), __('Select the method to send email notification.','dbem')); 
						 dbem_options_radio_binary(__('Use SMTP authentication?','dbem'),'dbem_rsvp_mail_SMTPAuth', __('SMTP authenticatio is often needed. If you use GMail, make sure to set this parameter to Yes','dbem'));   
						 dbem_options_input_text('SMTP host','dbem_smtp_host',__("The SMTP host. Usually it corresponds to 'localhost'. If you use GMail, set this value to 'ssl://smtp.gmail.com:465'.",'dbem'));									
             dbem_options_input_text(__('SMTP username','dbem'),'dbem_smtp_username', __("Insert the username to be used to access your SMTP server.",'dbem'));
						 dbem_options_input_password(__('SMTP password','dbem'),"dbem_smtp_password", __("Insert the password to be used to access your SMTP server",'dbem'));?>
				 
						 
			   
					</table>
				
				 	<h3><?php _e('Images size', 'dbem');?></h3>
				    <table class='form-table'> <?php 
							dbem_options_input_text(__('Maximum width (px)','dbem'),'dbem_image_max_width',__('The maximum allowed width for images uploades','dbem')); 			 
							dbem_options_input_text(__('Maximum height (px)','dbem'),'dbem_image_max_height', __("The maximum allowed width for images uploaded, in pixels",'dbem'));
							dbem_options_input_text(__('Maximum size (bytes)','dbem'),'dbem_image_max_size', __("The maximum allowed size for images uploaded, in pixels",'dbem'));
							?>
					 </table> 
				
				
				
				<p class="submit">
					<input type="submit" id="dbem_options_submit" name="Submit" value="<?php _e('Save Changes') ?>" />
				</p>

				
			<?php settings_fields('dbem-options'); ?> 
			</form>
		</div> 
	<?php

    
}




//This is the content of the event page
function dbem_events_page_content() {
	global $wpdb;
	if (isset($_REQUEST['location_id']) && $_REQUEST['location_id'] |= '') {
		
		$location= dbem_get_location($_REQUEST['location_id']);
		$single_location_format = get_option('dbem_single_location_format');  
		$page_body = dbem_replace_locations_placeholders($single_location_format, $location);
		return $page_body;
	
	}
	if (isset($_REQUEST['event_id']) && $_REQUEST['event_id'] != '') { 
		// single event page
		$event_ID=$_REQUEST['event_id'];
		$event= dbem_get_event($event_ID);
		$single_event_format = get_option('dbem_single_event_format');  
		$page_body = dbem_replace_placeholders($single_event_format, $event);
	  	return $page_body;
	} elseif (isset($_REQUEST['calendar_day']) && $_REQUEST['calendar_day'] != ''){ 
			
			$date = $_REQUEST['calendar_day'];
			$events_N = dbem_events_count_for($date);
			// $_GET['scope'] ? $scope = $_GET['scope']: $scope =  "future";   
			// $stored_format = get_option('dbem_event_list_item_format');
			// $events_body  =  dbem_get_events_list(10, $scope, "ASC", $stored_format, $false);  
			if ($events_N > 1) {
					$_GET['calendar_day'] ? $scope = $_GET['calendar_day']: $scope =  "future";   
					$stored_format = get_option('dbem_event_list_item_format');
					$events_body  =  "<ul class='dbem_events_list'>".dbem_get_events_list(10, $scope, "ASC", $stored_format, $false)."</ul>"; 
					return $events_body;   
			} else {
			   $events = dbem_get_events("",$_REQUEST['calendar_day']);
				  $event= $events[0];
					$single_event_format = get_option('dbem_single_event_format');  
					$page_body = dbem_replace_placeholders($single_event_format, $event);
				  return $page_body;
			}
			return $events_body;
	} else { 
		// Multiple events page
		$_GET['scope'] ? $scope = $_GET['scope']: $scope =  "future";   
		$stored_format = get_option('dbem_event_list_item_format');
		if(get_option('dbem_display_calendar_in_events_page'))
			$events_body = dbem_get_calendar();
		else
			$events_body  =  $events_body  =  "<ul class='dbem_events_list'>".dbem_get_events_list(10, $scope, "ASC", $stored_format, $false)."</ul>";  
		return $events_body;       
		
	}
}                                                         
function dbem_events_count_for($date) {
	global $wpdb;      
	$table_name = $wpdb->prefix .EVENTS_TBNAME;           
	$sql = "SELECT COUNT(*) FROM  $table_name WHERE event_start_date  like '$date';";
	return $wpdb->get_var($sql);
}

// filter function to call the event page when appropriate
function dbem_filter_events_page($data) {
	
	// $table_name = $wpdb->prefix .EVENTS_TBNAME;
	// 	$start = strpos($data, DBEM_PAGE);
	
	$is_events_post = (get_the_ID() == get_option('dbem_events_page'));
 	$events_page_id = get_option('dbem_events_page');      
    if (is_page($events_page_id) && $is_events_post) { 
	    return dbem_events_page_content();
	} else {
		return $data;
	} 
}
add_filter('the_content','dbem_filter_events_page');

function dbem_events_page_title($data) {
	$events_page_id = get_option('dbem_events_page');
	$events_page = get_page($events_page_id);
	$events_page_title = $events_page->post_title;      
  


	if (($data == $events_page_title) && (is_page($events_page_id))) {    
		if (isset($_REQUEST['calendar_day']) && $_REQUEST['calendar_day'] != ''){ 

				$date = $_REQUEST['calendar_day'];
				$events_N = dbem_events_count_for($date);

				if ($events_N == 1) { 
					$events = dbem_get_events("",$_REQUEST['calendar_day']);
					$event= $events[0];  
					$stored_page_title_format = get_option('dbem_event_page_title_format');
					$page_title = dbem_replace_placeholders($stored_page_title_format, $event);
				  return $page_title;
				}

		}
	 
		
		
		
		if (isset($_REQUEST['location_id']) && $_REQUEST['location_id'] |= '') {
			$location= dbem_get_location($_REQUEST['location_id']);
			$stored_page_title_format = get_option('dbem_location_page_title_format');
			$page_title = dbem_replace_locations_placeholders($stored_page_title_format, $location);
			return $page_title;
		}
	  if (isset($_REQUEST['event_id']) && $_REQUEST['event_id'] != '') { 
			// single event page
			$event_ID=$_REQUEST['event_id'];
			$event= dbem_get_event($event_ID);
			$stored_page_title_format = get_option('dbem_event_page_title_format');
			$page_title = dbem_replace_placeholders($stored_page_title_format, $event);
			return $page_title;
			} else { 
			// Multiple events page
		  $page_title = get_option('dbem_events_page_title');  
			return $page_title;       

		}
	 
		
		
	
	} else {
		return $data;
	}                
	
} 
// to make sure that in pages lists the title is dbem_events_page_title, and not overwritten by the previous filter
add_filter('the_title','dbem_events_page_title');  
add_filter('single_post_title','dbem_events_page_title');   

function dbem_filter_get_pages($data) {
	$output = array();
    $events_page_id = get_option('dbem_events_page');
	for ($i = 0; $i < count($data); ++$i) {  
		if ($data[$i]->ID == $events_page_id) {   
			$list_events_page = get_option('dbem_list_events_page');
			if($list_events_page) {
				$data[$i]->post_title = get_option('dbem_events_page_title')."&nbsp;";  
		    	$output[] = $data[$i];
			} 
		} else {
	    	$output[] = $data[$i];
	   }
   } 
   return $output; 
}                                  
add_filter('get_pages', 'dbem_filter_get_pages');
            


//
// TODO: ROBA NUOVA DA RIORDINARE
// ADMIN CSS for debug
function dbem_admin_css() {
	$css = "
	<style type='text/css'>
	.debug{
		color: green;
		background: #B7F98C;
		margin: 15px;
		padding: 10px;
		border: 1px solid #629948;
	}
	.switch-tab {
		background: #aaa;
		width: 100px;
		float: right;
		text-align: center;
		margin: 3px 1px 0 5px;
		padding: 2px;
	}
	.switch-tab a {
		color: #fff;
		text-decoration: none;
	}
	.switch-tab a:hover {
		color: #D54E21;
		
	} 
	#events-pagination {
		text-align: center; 
		
	}
	#events-pagination a {
		margin: 0 20px 0 20px;
		text-decoration: none;
		width: 80px;
		padding: 3px 0; 
		background: #FAF4B7;
		border: 1px solid #ccc;
		border-top: none;
	} 
	#new-event {
		float: left;
	 
	}
	</style>";
	echo $css;
}

add_action('admin_print_scripts','dbem_admin_css');

// TEMPLATE TAGS   

// exposed function, for theme  makers
function dbem_get_events_list($limit="3", $scope="future", $order="ASC", $format='', $echo=1) { 
	if(strpos($limit, "=")) {
		// allows the use of arguments without breaking the legacy code
		$defaults = array(
			'limit' => 3,
			'scope' => 'future',
			'order' => 'ASC',
			'format' => '',
			'echo' => 1
		);

		$r = wp_parse_args( $limit, $defaults );
		extract( $r, EXTR_SKIP ); 
		$limit = $r['limit']; 
		$scope = $r['scope']; 
		$order = $r['order']; 
		$format = $r['format']; 
		$echo = $r['echo']; 
	}
	if ($scope == "") 
		$scope = "future";
	if ($order != "DESC") 
		$order = "ASC";	
	if ($format == '')
		$format = get_option('dbem_event_list_item_format');
	$events = dbem_get_events($limit, $scope, $order);
	$output = "";
	if (!empty($events)) {
	foreach ($events as $event){
	  //  $localised_date = mysql2date("j M Y", $event->event_time);

	 	
			
		$output .= dbem_replace_placeholders($format, $event);
	}
	} else {
		$output = "<li class='dbem-no-events'>".get_option('dbem_no_events_message')."</li>";
	}
	if ($echo)
		echo $output;
	else
		return $output;
}  

function dbem_get_events_list_shortcode($atts) {  
	extract(shortcode_atts(array(
			'limit' => 3, 
			'scope' => 'future',
			'order' => 'ASC',
			'format'=> '', 
				), $atts));                                  
	$result = dbem_get_events_list("limit=$limit&scope=$scope&order=$order&format=$format&echo=0") ;
	return $result;
}
add_shortcode('events_list', 'dbem_get_events_list_shortcode');




function dbem_get_events_page($justurl=0, $echo=1, $text='') {
	if(strpos($justurl, "=")) {
		// allows the use of arguments without breaking the legacy code
		$defaults = array(
			'justurl' => 0,
			'text' => '', 
			'echo' => 1,
		);

		$r = wp_parse_args( $justurl, $defaults );
		extract( $r, EXTR_SKIP ); 
		$justurl = $r['justurl']; 
		$text = $r['text']; 
		$echo = $r['echo']; 
	}
	 
	$page_link = get_permalink(get_option("dbem_events_page")) ;
	if($justurl) {  
		$result = $page_link;
	} else {
		if ($text=='')
			$text = get_option("dbem_events_page_title") ;
		$result = "<a href='$page_link' title='$text'>$text</a>";
	}
	if ($echo)
		echo $result;
	else
		return $result;
	
}       
function dbem_get_events_page_shortcode($atts) {  
	extract(shortcode_atts(array(
			'justurl' => 0,
			'text' => '',  
				), $atts));                                  
	$result = dbem_get_events_page("justurl=$justurl&text=$text&echo=0") ;
	return $result;
}
add_shortcode('events_page', 'dbem_get_events_page_shortcode');



function dbem_are_events_available($scope="future") {
	if ($scope == "") 
		$scope = "future";
	$events = dbem_get_events(1, $scope);    
	
  	if (empty($events))
		return FALSE;
  	else 
		return TRUE  ;
}
	
// Returns true if the page in question is the events page
function dbem_is_events_page() {
	$events_page_id = get_option('dbem_events_page');
	return is_page($events_page_id);
}    

function dbem_is_single_event_page() {
	return  (dbem_is_events_page() && (isset($_REQUEST['event_id']) && $_REQUEST['event_id'] != '')) ;
}

function dbem_is_multiple_events_page() {
	return  (dbem_is_events_page() && !(isset($_REQUEST['event_id']) && $_REQUEST['event_id'] != '')) ;
}


// main function querying the database event table
function dbem_get_events($limit="",$scope="future",$order="ASC", $offset="", $location_id = "") {
	global $wpdb;   
	
 	$events_table = $wpdb->prefix.EVENTS_TBNAME;
	if ($limit != "")
		$limit = "LIMIT $limit";
	if ($offset != "")
		$offset = "OFFSET $offset";  
	if($order="")
		$order="ASC";

	$timestamp = time();
	$date_time_array = getdate($timestamp);
  $hours = $date_time_array['hours'];
	$minutes = $date_time_array['minutes'];
	$seconds = $date_time_array['seconds'];
	$month = $date_time_array['mon'];
	$day = $date_time_array['mday'];
	$year = $date_time_array['year'];
 	$today = strftime('%Y-%m-%d', mktime($hours,$minutes,$seconds,$month,$day,$year));
	
	$conditions = array();
	if(preg_match("/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/", $scope)) {
		$conditions[] = " event_start_date like '$scope'" ;
	}	 else {
	if (($scope != "past") && ($scope !="all"))
	 		$scope = "future"; 
	  if ($scope == "future") 
			$conditions[] = " event_start_date >= '$today'" ;
		if ($scope == "past") 
			$conditions[] = " event_start_date < '$today'" ;
	}
 	
	if($location_id != "")
		$conditions[] = " location_id = $location_id";
 	
	$where = implode(" AND ", $conditions);
	if ($where != "")
		$where = " WHERE ".$where;
	
	$sql = "SELECT event_id, 
			    event_name, 
			  	DATE_FORMAT(event_start_time, '%e') AS 'event_day',
			  	DATE_FORMAT(event_start_time, '%Y') AS 'event_year',
			  	DATE_FORMAT(event_start_time, '%k') AS 'event_hh',
			  	DATE_FORMAT(event_start_time, '%i') AS 'event_mm',
				DATE_FORMAT(event_end_time, '%e') AS 'event_end_day',
			  	DATE_FORMAT(event_end_time, '%Y') AS 'event_end_year',
			  	DATE_FORMAT(event_end_time, '%k') AS 'event_end_hh',
			  	DATE_FORMAT(event_end_time, '%i') AS 'event_end_mm',
			  	event_start_date,
					event_end_date,
					event_start_time,
					event_end_time,
	 				event_notes, 
					event_rsvp,
					recurrence_id, 
					location_id, 
					event_contactperson_id
					FROM $events_table   
					$where
					ORDER BY event_start_date $order
					$limit 
					$offset";   
    
  $wpdb->show_errors = true;
	$events = $wpdb->get_results($sql, ARRAY_A); 
	if (!empty($events)) {
		//$wpdb->print_error(); 
		$inflated_events = array();
		foreach($events as $this_event) {
		
			$this_location = dbem_get_location($this_event['location_id']);
			$this_event['location_name'] = $this_location['location_name'];
			$this_event['location_address'] = $this_location['location_address'];
			$this_event['location_town'] = $this_location['location_town'];
			array_push($inflated_events, $this_event);
		}
	  
		return $inflated_events;
	} else {
		return null;
	}
}    
function dbem_get_event($event_id) {
	global $wpdb;
	$events_table = $wpdb->prefix.EVENTS_TBNAME; 	
	$sql = "SELECT event_id, 
			   	event_name, 
		 	  	DATE_FORMAT(event_start_date, '%Y-%m-%e') AS 'event_date', 
					DATE_FORMAT(event_start_date, '%e') AS 'event_day',   
					DATE_FORMAT(event_start_date, '%m') AS 'event_month',
					DATE_FORMAT(event_start_date, '%Y') AS 'event_year',
			  	DATE_FORMAT(event_start_time, '%k') AS 'event_hh',
			  	DATE_FORMAT(event_start_time, '%i') AS 'event_mm',
			DATE_FORMAT(event_start_time, '%h:%i%p') AS 'event_start_12h_time', 
			DATE_FORMAT(event_start_time, '%H:%i') AS 'event_start_24h_time', 
			    DATE_FORMAT(event_end_time, '%Y-%m-%e') AS 'event_end_date', 
					DATE_FORMAT(event_end_time, '%e') AS 'event_end_day',        
					DATE_FORMAT(event_end_time, '%m') AS 'event_end_month',  
		  		DATE_FORMAT(event_end_time, '%Y') AS 'event_end_year',
		  		DATE_FORMAT(event_end_time, '%k') AS 'event_end_hh',
		  		DATE_FORMAT(event_end_time, '%i') AS 'event_end_mm',
				DATE_FORMAT(event_end_time, '%h:%i%p') AS 'event_end_12h_time', 
				DATE_FORMAT(event_end_time, '%H:%i') AS 'event_end_24h_time',   
		      event_start_date,
					event_end_date,
					event_start_time,
			  	event_end_time,
					event_notes,
					event_rsvp,
					event_seats,
					recurrence_id, 
					location_id,
					event_contactperson_id
				FROM $events_table   
			    WHERE event_id = $event_id";   
	     
	//$wpdb->show_errors(true);
	$event = $wpdb->get_row($sql,ARRAY_A);	
	//$wpdb->print_error();
	$location = dbem_get_location($event['location_id']); 
	$event['location_name'] = $location['location_name'];
	$event['location_address'] = $location['location_address'];
	$event['location_town'] = $location['location_town'];   
	$event['location_latitude'] = $location['location_latitude'];
	$event['location_longitude'] = $location['location_longitude']; 
	$event['location_image_url'] = $location['location_image_url']; 
	return $event;
}        

function dbem_hello_to_new_user() {
	$current_user = wp_get_current_user(); 
	$advice = sprintf(__("<p>Hey, <strong>%s</strong>, welcome to <strong>Events Manager</strong>! We hope you like it around here.</p> 
	<p>Now it's time to insert events lists through  <a href='%s' title='Widgets page'>widgets</a>, <a href='%s' title='Template tags documentation'>template tags</a> or <a href='%s' title='Shortcodes documentation'>shortcodes</a>.</p>
	<p>By the way, have you taken a look at the <a href='%s' title='Change settings'>Settings page</a>? That's where you customize the way events and locations are displayed.</p>
	<p>What? Tired of seeing this advice? I hear you, <a href='%s' title='Don't show this advice again'>click here</a> and you won't see this again!</p>", 'dbem'),
	$current_user->display_name, 
	get_bloginfo('url').'/wp-admin/widgets.php',
	'http://davidebenini.it/wordpress-plugins/events-manager#template-tags',
	'http://davidebenini.it/wordpress-plugins/events-manager#shortcodes',
	get_bloginfo('url').'/wp-admin/admin.php?page=events-manager-options',
	get_bloginfo('url').'/wp-admin/admin.php?page=events-manager/events-manager.php&disable_hello_to_user=true');
	?>
	<div id="message" class="updated">
		<?php echo $advice; ?>
	</div>
	<?php
}

function dbem_events_table($events, $limit, $title) {	
	if (isset($_GET['scope'])) 
		$scope = $_GET['scope'];
 	else
		$scope = "future";
	if (($scope  != "past") && ($scope != "all"))
		$scope = "future";
	$events_count = count(dbem_get_events("",$scope));
	
	if (isset($_GET['offset'])) 
		$offset = $_GET['offset'];
	
	$use_events_end = get_option('dbem_use_event_end');
	    	
	?>
	 
	<div class="wrap">
		 <div id="icon-events" class="icon32"><br/></div> 
		 <h2><?php echo $title; ?></h2>
		 		
		<?php 
			$say_hello = get_option('dbem_hello_to_user'); 
			if ($say_hello == 1)
				dbem_hello_to_new_user();
		
		?>   
  	<!--<div id='new-event' class='switch-tab'><a href="<?php bloginfo('wpurl')?>/wp-admin/edit.php?page=events-manager/events-manager.php&amp;action=edit_event"><?php _e('New Event ...', 'dbem');?></a></div>-->  
		<?php
			
			$link = array();
			$link['past'] = "<a href='".get_bloginfo('url')."/wp-admin/edit.php?page=events-manager/events-manager.php&amp;scope=past&amp;order=desc'>".__('Past events','dbem')."</a>"; 
			$link['all'] = " <a href='".get_bloginfo('url')."/wp-admin/edit.php?page=events-manager/events-manager.php&amp;scope=all&amp;order=desc'>".__('All events','dbem')."</a>";   
			$link['future'] = "  <a href='".get_bloginfo('url')."/wp-admin/edit.php?page=events-manager/events-manager.php&amp;scope=future'>".__('Future events','dbem')."</a>";  
			
			$scope_names = array();
			$scope_names['past'] = __('Past events','dbem'); 
	    $scope_names['all'] = __('All events','dbem');
			$scope_names['future'] = __('Future events','dbem');
			
			 ?> 
		
  			<form id="posts-filter" action="" method="get">
        <input type='hidden' name='page' value='events-manager/events-manager.php'/>  
				<ul class="subsubsub">
				<li><a href='edit.php'  class="current"><?php _e('Total', 'dbem');?> <span class="count">(<?php echo (count($events));?>)</span></a> </li></ul>


				<div class="tablenav">

				<div class="alignleft actions">
				<select name="action">
        <option value="-1" selected="selected"><?php _e('Bulk Actions');?></option> 	
				<option value="deleteEvents"><?php _e('Delete selected');?></option> 
				
				</select>
				<input type="submit" value="<?php _e('Apply'); ?>" name="doaction2" id="doaction2" class="button-secondary action" />
				<select name="scope">
	
					<?php
					foreach ($scope_names as $key => $value) {
						$selected = "";
						if ($key == $scope) 
							$selected = "selected='selected'";
					  echo "<option value='$key' $selected>$value</option>  ";
					}       
					?>
				</select>	
				<input id="post-query-submit" class="button-secondary" type="submit" value="<?php _e('Filter')?>"/>
				</div>
				<div class="clear">
				</div>
				<?php 
				if (empty($events)) {
					 // TODO localize
					echo "no events";
				} else {
					?>
		
	<table class="widefat">
  		<thead>
			<tr>
  				<th class='manage-column column-cb check-column' scope='col'><input class='select-all' type="checkbox" value='1'/></th>
  	  		<th><?php _e('Name', 'dbem');?></th>
  	   		<th><?php _e('Location', 'dbem');?></th>
  	   	 
  	  
				<th colspan="2"><?php _e('Date and time', 'dbem');?></th>
  	  	
	
  	   		
  	  </tr>
			</thead>
			<tbody>
  	  <?php
  		$i =1;
  		foreach ($events as $event){
  			$class = ($i % 2) ? ' class="alternate"' : '';
				// FIXME set to american
				$localised_date = mysql2date(__('D d M Y'), $event['event_start_date']);
  			$style = "";
  		 	$today = date("Y-m-d");
 
			  $location_summary = "<b>".$event['location_name']."</b><br/>".$event['location_address']." - ".$event['location_town'];
				
			  
				if ($event['event_start_date'] < $today )
					$style= "style ='background-color: #FADDB7;'";
			?>
	  <tr <?php echo"$class $style"; ?> >   
			 
  	   <td>
 	    		<input type='checkbox' class='row-selector' value='<?php echo $event['event_id'];?>' name='events[]'/>
  	   </td>
  	   <td>
  	    <strong><a class="row-title" href="<?php bloginfo('wpurl')?>/wp-admin/edit.php?page=events-manager/events-manager.php&amp;action=edit_event&amp;event_id=<?php echo $event['event_id']; ?>"><?php echo ($event['event_name']); ?></a></strong>
  	   </td>
  	   <td>
  	 
				 <?php echo $location_summary; ?>
				
  	   </td>
  	  
		<td>
  	     <?php echo $localised_date ; ?><br/>
  	    
  	     <?php echo substr($event['event_start_time'],0,5)." - ".substr($event['event_end_time'],0,5) ; ?>
		</td>
		<td>
				 <?php if($event['recurrence_id']) {
								$recurrence = dbem_get_recurrence($event[recurrence_id]);   ?>
						
								<b><?php echo $recurrence['recurrence_description'];?> <br/> <a href="<?php bloginfo('wpurl')?>/wp-admin/edit.php?page=events-manager/events-manager.php&amp;action=edit_recurrence&amp;recurrence_id=<?php echo $recurrence['recurrence_id'];?>"><?php _e('Reschedule','dbem');?></a></b>
								
								 
							 <?php } ?>
  	    </td>
	 
	   
	<?php
  	    	   echo'</tr>'; 
  	   $i++;
		}
	    ?>
	   
			</tbody>
  	  </table>  
  <?php } // end of table?>

			<div class='tablenav'>
				<div class="alignleft actions">
				
			 	 
					<br class='clear'/> 
				</div>
				<br class='clear'/>
				</div>
			</form>
		</div>
	</div>  

 			<?php
 			if ($events_count >  $limit) {
				$backward = $offset + $limit;
				$forward = $offset - $limit; 
				if (DEBUG)
			 		echo "COUNT = $count BACKWARD = $backward  FORWARD = $forward<br> -- OFFSET = $offset" ; 
				echo "<div id='events-pagination'> ";
				if ($backward < $events_count)
					echo "<a style='float: left' href='".get_bloginfo('url')."/wp-admin/edit.php?page=events-manager/events-manager.php&amp;scope=$scope&offset=$backward'>&lt;&lt;</a>" ;
				if ($forward >= 0)
					echo "<a style='float: right' href='".get_bloginfo('url')."/wp-admin/edit.php?page=events-manager/events-manager.php&amp;scope=$scope&offset=$forward'>&gt;&gt;</a>";
		    echo "</div>" ;
		}
			?>
			
	</div>
<?php
}
function dbem_event_form($event, $title, $element) { 
	
	global $localised_date_formats;
	// change prefix according to event/recurrence
	$_GET['action'] == "edit_recurrence" ? $pref = "recurrence_" : $pref = "event_";
	
	$_GET['action'] == "edit_recurrence" 
	? $form_destination=  "edit.php?page=events-manager/events-manager.php&amp;action=update_recurrence&amp;recurrence_id=".$element  
	: $form_destination = "edit.php?page=events-manager/events-manager.php&amp;action=update_event&amp;event_id=".$element;

	
	$locale_code = substr(get_locale(), 0, 2); 
	$localised_date_format = $localised_date_formats[$locale_code];  
	
	$hours_locale = "24"; 
	// Setting 12 hours format for those countries using it
	if(preg_match("/en|sk|zh|us|uk/",$locale_code)) 
		$hours_locale = "12";  
 


	$localised_example = str_replace("yy", "2008", str_replace("mm", "11", str_replace("dd", "28", $localised_date_format))); 
	$localised_end_example = str_replace("yy", "2008", str_replace("mm", "11", str_replace("dd", "28", $localised_date_format)));
	
	if ($event[$pref.'start_date'] != "") {
		preg_match("/(\d{4})-(\d{2})-(\d{2})/",$event[$pref.'start_date'], $matches );
		$year = $matches[1];
		$month = $matches[2];
		$day = $matches[3]; 
		$localised_date = str_replace("yy", $year, str_replace("mm", $month, str_replace("dd", $day, $localised_date_format)));  
	} else {
		$localised_date = "";
	}
	if ($event[$pref.'end_date'] != "") {  
		preg_match("/(\d{4})-(\d{2})-(\d{2})/",$event[$pref.'end_date'], $matches );
		$end_year = $matches[1];
		$end_month = $matches[2];
		$end_day = $matches[3];
	   $localised_end_date = str_replace("yy", $end_year, str_replace("mm", $end_month, str_replace("dd", $end_day, $localised_date_format)));
	} else {
		$localised_end_date = "";
	}    
	// if($event[$pref.'rsvp'])
	// 	echo (dbem_bookings_table($event[$pref.'id']));      
		
		
	$freq_options = array("daily" => __('Daily', 'dbem'), "weekly" => __('Weekly','dbem'),"monthly" => __('Monthly','dbem'));    
	$days_names = array(1 => __('Mon'), 2 => __('Tue'), 3 =>__('Wed'), 4 =>__('Thu'), 5 =>__('Fri'), 6 =>__('Sat'), 7 =>__('Sun'));
 	$saved_bydays = explode(",", $event['recurrence_byday']);
	$weekno_options = array("1" => __('first', 'dbem'), '2' => __('second','dbem'),'3'=>__('third','dbem'),'4'=>__('fourth', 'dbem'), '-1'=>__('last','dbem'));
	
	$event[$pref.'rsvp'] ? $event_RSVP_checked = "checked='checked'" :  $event_RSVP_checked ='';
	
	?> 
<form id="eventForm" method="post" action="<?php echo $form_destination; ?>">
    <div class="wrap">    
		<div id="icon-events" class="icon32"><br/></div>  
		<h2><?php echo $title; ?></h2>
		<?php
		if($event['recurrence_id']) { ?>
			<p id='recurrence_warning'>
			<?php if(isset($_GET['action']) && ($_GET['action'] == 'edit_recurrence')) {
				_e('WARNING: This is a recurrence.', 'dbem')?><br/>
			<?php _e('Modifying these data all the events linked to this recurrence will be rescheduled','dbem');
				
			} else {
			 _e('WARNING: This is a recurring event.', 'dbem');
			 _e('If you change these data and save, this will become an independent event.','dbem');
			}?></p>
	<?php } ?>
   		<div id="poststuff" class="metabox-holder">
        

				<!-- SIDEBAR -->	
			 	<div id="side-info-column" class="inner-sidebar">  
					<div id='side-sortables' class='meta-box-sortables'>
	          		<!-- recurrence postbox -->
						<div class="postbox " >
							<div class="handlediv" title="Fare clic per cambiare."><br /></div>
							<h3 class='hndle'><span><?php _e("Recurrence",'dbem');?></span></h3>
							
							<div class="inside">     
                        <?php
                        if(!$event['event_id']) {
                        ?>
								<?php if($event['recurrence_id'] != "") 
												$recurrence_YES = "checked='checked'";


								?>
								<p><input id="event-recurrence" type="checkbox" name="repeated_event" value="1" <?php echo $recurrence_YES;?> /> <?php _e('Repeated event', 'dbem');?></p>  
								<div id="event_recurrence_pattern">
								<p>Frequency:
									 	<select id="recurrence-frequency" name="recurrence_freq">
									      <?php dbem_option_items($freq_options, $event[$pref.'freq']); ?>
				          </select></p>
				 					<p>
										<?php _e('Every', 'dbem')?> 
										<input id="recurrence-interval" name='recurrence_interval' size='2' value='<?php echo $event['recurrence_interval'];?>'></input> 
										<span class='interval-desc' id="interval-daily-singular"><?php _e('day', 'dbem') ?></span>
										<span class='interval-desc' id="interval-daily-plural"><?php _e('days', 'dbem') ?></span>
										<span class='interval-desc' id="interval-weekly-singular"><?php _e('week', 'dbem') ?></span>
										<span class='interval-desc' id="interval-weekly-plural"><?php _e('weeks', 'dbem') ?></span>
										<span class='interval-desc' id="interval-monthly-singular"><?php _e('month', 'dbem') ?></span>
										<span class='interval-desc' id="interval-monthly-plural"><?php _e('months', 'dbem') ?></span>  
									</p>

									<p class="alternate-selector" id="weekly-selector">
										<?php dbem_checkbox_items('recurrence_bydays[]', $days_names, $saved_bydays); ?>
									</p>   

									<p class="alternate-selector" id="monthly-selector">
									<?php _e('Every','dbem')?> 
									<select id="monthly-modifier" name="recurrence_byweekno">
										<?php dbem_option_items($weekno_options, $event['recurrence_byweekno']);?>   
						      </select>
									<select id="recurrence-weekday" name="recurrence_byday">
						       	  <?php dbem_option_items($days_names, $event['recurrence_byday']); ?>  
		              </select>&nbsp;
								</p>	
		            </div>
							 <p id="recurrence-tip"><?php _e('Check if your event happens more than once according to a regular pattern', 'dbem') ?></p>
							
							<?php } else {
								   	
										if(!$event['recurrence_id']) {   
											echo "<p>".__('This is\'t a recurrent event','dbem').".</p>";
										} else {      
											
											$recurrence = dbem_get_recurrence($event['recurrence_id']);  ?>
											<p><?php echo $recurrence['recurrence_description'];?><br/><a href="<?php bloginfo('wpurl')?>/wp-admin/edit.php?page=events-manager/events-manager.php&amp;action=edit_recurrence&amp;recurrence_id=<?php echo $recurrence['recurrence_id'];?>"><?php _e('Reschedule', 'dbem');?></a></p>
																						
									 <?php  } ?>
								
						  <?php  } ?>
							</div>
							
							
							
						</div>
					   
						<div class="postbox " >
						<div class="handlediv" title="Fare clic per cambiare."><br /></div>
						<h3 class='hndle'><span><?php _e('Contact Person','dbem');?></span></h3>
						<div class="inside">
							<p>Contact: <?php wp_dropdown_users(array('name'=>'event_contactperson_id', 'show_option_none'=>__("Select...",'dbem'),'selected'=>$event['event_contactperson_id'])) ; ?></p> 
						</div>
					</div>       
			 
					
								
       	    	<div class="postbox " >
							<div class="handlediv" title="Fare clic per cambiare."><br /></div>
							<h3 class='hndle'><span>RSVP</span></h3>
							<div class="inside">
								 
								<p><input id="rsvp-checkbox" name='event_rsvp' value='1' type='checkbox' <?php echo $event_RSVP_checked ?> /> <?php _e('Enable registration for this event', 'dbem')?></p>
								<div id='rsvp-data'>
								<?php 
									if ($event['event_contactperson_id'] != NULL)
								 				$selected = $event['event_contactperson_id'];
											else
												$selected = '0';?>
								<p>	<?php _e('Spaces');?>: <input id="seats-input" type="text" name="event_seats" size='5' value="<?php echo $event[$pref.'seats']?>" /></p>
								 <?php dbem_bookings_compact_table($event[$pref.'id']);  ?>
							</div>
						</div>       
					</div>
				</div> 
			</div>
					
					
			  
				<!-- END OF SIDEBAR -->	
			
			

				<div id="post-body" class="has-sidebar">
				<div id="post-body-content" class="has-sidebar-content">
				<div id="event_name" class="stuffbox">
					<h3><?php _e('Name','dbem'); ?></h3>
					<div class="inside">
						<input type="text" name="event_name" value="<?php echo $event[$pref.'name'] ?>" /><br/>
						<?php _e('The event name. Example: Birthday party', 'dbem') ?>
					</div>
				</div>
				
				
				
				<div id="event_start_date" class="stuffbox">
					<h3 id='event-date-title'><?php _e('Event date','dbem'); ?></h3><h3 id='recurrence-dates-title'><?php _e('Recurrence dates','dbem'); ?></h3>  
					<div class="inside">
					 <input id="localised-date" type="text" name="localised_event_date" value="<?php echo $localised_date ?>" style ="display: none;" />  
					 <input id="date-to-submit" type="text" name="event_date" value="<?php echo $event[$pref.'start_date'] ?>" style="background: #FCFFAA" />              <input id="localised-end-date" type="text" name="localised_event_end_date" value="<?php echo $localised_end_date ?>" style ="display: none; " />
           
					 <input id="end-date-to-submit" type="text" name="event_end_date" value="<?php echo $event[$pref.'end_date'] ?>" style="background: #FCFFAA" />
			
			
			<br/>
						<span id='event-date-explanation'><?php _e('The event date.', 'dbem'); ?></span><span id='recurrence-dates-explanation'><?php _e('The recurrence beginning and end date.', 'dbem'); ?></span>
						
					</div>
				</div>
				

				
			
			 
					 <div id="event_end_day" class="stuffbox">
							<h3><?php _e('Event time','dbem'); ?></h3>  
							<div class="inside">
						  	<input id="start-time" type="text" size="8" maxlength="8" name="event_start_time" value="<?php echo $event[$pref.'start_'.$hours_locale."h_time"]; ?>" /> - 
						  	<input id="end-time" type="text" size="8" maxlength="8" name="event_end_time" value="<?php echo $event[$pref.'end_'.$hours_locale."h_time"]; ?>" /> <br/>
						
						
								<?php _e('The time of the event beginning and end', 'dbem') ?>. 

							</div>
					 </div>          
					
			
					
				 		<div id="location_coordinates" class="stuffbox" style='display:none;'>
							<h3><?php _e('Coordinates','dbem'); ?></h3>
							<div class="inside">
							<input id='location-latitude' name='location_latitude' type='text' value='<?php echo $event['location_latitude']; ?>' size='15'  /> - <input id='location-longitude' name='location_longitude'  type='text' value='<?php echo $event['location_longitude']; ?>' size='15'  />         
						</div>
					</div>
				
				
			
				
				<div id="location_name" class="stuffbox">
					<h3><?php _e('Location','dbem'); ?></h3>
					<div class="inside">
						<table id="dbem-location-data">
							<tr>
								<th><?php _e('Name:') ?>&nbsp;</th><td><input id="location-name" type="text" name="location_name" value="<?php echo $event['location_name']?>" /></td>
								<?php $gmap_is_active = get_option('dbem_gmap_is_active'); ?>
						    
								<?php if ($gmap_is_active) { ?>
									<td rowspan='6'>
										<div id='map-not-found' style='width: 400px; font-size: 140%; text-align: center; margin-top: 100px; display: hide'><p><?php _e('Map not found');?></p></div>
									<div id='event-map' style='width: 400px; height: 300px; background: green; display: hide; margin-right:8px'></div>
									</td> 
							<?php } ; // end of IF_GMAP_ACTIVE?>
					 		</tr>
				 			<tr>
								<td colspan='2'>
							 <p><?php _e('The name of the location where the event takes place. You can use the name of a venue, a square, etc','dbem') ?> </p>
									</td>
							</tr>
							<tr>
								<th><?php _e('Address:') ?>&nbsp;</th><td><input id="location-address" type="text" name="location_address" value="<?php echo $event['location_address']; ?>" /></td>
							</tr>
								<tr>
									<td colspan='2'>
								 <p><?php _e('The address of the location where the event takes place. Example: 21, Dominick Street','dbem') ?> </p>
										</td>
								</tr>
							<tr>
								<th><?php _e('Town:') ?>&nbsp;</th><td> <input id="location-town" type="text" name="location_town" value="<?php echo $event['location_town']?>" /></td>
							</tr>
						 <tr>
							<td colspan='2'>
						<p><?php _e('The town where the location is located. If you\'re using the Google Map integration and want to avoid geotagging ambiguities include the country in the town field. Example: Verona, Italy.', 'dbem') ?></p>
							</td>
						</tr>
						</table>
					</div>
				</div>
 

			

				
				<div id="event_notes" class="postbox">
					<h3><?php _e('Details','dbem'); ?></h3>
					<div class="inside">
						<textarea name="event_notes" rows="8" cols="60"><?php echo $event[$pref.'notes']; ?></textarea><br/>
						<?php _e('Details about the event', 'dbem') ?>
					</div>
				</div>
			</div>
		<p class="submit"><input type="submit" name="events_update" value="<?php _e('Submit Event','dbem'); ?> &raquo;" /></p> 
		</div>
	   
	</div>  
	</div>
</form>
 <?php
}            

function dbem_validate_event($event) {
	// Only for emergencies, when JS is disabled
	// TODO make it fully functional without JS
	global $required_fields;
	$errors = Array();
	foreach ($required_fields as $field) {
		if ($event[$field] == "" ) {
		$errors[] = $field;
		}       
	}
	$error_message = "";
	if (count($errors) >0) 
		$error_message = __('Missing fields: ').implode(", ", $errors).". "; 
	if($_POST['repeated_event'] == "1" && $_POST['event_end_date'] == "")
		$error_message .= __('Since the event is repeated, you must specify an event date.','dbem');
	if ($error_message != "")
		return  $error_message;
	else	
		return "OK";
	
}

function _dbem_is_date_valid($date) {
	$year = substr($date,0,4);
	$month = substr($date,5,2);
	$day = substr($date,8,2);
	return (checkdate($month, $day, $year));
}  
function _dbem_is_time_valid($time) {  
 	$result = preg_match ("/([01]\d|2[0-3])(:[0-5]\d)/", $time);

	return ($result);
}
// Enqueing jQuery script to make sure it's loaded
function dbem_enque_scripts(){ 
	wp_enqueue_script( 'jquery' );     
	// wp_enqueue_script('datepicker','/wp-content/plugins/events-manager/jquery-ui-datepicker/jquery-ui-personalized-1.6b.js', array('jquery') );
}
add_action ('template_redirect', 'dbem_enque_scripts');

function url_exists($url) {

	if ((strpos($url, "http")) === false) $url = "http://" . $url;
    // FIXME ripristina la linea seguente e VEDI DI SISTEMARE!!!!
	// if (is_array(@get_headers($url))) {
	if (true)
       	return true;
    else
		return false;
}

// General script to make sure hidden fields are shown when containing data
function dbem_admin_general_script(){  ?>
	<script src="<?php bloginfo('url');?>/wp-content/plugins/events-manager/dbem.js" type="text/javascript"></script>  
	<script src="<?php bloginfo('url');?>/wp-content/plugins/events-manager/js/jquery-ui-datepicker/ui.datepicker.js" 	type="text/javascript"></script>
  <script src="<?php bloginfo('url');?>/wp-content/plugins/events-manager/js/timeentry/jquery.timeentry.js" type="text/javascript"></script> 
	<?php

	// Check if the locale is there and loads it
	$locale_code = substr(get_locale(), 0, 2);
		
	$show24Hours = 'true'; 
	// Setting 12 hours format for those countries using it
	if(preg_match("/en|sk|zh|us|uk/",$locale_code)) 
			$show24Hours = 'false';
	  

	$locale_file = get_bloginfo('url')."/wp-content/plugins/events-manager/js/jquery-ui-datepicker/i18n/ui.datepicker-$locale_code.js";
	if(url_exists($locale_file)) {   ?>
	  <script src="<?php bloginfo('url');?>/wp-content/plugins/events-manager/js/jquery-ui-datepicker/i18n/ui.datepicker-<?php echo $locale_code;?>.js" type="text/javascript"></script>   
	<?php } ?>                 
	
	
	<style type='text/css' media='all'>
		@import "<?php bloginfo('url');?>/wp-content/plugins/events-manager/js/jquery-ui-datepicker/ui.datepicker.css";
	</style>
	<script type="text/javascript">
 	//<![CDATA[        
   // TODO: make more general, to support also latitude and longitude (when added)
$j=jQuery.noConflict();   

function updateIntervalDescriptor () { 
	$j(".interval-desc").hide();
	var number = "-plural";
	if ($j('input#recurrence-interval').val() == 1 || $j('input#recurrence-interval').val() == "")
	number = "-singular"
	var descriptor = "span#interval-"+$j("select#recurrence-frequency").val()+number;
	$j(descriptor).show();
}
function updateIntervalSelectors () {
	$j('p.alternate-selector').hide();   
	$j('p#'+ $j('select#recurrence-frequency').val() + "-selector").show();
	//$j('p.recurrence-tip').hide();
	//$j('p#'+ $j(this).val() + "-tip").show();
}
function updateShowHideRecurrence () {
	if($j('input#event-recurrence').attr("checked")) {
		$j("#event_recurrence_pattern").fadeIn();
		$j("input#localised-end-date").fadeIn(); 
		$j("#event-date-explanation").hide();
		$j("#recurrence-dates-explanation").show();
		$j("h3#recurrence-dates-title").show();
		$j("h3#event-date-title").hide();     
	} else {
		$j("#event_recurrence_pattern").hide();
		$j("input#localised-end-date").hide();
		$j("#recurrence-dates-explanation").hide();
		$j("#event-date-explanation").show();
		$j("h3#recurrence-dates-title").hide();
		$j("h3#event-date-title").show();   
	}
}

function updateShowHideRsvp () {
	if($j('input#rsvp-checkbox').attr("checked")) {
		$j("div#rsvp-data").fadeIn();
	} else {
		$j("div#rsvp-data").hide();
	}
}

$j(document).ready( function() {
	locale_format = "ciao";
 
	$j("#recurrence-dates-explanation").hide();
	$j("#localised-date").show();

	$j("#date-to-submit").hide();
	$j("#end-date-to-submit").hide(); 
	$j("#localised-date").datepicker($j.extend({},
		($j.datepicker.regional["it"], 
		{altField: "#date-to-submit", 
		altFormat: "yy-mm-dd"})));
	$j("#localised-end-date").datepicker($j.extend({},
		($j.datepicker.regional["it"], 
		{altField: "#end-date-to-submit", 
		altFormat: "yy-mm-dd"})));





  
 	$j("#start-time").timeEntry({spinnerImage: '', show24Hours: <?php echo $show24Hours;?> });
  $j("#end-time").timeEntry({spinnerImage: '', show24Hours: <?php echo $show24Hours;?>});





	$j('input.select-all').change(function(){
	 	if($j(this).is(':checked'))
	 	$j('input.row-selector').attr('checked', true);
	 	else
	 	$j('input.row-selector').attr('checked', false);
	}); 
	// TODO: NOT WORKING FOR SOME REASON, val() gives me 2 instead of 'smtp'...
	// console.log($j('select[name:dbem_rsvp_mail_send_method]').val());
	// 	if ($j('select[name:dbem_rsvp_mail_send_method]').val() != "smtp") {
	// 	 	$j('tr#dbem_smtp_host_row').hide();
	// 		$j('tr#dbem_rsvp_mail_SMTPAuth_row').hide();
	// 	 	$j('tr#dbem_smtp_username_row').hide(); 
	// 	 	$j('tr#dbem_smtp_password_row').hide();
	// 	 }    
	//     
	// 	 $j('select[name:dbem_rsvp_mail_send_method]').change(function() {
	// 	 	console.log($j(this).val()); 
	// 	 	if($j(this).val() == "smtp") {
	// 	 		$j('tr#dbem_smtp_host_row').show();   
	// 			$j('tr#dbem_rsvp_mail_SMTPAuth_row').show();
	// 	 		$j('tr#dbem_smtp_username_row').show(); 
	// 	 		$j('tr#dbem_smtp_password_row').show(); 
	// 	 	} else {
	// 	 		$j('tr#dbem_smtp_host_row').hide();
	// 			$j('tr#dbem_rsvp_mail_SMTPAuth_row').hide();
	// 	 		$j('tr#dbem_smtp_username_row').hide(); 
	// 	 		$j('tr#dbem_smtp_password_row').hide();
	// 	 	}                                                 
    
	 //});
	 updateIntervalDescriptor(); 
	 updateIntervalSelectors();
	 updateShowHideRecurrence();  
	 updateShowHideRsvp();
	 $j('input#event-recurrence').change(updateShowHideRecurrence);  
	 $j('input#rsvp-checkbox').change(updateShowHideRsvp);   
	 // recurrency elements   
	 $j('input#recurrence-interval').keyup(updateIntervalDescriptor);
	 $j('select#recurrence-frequency').change(updateIntervalDescriptor);
	 $j('select#recurrence-frequency').change(updateIntervalSelectors);
    
	 // hiding or showing notes according to their content	
	 jQuery('.postbox h3').prepend('<a class="togbox">+</a> ');
	 // 	    	if(jQuery("textarea[@name=event_notes]").val()!="") {
	 	//    jQuery("textarea[@name=event_notes]").parent().parent().removeClass('closed');
	 	// }
	jQuery('#event_notes h3').click( function() {
		   	jQuery(jQuery(this).parent().get(0)).toggleClass('closed');
    });

   // users cannot submit the event form unless some fields are filled
   	function validateEventForm(){
   		errors = "";
		var recurring = $j("input[@name=repeated_event]:checked").val();
		requiredFields= new Array('event_name', 'localised_event_date', 'location_name','location_address','location_town');
		var localisedRequiredFields = {'event_name':"<?php _e('Name','dbem')?>", 'localised_event_date':"<?php _e('Date','dbem')?>", 'location_name':"<?php _e('Location','dbem')?>",'location_address':"<?php _e('Address','dbem')?>",'location_town':"<?php _e('Town','dbem')?>"};
		
		missingFields = new Array;
		for (var i in requiredFields) {
			if ($j("input[@name=" + requiredFields[i]+ "]").val() == 0) {
				missingFields.push(localisedRequiredFields[requiredFields[i]]);
				$j("input[@name=" + requiredFields[i]+ "]").css('border','2px solid red');
			} else {
				$j("input[@name=" + requiredFields[i]+ "]").css('border','1px solid #DFDFDF');
				
			}
				
	   	}
	
		// 	alert('ciao ' + recurring+ " end: " + $j("input[@name=localised_event_end_date]").val());     
	   	if (missingFields.length > 0) {
	
		    errors = "<?php echo _e('Some required fields are missing:','dbem' )?> " + missingFields.join(", ") + ".\n";
		}
		if(recurring && $j("input[@name=localised_event_end_date]").val() == "")
			errors = errors +  "<?php _e('Since the event is repeated, you must specify an end date','dbem')?>.";
		if(errors != "") {
			alert(errors);
			return false;
		}
		return true; 
   }
   
   $j('#eventForm').bind("submit", validateEventForm);
   	
  
});
//]]>
</script>
	  
<?php	
}
add_action ('admin_head', 'dbem_admin_general_script');                




function dbem_admin_map_script() {  
	if ((isset($_REQUEST['event_id']) && $_REQUEST['event_id'] != '') || (isset($_REQUEST['page']) && $_REQUEST['page'] == 'locations') || (isset($_REQUEST['page']) && $_REQUEST['page'] == 'new_event') || (isset($_REQUEST['action']) && $_REQUEST['action'] == 'edit_recurrence') ) {   
		if (!(isset($_REQUEST['action']) && $_REQUEST['action'] == 'dbem_delete')) {    
			// single event page    
			
	   	
			$event_ID=$_REQUEST['event_id'];
		    $event = dbem_get_event($event_ID);
			
			if ($event['location_town'] != '' || (isset($_REQUEST['page']) && $_REQUEST['page'] = 'locations')) {
				$gmap_key = get_option('dbem_gmap_key');
		        if($event['location_address'] != "") {
			    	$search_key = $event['location_address'].", ".$event['location_town'];
				} else {
					$search_key = $event['location_name'].", ".$event['location_town'];
				}
	   		
		?>
		<style type="text/css">
		  /* div#location_town, div#location_address, div#location_name {
					width: 480px;
				}
				table.form-table {
					width: 50%;
				}     */
		</style>
		<script src="http://maps.google.com/maps?file=api&amp;v=2&amp;key=<?php echo $gmap_key;?>" type="text/javascript"></script>         
		<script type="text/javascript">
			//<![CDATA[
		   	$j=jQuery.noConflict();
		
			function loadMap(location, town, address) {
	      		if (GBrowserIsCompatible()) {
	        		var map = new GMap2(document.getElementById("event-map"));
	        	//	map.addControl(new GScaleControl()); 
							//map.setCenter(new GLatLng(37.4419, -122.1419), 13);   
					var geocoder = new GClientGeocoder();
					if (address !="") {
						searchKey = address + ", " + town;
					} else {
						searchKey =  location + ", " + town;
					}
					
					var search = "<?php echo $search_key ?>" ;
					geocoder.getLatLng(
					    searchKey,
					    function(point) {
					      if (!point) {
					       	$j("#event-map").hide();
							$j('#map-not-found').show();
					      } else {
							mapCenter= new GLatLng(point.lat()+0.01, point.lng()+0.005);
						    map.setCenter(mapCenter, 13);
					        var marker = new GMarker(point);
					        map.addOverlay(marker);
					        marker.openInfoWindowHtml('<strong>' + location +'</strong><p>' + address + '</p><p>' + town + '</p>'); 
							$j('input#location-latitude').val(point.y);
					    $j('input#location-longitude').val(point.x);   
							$j("#event-map").show();
							$j('#map-not-found').hide();
							}
					    }
					  );   
	      	 //	map.addControl(new GSmallMapControl());
					 // map.addControl(new GMapTypeControl());
					
					 }
	    	}
   
			$j(document).ready(function() {
	  		eventLocation = $j("input#location-name").val(); 
			  eventTown = $j("input#location-town").val(); 
				eventAddress = $j("input#location-address").val();
		   
				loadMap(eventLocation, eventTown, eventAddress);
			
				$j("input#location-name").blur(function(){
						newEventLocation = $j("input#location-name").val();  
						if (newEventLocation !=eventLocation) {                
							loadMap(newEventLocation, eventTown, eventAddress); 
							eventLocation = newEventLocation;
					   
						}
				});
				$j("input#location-town").blur(function(){
						newEventTown = $j("input#location-town").val(); 
						if (newEventTown !=eventTown) {  
							loadMap(eventLocation, newEventTown, eventAddress); 
							eventTown = newEventTown;
							} 
				});
				$j("input#location-address").blur(function(){
						newEventAddress = $j("input#location-address").val(); 
						if (newEventAddress != eventAddress) {
							loadMap(eventLocation, eventTown, newEventAddress);
						 	eventAddress = newEventAddress; 
						}
				});
			  
			
		 
		   }); 
		   $j(document).unload(function() {
				GUnload();
		   });
		    //]]>
		</script>
	<?php
	 		}
		}
}    
}
$gmap_is_active = get_option('dbem_gmap_is_active'); 
if ($gmap_is_active) {
	add_action ('admin_head', 'dbem_admin_map_script');
	  
}

// Script to validate map options
function dbem_admin_options_script() { 
	if (isset($_REQUEST['page']) && $_REQUEST['page'] == 'events-manager-options') {  
	   ?>
	<script type="text/javascript">
	//<![CDATA[
		$j=jQuery.noConflict();
		 
		 $j(document).ready(function() {   
				
	  		// users cannot enable Google Maps without an api key
				function verifyOptionsForm(){
				   	var gmap_is_active = $j("input[@name=dbem_gmap_is_active]:checked").val();
						var gmap_key = $j("input[@name=dbem_gmap_key]").val();
				  	if(gmap_is_active == '1' && (gmap_key == '')){
					    alert("<?php _e('You cannot enable Google Maps integration without setting an appropriate API key.');?>");
							$j("input[@name='dbem_gmap_is_active']:nth(1)").attr("checked","checked");
					
						return false;
					} else {
						return true;
					}
				}
				
        $j('#dbem_options_form').bind("submit", verifyOptionsForm);


		   });
	
		
		//]]>
	</script>
	
	<?php
		
	}
	
}
add_action ('admin_head', 'dbem_admin_options_script');   

function dbem_rss_link($justurl=0, $echo=1, $text="RSS") {   
	if(strpos($justurl, "=")) {         
		// allows the use of arguments without breaking the legacy code
		$defaults = array(
			'justurl' => 0,  
			'echo' => 1,   
			'text' => 'RSS',
		);

		$r = wp_parse_args( $justurl, $defaults );
		extract( $r, EXTR_SKIP ); 
		$justurl = $r['justurl'];
		$echo = $r['echo'];   
		$text = $r['text'];    
	}
  if ($text == '')
		$text = "RSS";
	$rss_title = get_option('dbem_events_page_title');
	$url = get_bloginfo('url')."/?dbem_rss=main";  
	$link = "<a href='$url'>$text</a>";

	if ($justurl)
		$result =  $url;
	else
		$result = $link;
	if($echo)
		echo $result;
	else
		return $result;
}  

function dbem_rss_link_shortcode($atts) {  
	extract(shortcode_atts(array(
			'justurl' => 0,
			'text' => 'RSS',
		), $atts));                                  
	$result = dbem_rss_link("justurl=$justurl&echo=0&text=$text") ;
	return $result;
}
add_shortcode('events_rss_link', 'dbem_rss_link_shortcode');



function dbem_rss() {
	if (isset($_REQUEST['dbem_rss']) && $_REQUEST['dbem_rss'] == 'main') {	
		header("Content-type: text/xml");
		echo "<?xml version='1.0'?>\n";
		
		$events_page_id = get_option('dbem_events_page');
		$events_page_link = get_permalink($events_page_id);
		if (stristr($events_page_link, "?"))
		  $joiner = "&amp;";
		else
			$joiner = "?";
		
		
		?>
		<rss version="2.0">
		<channel>
		<title><?php echo get_option('dbem_rss_main_title');?></title>
    <link><?php echo $events_page_link; ?></link>
    <description><?php echo get_option('dbem_rss_main_description');?></description>
    <docs>http://blogs.law.harvard.edu/tech/rss</docs>
    <generator>Weblog Editor 2.0</generator>
    	<?php
			$title_format = get_option('dbem_rss_title_format');
			$description_format = str_replace(">","&gt;",str_replace("<","&lt;",get_option('dbem_rss_description_format')));
			$events = dbem_get_events(5);
			foreach ($events as $event) {
				$title = dbem_replace_placeholders($title_format, $event, "rss");
        		$description = dbem_replace_placeholders($description_format, $event, "rss");
				echo "<item>";   
				echo "<title>$title</title>\n";
				echo "<link>$events_page_link".$joiner."event_id=$event->event_id</link>\n ";
				echo "<description>$description </description>\n";
				echo "</item>";
      }
			?>

			</channel>
			</rss>
	
	<?php
	die();
	}
}
add_action('init','dbem_rss');
function substitute_rss($data) {
	if (isset($_REQUEST['event_id'])) 
		return get_bloginfo('url')."/?dbem_rss=main";
	else
		return $data;
}           
function dbem_general_css() {  
	$base_url = get_bloginfo('url');
	echo "<link rel='stylesheet' href='$base_url/wp-content/plugins/events-manager/events_manager.css' type='text/css'/>";
	
}
add_action('wp_head','dbem_general_css');
add_action('admin_head','dbem_general_css');
//add_filter('feed_link','substitute_rss')
                
// TODO roba da mettere nell'ordine giusto
function dbem_delete_event($event_id) {
		global $wpdb;	
		$table_name = $wpdb->prefix.EVENTS_TBNAME;
		$sql = "DELETE FROM $table_name WHERE event_id = '$event_id';";
	 	$wpdb->query($sql);

}
add_filter('favorite_actions', 'dbem_favorite_menu');
 
function dbem_favorite_menu($actions) {
    // add quick link to our favorite plugin
    $actions['admin.php?page=new_event'] = array(__('Add an event', 'dbem'), MIN_CAPABILITY);
    return $actions;
}    

////////////////////////////////////
// WP 2.7 options registration
function dbem_options_register() {
   $options = array(
	'dbem_events_page',
	'dbem_display_calendar_in_events_page',
	'dbem_use_event_end',
	'dbem_event_list_item_format',
	'dbem_event_page_title_format',
	'dbem_single_event_format',
	'dbem_list_events_page',
	'dbem_events_page_title',
	'dbem_no_events_message',
	'dbem_location_page_title_format','dbem_location_baloon_format',
	'dbem_single_location_format',
	'dbem_location_event_list_item_format',
	'dbem_location_no_events_message',
	'dbem_gmap_is_active',
	'dbem_rss_main_title',
	'dbem_rss_main_description',
	'dbem_rss_title_format',
	'dbem_rss_description_format',
	'dbem_gmap_key',
	'dbem_map_text_format',
	'dbem_rsvp_mail_notify_is_active',
	'dbem_contactperson_email_body',
	'dbem_respondent_email_body',
	'dbem_mail_sender_name',
	'dbem_smtp_username',
	'dbem_smtp_password',
	'dbem_default_contact_person',
	'dbem_mail_sender_address',
	'dbem_mail_receiver_address',
	'dbem_smtp_host',
	'dbem_rsvp_mail_send_method',
	'dbem_rsvp_mail_port',
	'dbem_rsvp_mail_SMTPAuth',
	'dbem_image_max_width',
	'dbem_image_max_height',
	'dbem_image_max_size',
   );
   foreach($options as $opt) {
       register_setting('dbem-options',$opt,'');
   } 

}
add_action( 'admin_init', 'dbem_options_register' );             

function dbem_alert_events_page() { 
	$events_page_id = get_option('dbem_events_page');
	if(strpos($_SERVER['SCRIPT_NAME'], 'page.php') && isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['post']) && $_GET['post'] == "$events_page_id" )  { 
		$message = sprintf(__("This page corresponds to <strong>Events Manager</strong> events page. Its content will be overriden by <strong>Events Manager</strong>. If you want to display your content, you can can assign another page to <strong>Events Manager</strong> in the the <a href='%s'>Settings</a>. ", 'dbem'), 'admin.php?page=events-manager-options');
		$notice = "<div class='error'><p>$message</p></div>";
		echo $notice;
	}

}     
add_action('admin_notices', 'dbem_alert_events_page');
?>