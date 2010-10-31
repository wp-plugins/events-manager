<?php 
/**
 * Display function for the support page. here we can give links to forums and special upgrade instructions e.g. migration features 
 */
function em_admin_help(){
	global $wpdb;
	?>
	<div class="wrap">
		<div id="icon-events" class="icon32"><br /></div>
		<h2><?php _e('Getting Help for Events Manager','dbem'); ?></h2>
		<p>
			If you require further support or encounter any bugs please visit us at our <a href="http://davidebenini.it/events-manager-forum/">Forum</a>. We ask that you give the documentation a good read first, as this answers many common questions. 
		</p>
		<?php
		//Is this a previously imported installation? 
		$old_table_name = $wpdb->prefix.'dbem_events';
		if( $wpdb->get_var("SHOW TABLES LIKE '$old_table_name'") == $old_table_name ){
			?>
			<div class="updated">
				<h3>Troubleshooting upgrades from version 2.x to 3.x</h3>
				<p>We notice that you upgraded from version 2, as we are now using new database tables, and we do not delete the old tables in case something went wrong with this upgrade.</p>
		   		<p>If something went wrong with the update to version 3 read on:</p>
		   		<h4>Scenario 1: the plugin is working, but for some reason the old events weren't imported</h4>
		   		<p>You can safely reimport your old events from the previous tables without any risk of deleting them. However, if you click the link below <b>YOU WILL OVERWRITE ANY NEW EVENTS YOU CREATED IN VERSION 3</b></p>
				<p><a onclick="return confirm('Are you sure you want to do this? Any new changes made since updating will be overwritten by your old ones, and this cannot be undone');" href="<?php echo wp_nonce_url( get_bloginfo('wpurl').'/wp-admin/admin.php?page=events-manager-help&em_reimport=1', 'em_reimport' ) ?>">Reimport Events from version 2</a></p>
				<h4>Scenario 2: the plugin is not working, I want to go back to version 2!</h4>
				<p>You can safely downgrade and will not lose any information.</p>
				<ol> 
					<li>First of all, <a href='http://downloads.wordpress.org/plugin/events-manager.2.2.2.zip'>dowload a copy of version 2.2</a></li>
					<li>Deactivate and delete Events Manager in the plugin page</li>
					<li><a href="<?php bloginfo('wpurl'); ?>/wp-admin/plugin-install.php?tab=upload">Upload the zip file you just downloaded here</a></li>
					<li>Let the developers know, of any bugs you ran into while upgrading. We'll help you out if there is a simple solution, and will fix reported bugs within days, if not quicker!</li>
				</ol>
			</div>
			<?php
		}
		?>
		<div>
			<h2>Placeholders Documentation</h2>
			<?php
			global $EM_Documentation;
			foreach($EM_Documentation['placeholders'] as $type => $data): ?>
				<h3><?php echo ucfirst($type); ?></h3>
				<?php foreach($data as $sectionTitle => $details) : ?>
				<h4><?php echo $sectionTitle; ?></h4>
				<?php if($details['desc'] != ''): ?>
				<p><?php echo $details['desc']; ?></p>
				<?php endif; ?>
				<div>
					<dl>
						<?php foreach($details['placeholders'] as $placeholder => $desc ): ?>
						<dt><b><?php echo $placeholder; ?></b></dt>
						<dd><?php echo $desc['desc']; ?></dd>
						<?php endforeach; ?>
					</dl>						
				</div>
				<?php endforeach; ?>
			<?php endforeach; ?>
		</div>
	</div>
	<?php
}


global $EM_Documentation;
$EM_Documentation = array(
	'placeholders' => array(
		'events' => array(
			'Event Details' => array(
				'placeholders' => array(
					'#_NAME' => array( 'desc' => 'Displays the name of the event.' ),
					'#_NOTES' => array( 'desc' => 'Shows the description of the event.' ),
					'#_EXCERPT' => array( 'desc' => 'If you added a <a href="http://en.support.wordpress.com/splitting-content/more-tag/">more tag</a> to your event description, only the content before this tag will show (currently, no read more link is added).' ),
					'#_CATEGORY' => array( 'desc' => 'Shows the category name of the event.' )
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
					'#_BOOKEDSPACES' => array( 'desc' => 'Shows the amount of currently booked seats for the event.' ),
					'#_SPACES' => array( 'desc' => 'Shows the total spaces for the event.' )
				)
			),
			'Contact Details' => array(
				'desc' => 'The values here are taken from the chosen contact for the specific event, or the default contact in the settings page.',
				'placeholders' => array(
					'#_CONTACTNAME' => array( 'desc' => '' ),
					'#_CONTACTEMAIL' => array( 'desc' => '' ),
					'#_CONTACTPHONE' => array( 'desc' => '' )
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
					'#_LOCATIONIMAGE' => array( 'desc' => 'Shows the location image.' )
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
			)
		),
	),
	'attributes' => array(
		'events' => array(
			'limit' => array( 'default'=>'See the events lists limit option on the settings page.' ),					
			'orderby' => array( 'default'=>'See the event lists ordering option on the settings page.' ),
			'order' => array( 'default'=>'See the event lists ordering option on the settings page.' )
		),
		'locations' => array(
			'eventful' => array( 'desc'=>'If set to 1 will only show locations that have at least one event occurring during the scope.', 'default' => 0),
			'eventless' => array( 'desc'=>'If set to 1 will only show locations that have no events occurring during the scope.', 'default' => 0),
			'scope' => array( 'default' => 'all')
		),
		'calendar' => array(
			'full' => array( 'desc'=>'If set to 1 it will display a full calendar that shows event names.', 'default' => 0),
			'long_events' => array( 'desc'=>'If set to 1, will show events that last longer than a day.', 'default' => 0),
			'scope' => array( 'default' => 'future')
		),
		//The object is commonly shared by all, so entries above overwrite entries here
		'object' => array(
			'limit' => array( 'desc'=>'', 'default'=>0),
			'scope' => array( 'desc'=>'Choose the time frame of events to show. Accepted values are "future", "past" or "all" events. Additionally you can supply dates (in format of YYYY-MM-DD), either single for events on a specific date or two dates seperated by a comma (e.g. 2010-12-25,2010-12-31) for events ocurring between these dates.', 'default'=>'future'),
			'order' => array( 'desc'=>'Indicates the order of the events. Choose between ASC (ascending) and DESC (descending).', 'default'=>'ASC'),
			'orderby' => array( 'desc'=>'', 'default'=>0),
			'format' => array( 'desc'=>'', 'default'=>''), 
			'category' => array( 'desc'=>'', 'default'=>0), 
			'location' => array( 'desc'=>'', 'default'=>0), 
			'offset' => array( 'desc'=>'', 'default'=>0), 
			'recurrence' => array( 'desc'=>'Will show only events that are recurring (i.e. events that are repeated over different dates).', 'default'=>0),
			'recurring' => array( 'desc'=>'Will only show recurring event templates. Only useful if you know what you\'re doing, use recurrence if you want events that are recurrences.', 'default'=>0),
			'month' => array( 'desc'=>'If set to a month (1 to 12) only events during this month will be retured. Combine with year for a specific year.', 'default'=>''),
			'year' => array( 'desc'=>'If set to a year (e.g. 2010) only events occurring during those dates will be returned. Combine with month for a specific month of the year.', 'default'=>''),
			'array' => array( 'desc'=>'If you supply this as an argument, the returned data will be in an array, no objects (only useful wen using PHP, not shortcodes)', 'default'=>0),
		)
	)
);
?>