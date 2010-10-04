<?php
// Function composing the options subpanel
function em_options_save(){
	if( current_user_can('activate_plugins') and  $_POST['em-submitted'] == '1' ){
		//Build the array of options here
		foreach ($_POST as $postKey => $postValue){
			if( substr($postKey, 0, 5) == 'dbem_' ){
				//For now, no validation, since this is in admin area.
				//TODO slashes being added?
				//$postValue = EM_Object::sanitize($postValue)
				update_option($postKey, stripslashes($postValue));
			}
		}
		function em_options_saved_notice(){
			?>
			<div class="updated"><p><strong><?php _e('Changes saved.'); ?></strong></p></div>
			<?php
		}
		add_action ( 'admin_notices', 'em_options_saved_notice' );
	}              
	if( current_user_can('activate_plugins') and  $_GET['revert-to2'] == '1' ){
	    update_option('dbem_version',2);
		function em_reverted_to2(){
			?>
			<div class="updated"><p><strong><?php _e('Succesfully reverted to 2. Now deactivate Events Manager and replace it with versione 2.2.'); ?></strong></p></div>
			<?php
		}
		add_action ( 'admin_notices', 'em_reverted_to2' );
		
	}
	
	
}
add_action('admin_head', 'em_options_save');          



function dbem_options_subpanel() {
	//TODO place all options into an array
	$save_button = '<tr><th>&nbsp;</th><td><p class="submit" style="margin:0px; padding:0px; text-align:right;"><input type="submit" id="dbem_options_submit" name="Submit" value="'. __( 'Save Changes' ) .' ('. __('All','dbem') .')" /></p></ts></td></tr>';
	?>	
	<script type="text/javascript" charset="utf-8">
		jQuery(document).ready(function($){
			//$(".postbox:not(:first)").addClass('closed');
		});
	</script>		
	<div class="wrap">
		<div id='icon-options-general' class='icon32'><br />
		</div>
		
		<h2><?php _e ( 'Event Manager Options', 'dbem' ); ?></h2>
		
		<form id="dbem_options_form" method="post" action="">          

			<div class="metabox-holder">         
			<!-- // TODO Move style in css -->
			<div class='postbox-container' style='width: 99.5%'>
			<div id="" class="meta-box-sortables" >
		  
			<div  class="postbox " >
			<div class="handlediv" title="<?php __('Click to toggle'); ?>"><br /></div><h3 class='hndle'><span><?php _e ( 'General options', 'dbem' ); ?> </span></h3>
			<div class="inside">
	            <table class="form-table">
					<?php 
					dbem_options_radio_binary ( __( 'Use dropdown for locations?' ), 'dbem_use_select_for_locations', __( 'Select yes to select location from a drow-down menu; location selection will be faster, but you will lose the ability to insert locations with events','dbem' ) );  
					dbem_options_radio_binary ( __( 'Use recurrence?' ), 'dbem_recurrence_enabled', __( 'Select yes to enable the recurrence features feature','dbem' ) ); 
					dbem_options_radio_binary ( __( 'Use RSVP?' ), 'dbem_rsvp_enabled', __( 'Select yes to enable the recurrence features feature','dbem' ) );     
					dbem_options_radio_binary ( __( 'Use categories?' ), 'dbem_categories_enabled', __( 'Select yes to enable the category features','dbem' ) );     
					dbem_options_radio_binary ( __( 'Use attributes?' ), 'dbem_attributes_enabled', __( 'Select yes to enable the attributes feature','dbem' ) );
					echo $save_button;
					?>
				</table>
				    
			</div> <!-- . inside --> 
			</div> <!-- .postbox -->    
			
			<div  class="postbox " >
			<div class="handlediv" title="<?php __('Click to toggle'); ?>"><br /></div><h3 class='hndle'><span><?php _e ( 'Events page', 'dbem' ); ?> </span></h3>
			<div class="inside">
                 <table class="form-table">         
				 	<?php
				 	//Wordpress Pages
				 	global $em_disable_filter; //Using a flag here instead
				 	$em_disable_filter = true;     
				 	$get_pages = get_pages();
				 	$events_page_options = array();
				 	//TODO Add the hierarchy style ddm, like when choosing page parents
				 	foreach($get_pages as $page){
				 		$events_page_options[$page->ID] = $page->post_title;
				 	} 
				   	dbem_options_select ( __( 'Events page' ), 'dbem_events_page', $events_page_options, __( 'This option allows you to select which page to use as an events page','dbem' ) );
					$em_disable_filter = false;
					//Rest
					dbem_options_radio_binary ( __( 'Show events page in lists?', 'dbem' ), 'dbem_list_events_page', __( 'Check this option if you want the events page to appear together with other pages in pages lists.', 'dbem' ) ); 
					dbem_options_radio_binary ( __( 'Display calendar in events page?', 'dbem' ), 'dbem_display_calendar_in_events_page', __( 'This options allows to display the calendar in the events page, instead of the default list. It is recommended not to display both the calendar widget and a calendar page.','dbem' ) );
					echo $save_button;
					?>				
				</table>
			</div> <!-- . inside -->
			</div> <!-- .postbox -->
			
			<div  class="postbox " >
			<div class="handlediv" title="<?php __('Click to toggle'); ?>"><br /></div><h3 class='hndle'><span><?php _e ( 'Events format', 'dbem' ); ?> </span></h3>
			<div class="inside">
            	<table class="form-table">
				 	<?php
					dbem_options_textarea ( __( 'Default event list format header', 'dbem' ), 'dbem_event_list_item_format_header', __( 'This content will appear just above your code for the default event list format. Default is blank', 'dbem' ) );
				 	dbem_options_textarea ( __( 'Default event list format', 'dbem' ), 'dbem_event_list_item_format', __( 'The format of any events in a list.<br/>Insert one or more of the following placeholders: <code>#_NAME</code>, <code>#_LOCATION</code>, <code>#_ADDRESS</code>, <code>#_TOWN</code>, <code>#_NOTES</code>.<br/> Use <code>#_EXCERPT</code> to show <code>#_NOTES</code> until you place a &lt;!&ndash;&ndash; more &ndash;&ndash;&gt; marker.<br/> Use <code>#_LINKEDNAME</code> for the event name with a link to the given event page.<br/> Use <code>#_EVENTPAGEURL</code> to print the event page URL and make your own customised links.<br/> Use <code>#_LOCATIONPAGEURL</code> to print the location page URL and make your own customised links.<br/>Use <code>#_EDITEVENTLINK</code> to add add a link to edit page for the event, which will appear only when a user is logged in.<br/>To insert date and time values, use <a href="http://www.php.net/manual/en/function.date.php">PHP time format characters</a>  with a <code>#</code> symbol before them, i.e. <code>#m</code>, <code>#M</code>, <code>#j</code>, etc.<br/> For the end time, put <code>#@</code> in front of the character, ie. <code>#@h</code>, <code>#@i</code>, etc.<br/> You can also create a date format without prepending <code>#</code> by wrapping it in #_{} or #@_{} (e.g. <code>#_{d/m/Y}</code>). If there is no end date, the value is not shown.<br/>Feel free to use HTML tags as <code>li</code>, <code>br</code> and so on.<br/>For custom attributes, you use <code>#_ATT{key}{alternative text}</code>, the second braces are optional and will appear if the attribute is not defined or left blank for that event. This key will appear as an option when adding attributes to your event.', 'dbem' ) );
					dbem_options_textarea ( __( 'Default event list format footer', 'dbem' ), 'dbem_event_list_item_format_footer', __( 'This content will appear just below your code for the default event list format. Default is blank', 'dbem' ) );
					dbem_options_input_text ( __( 'Single event page title format', 'dbem' ), 'dbem_event_page_title_format', __( 'The format of a single event page title. Follow the previous formatting instructions.', 'dbem' ) );
					dbem_options_textarea ( __( 'Default single event format', 'dbem' ), 'dbem_single_event_format', __( 'The format of a single event page.<br/>Follow the previous formatting instructions. <br/>Use <code>#_MAP</code> to insert a map.<br/>Use <code>#_CONTACTNAME</code>, <code>#_CONTACTEMAIL</code>, <code>#_CONTACTPHONE</code> to insert respectively the name, e-mail address and phone number of the designated contact person. <br/>Use <code>#_ADDBOOKINGFORM</code> to insert a form to allow the user to respond to your events reserving one or more places (RSVP).<br/> Use <code>#_REMOVEBOOKINGFORM</code> to insert a form where users, inserting their name and e-mail address, can remove their bookings.', 'dbem' ) );
					dbem_options_input_text ( __( 'Events page title', 'dbem' ), 'dbem_events_page_title', __( 'The title on the multiple events page.', 'dbem' ) );
					dbem_options_input_text ( __( 'No events message', 'dbem' ), 'dbem_no_events_message', __( 'The message displayed when no events are available.', 'dbem' ) );
					dbem_options_input_text ( __( 'List events by date title', 'dbem' ), 'dbem_list_date_title', __( 'If viewing a page for events on a specific dates, this is the title that would show up. To insert date values, use <a href="http://www.php.net/manual/en/function.date.php">PHP time format characters</a>  with a <code>#</code> symbol before them, i.e. <code>#m</code>, <code>#M</code>, <code>#j</code>, etc.<br/>', 'dbem' ) );
					echo $save_button;
					?>
				</table> 	
			</div> <!-- . inside -->
			</div> <!-- .postbox -->
			      
           	<div  class="postbox " >
			<div class="handlediv" title="<?php __('Click to toggle'); ?>"><br /></div><h3 class='hndle'><span><?php _e ( 'Calendar format', 'dbem' ); ?></span></h3>
			<div class="inside">
            	<table class="form-table">   
					<?php
				    dbem_options_input_text ( __( 'Small calendar title', 'dbem' ), 'dbem_small_calendar_event_title_format', __( 'The format of the title, corresponding to the text that appears when hovering on an eventful calendar day.', 'dbem' ) );
					dbem_options_input_text ( __( 'Small calendar title separator', 'dbem' ), 'dbem_small_calendar_event_title_separator', __( 'The separator appearing on the above title when more than one events are taking place on the same day.', 'dbem' ) );         
				    dbem_options_input_text ( __( 'Full calendar events format', 'dbem' ), 'dbem_full_calendar_event_format', __( 'The format of each event when displayed in the full calendar. Remember to include <code>li</code> tags before and after the event.', 'dbem' ) );
					echo $save_button;        
					?>
				</table>
			</div> <!-- . inside -->
			</div> <!-- .postbox -->
			
			<div  class="postbox " >
			<div class="handlediv" title="<?php __('Click to toggle'); ?>"><br /></div><h3 class='hndle'><span><?php _e ( 'Locations format', 'dbem' ); ?> </span></h3>
			<div class="inside">
            	<table class="form-table">
					<?php
					dbem_options_input_text ( __( 'Single location page title format', 'dbem' ), 'dbem_location_page_title_format', __( 'The format of a single location page title.<br/>Follow the previous formatting instructions.', 'dbem' ) );
					dbem_options_textarea ( __( 'Default single location page format', 'dbem' ), 'dbem_single_location_format', __( 'The format of a single location page.<br/>Insert one or more of the following placeholders: <code>#_NAME</code>, <code>#_ADDRESS</code>, <code>#_TOWN</code>, <code>#_DESCRIPTION</code>.<br/> Use <code>#_MAP</code> to display a map of the event location, and <code>#_IMAGE</code> to display an image of the location.<br/> Use <code>#_NEXTEVENTS</code> to insert a list of the upcoming events, <code>#_PASTEVENTS</code> for a list of past events, <code>#_ALLEVENTS</code> for a list of all events taking place in this location.', 'dbem' ) );
					dbem_options_textarea ( __( 'Default location baloon format', 'dbem' ), 'dbem_location_baloon_format', __( 'The format of of the text appearing in the baloon describing the location in the map.<br/>Insert one or more of the following placeholders: <code>#_NAME</code>, <code>#_ADDRESS</code>, <code>#_TOWN</code>, <code>#_DESCRIPTION</code> or <code>#_IMAGE</code>.', 'dbem' ) );
					dbem_options_textarea ( __( 'Default location event list format', 'dbem' ), 'dbem_location_event_list_item_format', __( 'The format of the events the list inserted in the location page through the <code>#_NEXTEVENTS</code>, <code>#_PASTEVENTS</code> and <code>#_ALLEVENTS</code> element. <br/> Follow the events formatting instructions', 'dbem' ) );
					dbem_options_textarea ( __( 'Default no events message', 'dbem' ), 'dbem_location_no_events_message', __( 'The message to be displayed in the list generated by <code>#_NEXTEVENTS</code>, <code>#_PASTEVENTS</code> and <code>#_ALLEVENTS</code> when no events are available.', 'dbem' ) );
					echo $save_button;
					?>
				</table>
			</div> <!-- . inside -->
			</div> <!-- .postbox -->
			
			<div  class="postbox " >
			<div class="handlediv" title="<?php __('Click to toggle'); ?>"><br /></div><h3 class='hndle'><span><?php _e ( 'RSS feed format', 'dbem' ); ?> </span></h3>
			<div class="inside">
            	<table class="form-table">
					<?php				
					dbem_options_input_text ( __( 'RSS main title', 'dbem' ), 'dbem_rss_main_title', __( 'The main title of your RSS events feed.', 'dbem' ) );
					dbem_options_input_text ( __( 'RSS main description', 'dbem' ), 'dbem_rss_main_description', __( 'The main description of your RSS events feed.', 'dbem' ) );
					dbem_options_input_text ( __( 'RSS title format', 'dbem' ), 'dbem_rss_title_format', __( 'The format of the title of each item in the events RSS feed.', 'dbem' ) );
					dbem_options_input_text ( __( 'RSS description format', 'dbem' ), 'dbem_rss_description_format', __( 'The format of the description of each item in the events RSS feed. Follow the previous formatting instructions.', 'dbem' ) );
					echo $save_button;
					?>
				</table>
			</div> <!-- . inside -->
			</div> <!-- .postbox -->
			
			<div  class="postbox " >
			<div class="handlediv" title="<?php __('Click to toggle'); ?>"><br /></div><h3 class='hndle'><span><?php _e ( 'Maps and geotagging', 'dbem' ); ?> </span></h3>
			<div class="inside">
				<table class='form-table'> 
					<?php $gmap_is_active = get_option ( 'dbem_gmap_is_active' ); ?>
					<tr valign="top">
						<th scope="row"><?php _e ( 'Enable Google Maps integration?', 'dbem' ); ?></th>
						<td>
							<input id="dbem_gmap_is_active_yes" name="dbem_gmap_is_active" type="radio" value="1" <?php echo ($gmap_is_active) ? "checked='checked'":''; ?> /><?php _e ( 'Yes' ); ?><br />
							<input name="dbem_gmap_is_active" type="radio" value="0" <?php echo ($gmap_is_active) ? '':"checked='checked'"; ?> /> <?php _e ( 'No' ); ?>  <br />
							<?php _e ( 'Check this option to enable Goggle Map integration.', 'dbem' )?>
						</td>
					</tr>
					<?php
					dbem_options_textarea ( __( 'Map text format', 'dbem' ), 'dbem_map_text_format', __( 'The format the text appearing in the event page map cloud.<br/>Follow the previous formatting instructions.', 'dbem' ) );
					echo $save_button;     
					?> 
				</table>
			</div> <!-- . inside -->
			</div> <!-- .postbox -->
			
			<div  class="postbox " >
			<div class="handlediv" title="<?php __('Click to toggle'); ?>"><br /></div><h3 class='hndle'><span><?php _e ( 'RSVP and bookings', 'dbem' ); ?> </span></h3>
			<div class="inside">
				<table class='form-table'>
					<?php
					dbem_options_select ( __( 'Default contact person', 'dbem' ), 'dbem_default_contact_person', em_get_wp_users (), __( 'Select the default contact person. This user will be employed whenever a contact person is not explicitly specified for an event', 'dbem' ) );
					dbem_options_radio_binary ( __( 'Enable the RSVP e-mail notifications?', 'dbem' ), 'dbem_rsvp_mail_notify_is_active', __( 'Check this option if you want to receive an email when someone books places for your events.', 'dbem' ) );
					dbem_options_textarea ( __( 'Contact person email format', 'dbem' ), 'dbem_contactperson_email_body', __( 'The format or the email which will be sent to  the contact person. Follow the events formatting instructions. <br/>Use <code>#_RESPNAME</code>, <code>#_RESPEMAIL</code> and <code>#_RESPPHONE</code> to display respectively the name, e-mail, address and phone of the respondent.<br/>Use <code>#_SPACES</code> to display the number of spaces reserved by the respondent. Use <code>#_COMMENT</code> to display the respondent\'s comment. <br/> Use <code>#_BOOKEDSEATS</code> and <code>#_AVAILABLESEATS</code> to display respectively the number of booked and available seats.', 'dbem' ) );
					dbem_options_textarea ( __( 'Contact person email format', 'dbem' ), 'dbem_respondent_email_body', __( 'The format or the email which will be sent to reposdent. Follow the events formatting instructions. <br/>Use <code>#_RESPNAME</code> to display the name of the respondent.<br/>Use <code>#_CONTACTNAME</code> and <code>#_CONTACTMAIL</code> a to display respectively the name and e-mail of the contact person.<br/>Use <code>#_SPACES</code> to display the number of spaces reserved by the respondent. Use <code>#_COMMENT</code> to display the respondent\'s comment.', 'dbem' ) );
					dbem_options_input_text ( __( 'Notification sender name', 'dbem' ), 'dbem_mail_sender_name', __( "Insert the display name of the notification sender.", 'dbem' ) );
					dbem_options_input_text ( __( 'Notification sender address', 'dbem' ), 'dbem_mail_sender_address', __( "Insert the address of the notification sender. It must corresponds with your gmail account user", 'dbem' ) );
					dbem_options_input_text ( __( 'Default notification receiver address', 'dbem' ), 'dbem_mail_receiver_address', __( "Insert the address of the receiver of your notifications", 'dbem' ) );
					dbem_options_input_text ( 'Mail sending port', 'dbem_rsvp_mail_port', __( "The port through which you e-mail notifications will be sent. Make sure the firewall doesn't block this port", 'dbem' ) );
					dbem_options_select ( __( 'Mail sending method', 'dbem' ), 'dbem_rsvp_mail_send_method', array ('smtp' => 'SMTP', 'mail' => __( 'PHP mail function', 'dbem' ), 'sendmail' => 'Sendmail', 'qmail' => 'Qmail' ), __( 'Select the method to send email notification.', 'dbem' ) );
					dbem_options_radio_binary ( __( 'Use SMTP authentication?', 'dbem' ), 'dbem_rsvp_mail_SMTPAuth', __( 'SMTP authenticatio is often needed. If you use GMail, make sure to set this parameter to Yes', 'dbem' ) );
					dbem_options_input_text ( 'SMTP host', 'dbem_smtp_host', __( "The SMTP host. Usually it corresponds to 'localhost'. If you use GMail, set this value to 'ssl://smtp.gmail.com:465'.", 'dbem' ) );
					dbem_options_input_text ( __( 'SMTP username', 'dbem' ), 'dbem_smtp_username', __( "Insert the username to be used to access your SMTP server.", 'dbem' ) );
					dbem_options_input_password ( __( 'SMTP password', 'dbem' ), "dbem_smtp_password", __( "Insert the password to be used to access your SMTP server", 'dbem' ) );
					echo $save_button;
					?>
				</table>
			</div> <!-- . inside -->
			</div> <!-- .postbox -->
			
			<div  class="postbox " >
			<div class="handlediv" title="<?php __('Click to toggle'); ?>"><br /></div><h3 class='hndle'><span><?php _e ( 'Images size', 'dbem' ); ?> </span></h3>
			<div class="inside">
				<table class='form-table'>
					<?php
					dbem_options_input_text ( __( 'Maximum width (px)', 'dbem' ), 'dbem_image_max_width', __( 'The maximum allowed width for images uploades', 'dbem' ) );
					dbem_options_input_text ( __( 'Maximum height (px)', 'dbem' ), 'dbem_image_max_height', __( "The maximum allowed width for images uploaded, in pixels", 'dbem' ) );
					dbem_options_input_text ( __( 'Maximum size (bytes)', 'dbem' ), 'dbem_image_max_size', __( "The maximum allowed size for images uploaded, in pixels", 'dbem' ) );
					?>
				</table> 
			</div> <!-- . inside -->
			</div> <!-- .postbox -->
            
			<div  class="postbox " >
			<div class="handlediv" title="<?php __('Click to toggle'); ?>"><br /></div><h3 class='hndle'><span><?php _e ( 'Troubleshooting', 'dbem' ); ?> </span></h3>
			<div class="inside">      
				<div style='margin-left: 20px'>
			   		<p>If somehthing went wrong with the update to version 3.0 you can safely dowgrade.</p>
					<ul style="list-style: bullet"> 
						<li>First of all, <a href='http://downloads.wordpress.org/plugin/events-manager.2.2.2.zip'>dowload a copy of version 2.2</a></li>
						<li>Click the link at the end of this box</li>
						<li>Deactivate Event Manager in the plugin page</li>
						<li>Replace the Events Manager 3.0 folder with the 2.2 version</li>
						<li>Let the developers know, we might be able to help.</li>
					<ul>
					<p><a href='?page=events-manager-options&amp;revert-to2=1'>Revert my database to 2.2</a> </p> 
			   </div>
			</div> <!-- . inside -->
			</div> <!-- .postbox -->

			<p class="submit">
				<input type="submit" id="dbem_options_submit" name="Submit" value="<?php _e ( 'Save Changes' )?>" />
				<input type="hidden" name="em-submitted" value="1" />
			</p>  
			
			</div> <!-- .metabox-sortables -->
			</div> <!-- .postbox-container -->
			
			</div> <!-- .metabox-holder -->	
		</form>
	</div>
	<?php
}
?>