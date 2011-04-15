<?php

function em_docs_init(){
	if( class_exists('EM_Event') ){
		add_action('wp_head', 'emd_head');
		//Generate the docs
		global $EM_Documentation;
		$EM_Event = new EM_Event();
		$event_fields = $EM_Event->get_fields(true);
		$EM_Location = new EM_Location();
		$location_fields = $EM_Location->get_fields(true);
		$EM_Documentation = array(
			'arguments' => array(
				'events' => array(
					'limit' => array( 'default'=>'See the events lists limit option on the settings page.' ),					
					'orderby' => array( 'desc'=>'Choose what fields to order your results by. You can supply a single field or multiple comma-seperated fields (e.g. "start_date,name").', 'default'=>'See the event lists ordering option on the settings page.', 'args'=>'name, start_date, start_time, end_date, end_time'),
					'order' => array( 'default'=>'See the event lists ordering option on the settings page.' )
				),
				'locations' => array(
					'eventful' => array( 'desc'=>'If set to 1 will only show locations that have at least one event occurring during the scope.', 'default' => 0),
					'eventless' => array( 'desc'=>'If set to 1 will only show locations that have no events occurring during the scope.', 'default' => 0),
					'orderby' => array('desc'=>'Choose what fields to order your results by. You can supply a single field or multiple comma-seperated fields (e.g. "start_date,name").', 'default'=>'name', 'args' => 'name, address, town'),
					'scope' => array( 'default' => 'all')
				),
				'calendar' => array(
					'full' => array( 'desc'=>'If set to 1 it will display a full calendar that shows event names.', 'default' => 0),
					'long_events' => array( 'desc'=>'If set to 1, will show events that last longer than a day.', 'default' => 0),
					'order' => array( 'desc'=>'Same as for events.' ),
					'scope' => array( 'default' => 'future')
				),
				//The object is commonly shared by all, so entries above overwrite entries here
				'general' => array(
					'limit' => array( 'desc'=>'Limits the amount of values returned to this number.', 'default'=>'0 (no limit)'),
					'scope' => array( 'desc'=>'Choose the time frame of events to show. Accepted values are "future", "past" or "all" events. Additionally you can supply dates (in format of YYYY-MM-DD), either single for events on a specific date or two dates seperated by a comma (e.g. 2010-12-25,2010-12-31) for events ocurring between these dates.', 'default'=>'future'),
					'order' => array( 'desc'=>'Indicates the order of the events. Choose between ASC (ascending) and DESC (descending).', 'default'=>'ASC'),
					'orderby' => array( 'desc'=>'Choose what fields to order your results by. You can supply a single field or multiple comma-seperated fields (e.g. "start_date,name"). See specific instances (e.g. events, locations, etc.) for field names.', 'default'=>0),
					'format' => array( 'desc'=>'If you are displaying some information with the shortcode or function (e.g. listing events), you can supply the html and placeholders here.', 'default'=>'The relevant default format will be taken from the settings page.'), 
					'event' => array( 'desc'=>'Supply a single id or comma-seperated ids (e.g. "1,2,3") to limit the search to events with these id(s).', 'default'=>0),
					'category' => array( 'desc'=>'Supply a single id or comma-seperated ids (e.g. "1,2,3") to limit the search to events in these categories.', 'default'=>0), 
					'location' => array( 'desc'=>'Supply a single id or comma-seperated ids to limit the search to these locations (or events in these locations).', 'default'=>0), 
					'offset' => array( 'desc'=>'For example, if you have ten results, if you set this to 5, only the last 5 results will be returned. Useful for pagination.', 'default'=>0), 
					'recurrence' => array( 'desc'=>'If set to 1, will show only events that are recurring (i.e. events that are repeated over different dates).', 'default'=>0),
					'recurring' => array( 'desc'=>'If set to 1, will only show recurring event templates. Only useful if you know what you\'re doing, use recurrence if you want events that are recurrences.', 'default'=>0),
					'month' => array( 'desc'=>'If set to a month (1 to 12) only events that start or end during this month/year will be retured. Must be used in conjunction with year', 'default'=>''),
					'year' => array( 'desc'=>'If set to a year (e.g. 2010) only events that start or end during this year/month will be returned. Must be used in conjunction with year', 'default'=>''),
					'array' => array( 'desc'=>'If you supply this as an argument, the returned data will be in an array, no objects (only useful wen using PHP, not shortcodes)', 'default'=>0),
					'pagination' => array('desc'=>'When using a function or shortcode that outputs items (e.g. [events_list] for events, [locations_list] for locations), if the number of items supercede the limit of items to show, setting this to 1 will show page links under the list.', 'default'=>0)
				)
			),
			'placeholders' => array(
				'events' => array(
					'Event Details' => array(
						'placeholders' => array(
							'#_NAME' => array( 'desc' => 'Displays the name of the event.' ),
							'#_NOTES' => array( 'desc' => 'Shows the description of the event.' ),
							'#_EXCERPT' => array( 'desc' => 'If you added a <a href="http://en.support.wordpress.com/splitting-content/more-tag/">more tag</a> to your event description, only the content before this tag will show (currently, no read more link is added).' ),
							'#_EVENTID' => array( 'desc' => 'Shows the event\'s corresponding ID number in the database table.' )
						)
					),
					'Category Details' => array(
						'placeholders' => array(
							'#_CATEGORYNAME' => array( 'desc' => 'Shows the category name of the event.' ),
							'#_CATEGORYID' => array( 'desc' => 'Shows the category ID of the event.' )
						)
					),					
					'Time' => array(
						'desc' => '',
						'placeholders' => array(
							'#_24HSTARTTIME' => array( 'desc' => 'Displays the start time in a 24 hours format (e.g. 16:30).' ),
							'#_24HENDTIME' => array( 'desc' => 'Displays the end time in a 24 hours format (e.g. 18:30).' ),
							'#_12HSTARTTIME' => array( 'desc' => 'Displays the start time in a 12 hours format (e.g. 4:30 PM).' ),
							'#_12HENDTIME' => array( 'desc' => 'Displays the end time in a 12 hours format (e.g. 6:30 PM).' )
						)
					),
					'Custom Date/Time Formatting' => array(
						'desc' => 'Events Manager allows extremely flexible date formatting by using <a href="http://www.php.net/manual/en/function.date.php">PHP date syntax format characters</a> along with placeholders.',
						'placeholders' => array(
							'# or #@' => array( 'desc' => 'Prepend <code>#</code> or <code>#@</code> before a valid PHP date syntax format character to show start and end date/time information respectively (e.g. <code>#F</code> will show the starting month name like "January", #@h shows the end hour).' ),
							'#{x} or #@{x}' => array( 'desc' => 'You can also create a date format without prepending # to each character by wrapping a valid php date() format in <code>#{}</code> or <code>#@{}</code> (e.g. <code>#_{d/m/Y}</code>). If there is no end date (or is same as start date), the value is not shown. This is useful if you want to show event end dates only on events that are longer than on day, e.g. <code>#j #M #Y #@_{ \u\n\t\i\l j M Y}</code>.' ),
						)
					),
					'Links' => array(
						'placeholders' => array(
							'#_EVENTURL' => array( 'desc' => 'Simply prints the event URL. You can use this placeholder to build your own customised links.' ),
							'#_EVENTLINK' => array( 'desc' => 'Displays the event name with a link to the event page.' ),
							'#_EDITEVENTLINK' => array( 'desc' => 'Inserts a link to the edit event page, only if a user is logged in and is allowed to edit the event.' )
						)
					),
					'Custom Attributes' => array(
						'desc' => 'Events Manager allows you to create dynamic attributes to your events, which act as extra information fields for your events (e.g. "Dress Code"). For more information see <a href="http://wp-events-plugin.com/documentation/categories-and-attributes/">our online documentation</a> for more info on attributes.',
						'placeholders' => array( 
							'#_ATT{key}{alternative text}' => array('desc'=>'This key will appear as an option when adding attributes to your event. The second braces are optional and will appear if the attribute is not defined or left blank for that event.')
						)
					),
					'Bookings/RSVP' => array(
						'desc' => 'These placeholders will only show if RSVP is enabled for the given event and in the events manager settings page. Spaces placeholders will default to 0',
						'placeholders' => array(
							'#_ADDBOOKINGFORM' => array( 'desc' => 'Adds a form which allows the visitors to register for an event.' ),
							'#_REMOVEBOOKINGFORM' => array( 'desc' => 'Adds a form which allows the visitors to remove their booking.' ),
							'#_BOOKINGFORM' => array( 'desc' => 'Adds a both booking forms (add and remove).' ),
							'#_AVAILABLESPACES' => array( 'desc' => 'Shows available spaces for the event.' ),
							'#_BOOKEDSPACES' => array( 'desc' => 'Shows the amount of currently booked spaces for the event.' ),
							'#_PENDINGSPACES' => array( 'desc' => 'Shows the amount of pending spaces for the event.' ),
							'#_SPACES' => array( 'desc' => 'Shows the total spaces for the event.' ),
							'#_ATTENDEES' => array( 'desc' => 'Shows the list of user avatars attending events.' ),
							'#_BOOKINGBUTTON' => array( 'desc' => 'A single button that will appear to logged in users, if they click on it, they apply for a booking. This button will only display if there is one ticket.' )
						)
					),
					'Contact Details' => array(
						'desc' => 'The values here are taken from the chosen contact for the specific event, or the default contact in the settings page.',
						'placeholders' => array(
							'#_CONTACTNAME' => array( 'desc' => 'Name of the contact person for this event (as shown in the dropdown when adding an event).' ),
							'#_CONTACTUSERNAME' => array( 'desc' => 'Contact person\'s username.' ),
							'#_CONTACTEMAIL' => array( 'desc' => 'E-mail of the contact person for this event.' ),
							'#_CONTACTPHONE' => array( 'desc' => 'Phone number of the contact person for this event. Can be set in the user profile page.' ),
							'#_CONTACTAVATAR' => array( 'desc' => 'Contact person\'s avatar.' ),
							'#_CONTACTPROFILELINK' => array( 'desc' => 'Contact person\'s "Profile" link. Only works with BuddyPress enabled.' ),
							'#_CONTACTPROFILEURL' => array( 'desc' => 'Contact person\'s profile url. Only works with BuddyPress enabled.' ),
							'#_CONTACTID' => array( 'desc' => 'Contact person\'s wordpress user ID.')
						)
					),			
				),
				'locations' => array(
					'Location Details' => array(
						'desc' => '',
						'placeholders' => array(
							'#_LOCATIONNAME' => array( 'desc' => 'Displays the location name.' ),
							'#_LOCATIONADDRESS' => array( 'desc' => 'Displays the address.' ),
							'#_LOCATIONTOWN' => array( 'desc' => 'Displays the town.' ),
							'#_LOCATIONMAP' => array( 'desc' => 'Displays a google map showing where the event is located (Will not show if maps are disabled in the settings page)' ),
							'#_LOCATIONNOTES' => array( 'desc' => 'Shows the location description.' ),
							'#_LOCATIONEXCERPT' => array( 'desc' => 'If you added a <a href="http://en.support.wordpress.com/splitting-content/more-tag/">more tag</a> to your location description, only the content before this tag will show (currently, no read more link is added).' ),
							'#_LOCATIONIMAGE' => array( 'desc' => 'Shows the location image.' ),
							'#_LOCATIONID' => array( 'desc' => 'Displays the location ID number.' )
						)
					),
					'Links' => array(
						'placeholders' => array(
							'#_LOCATIONURL' => array( 'desc' => 'Simply prints the location URL. You can use this placeholder to build your own customised links.' ),
							'#_LOCATIONLINK' => array( 'desc' => 'Displays the location name with a link to the location page.' )
						)
					),			
					'Related Events' => array(
						'desc' => 'You can show lists of other events that are being held at this location. The formatting of the list is the same as a normal events list.',
						'placeholders' => array(
							'#_LOCATIONPASTEVENTS' => array( 'desc' => 'Will show a list of all past events at this location.' ),
							'#_LOCATIONNEXTEVENTS' => array( 'desc' => 'Will show a list of all future events at this location.' ),
							'#_LOCATIONALLEVENTS' => array( 'desc' => 'Will show a list of all events at this location.' )
						)
					),
				),
				'bookings' => array(
					'Booking Person Information' => array(
						'desc' => 'When a specific booking is displayed (on screen and on email), you can use these placeholders to show specific information about the booking. For contact details of the contact of this event, see the events placeholders.',
						'placeholders' => array(
							'#_BOOKINGNAME' => array( 'desc' => 'Name of person who made the booking.' ),
							'#_BOOKINGEMAIL' => array( 'desc' => 'Email of person who made the booking.' ),
							'#_BOOKINGPHONE' => array( 'desc' => 'Phone number of person who made the booking.' ),
							'#_BOOKINGSPACES' => array( 'desc' => 'Number of spaces the person has booked.' ),
							'#_BOOKINGCOMMENT' => array( 'desc' => 'Any specific comments made by the person who made the booking.' )
						)
					),
					'Links' => array(
						'desc' => 'People are able to manage their bookings. Below are some placeholder which automatically provides correctly formatted urls',
						'placeholders' => array(
							'#_BOOKINGLISTURL' => array( 'desc' => 'URL to page showing that users booked events.' )
						)
					)
				),
			),
			//TODO add capabilites explanations
			'capabilities' => array()
		);
	}
}
add_action('init', 'em_docs_init');

function em_docs_placeholders($atts){
	ob_start();
	?>
	<div class="em-docs">
		<?php
		global $EM_Documentation;
		$type = $atts['type'];
		$data = $EM_Documentation['placeholders'][$type];
		foreach($data as $sectionTitle => $details) : ?>
			<div>
				<h3><?php echo $sectionTitle; ?></h3>
				<?php if( !empty($details['desc']) ): ?>
				<p><?php echo $details['desc']; ?></p>
				<?php endif; ?>
				<dl>
					<?php foreach($details['placeholders'] as $placeholder => $desc ): ?>
					<dt><b><?php echo $placeholder; ?></b></dt>
					<dd><?php echo $desc['desc']; ?></dd>
					<?php endforeach; ?>
				</dl>
			</div>
		<?php endforeach; ?>
	</div>
	<?php
	return ob_get_clean();
}
?>