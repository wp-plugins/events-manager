<?php
//Admin functions

/**
 * Generate warnings and notices in the admin area
 */
function em_admin_warnings() {
	//If we're editing the events page show hello to new user
	$events_page_id = get_option ( 'dbem_events_page' );
	if (isset ( $_GET ['disable_hello_to_user'] ) && $_GET ['disable_hello_to_user'] == 'true'){
		// Disable Hello to new user if requested
		update_option ( 'dbem_hello_to_user', 0 );
	}else{
		if ( preg_match( '/(post|page).php/', $_SERVER ['SCRIPT_NAME']) && isset ( $_GET ['action'] ) && $_GET ['action'] == 'edit' && isset ( $_GET ['post'] ) && $_GET ['post'] == "$events_page_id") {
			$message = sprintf ( __ ( "This page corresponds to <strong>Events Manager</strong> events page. Its content will be overriden by <strong>Events Manager</strong>. If you want to display your content, you can can assign another page to <strong>Events Manager</strong> in the the <a href='%s'>Settings</a>. ", 'dbem' ), 'admin.php?page=events-manager-options' );
			$notice = "<div class='error'><p>$message</p></div>";
			echo $notice;
		}
	}
	//If events page couldn't be created
	if( !empty($_GET['em_dismiss_events_page']) ){
		update_option('dbem_dismiss_events_page',1);
	}else{
		if ( !get_page($events_page_id) && !get_option('dbem_dismiss_events_page') ){
			$dismiss_link_joiner = ( count($_GET) > 0 ) ? '&amp;':'?';
			$advice = sprintf ( __( 'Uh Oh! For some reason wordpress could not create an events page for you (or you just deleted it). Not to worry though, all you have to do is create an empty page, name it whatever you want, and select it as your events page in your <a href="%s">options page</a>. Sorry for the extra step! If you know what you are doing, you may have done this on purpose, if so <a href="%s">ignore this message</a>', 'dbem'), get_bloginfo ( 'url' ) . '/wp-admin/admin.php?page=events-manager-options', $_SERVER['REQUEST_URI'].$dismiss_link_joiner.'em_dismiss_events_page=1' );
			?>
			<div id="em_page_error" class="updated">
				<p><?php echo $advice; ?></p>
			</div>
			<?php		
		}
	}
}
add_action ( 'admin_notices', 'em_admin_warnings' );

/**
 * Called by admin_print_scripts-(hook|page) action, created when adding menu items in events-manager.php
 */
function em_admin_load_scripts(){
	//Load the UI items, currently date picker and autocomplete plus dependencies
	//wp_enqueue_script('em-ui-js', WP_PLUGIN_URL.'/events-manager/includes/js/jquery-ui-1.8.5.custom.min.js', array('jquery', 'jquery-ui-core'));
	wp_enqueue_script('em-ui-js', WP_PLUGIN_URL.'/events-manager/includes/js/em_ui.js', array('jquery', 'jquery-ui-core'));
	
	//Add maps
	if( get_option('dbem_gmap_is_active') ){
		wp_enqueue_script('em-google-maps', 'http://maps.google.com/maps/api/js?sensor=false');	
	}
	//Time Entry
	wp_enqueue_script('em-timeentry', WP_PLUGIN_URL.'/events-manager/includes/js/timeentry/jquery.timeentry.js', array('jquery'));	
	
	//Date Picker Locale
	$locale_code = substr ( get_locale (), 0, 2 );
	$locale_file = "/events-manager/includes/js/i18n/jquery.ui.datepicker-$locale_code.js";
	if ( file_exists(WP_PLUGIN_DIR.$locale_file) ) {
		wp_enqueue_script("em-ui-datepicker-$locale_code", WP_PLUGIN_URL.$locale_file, array('em-ui-js'));
	}
	wp_enqueue_script('em-script', WP_PLUGIN_URL.'/events-manager/includes/js/em_admin.js', array('em-ui-js'));
	
	//TinyMCE Editor
	add_action( 'admin_print_footer_scripts', 'wp_tiny_mce', 25 );
	wp_enqueue_script('post');
	if ( user_can_richedit() )
		wp_enqueue_script('editor');
	add_thickbox();
	wp_enqueue_script('media-upload');
	wp_enqueue_script('word-count');
	wp_enqueue_script('quicktags');	
}

/**
 * Called by admin_print_styles-(hook|page) action, created when adding menu items in events-manager.php  
 */
function em_admin_load_styles() {
	add_thickbox();
	wp_enqueue_style('em-ui-css', WP_PLUGIN_URL.'/events-manager/includes/css/jquery-ui-1.7.3.custom.css');
	wp_enqueue_style('events-manager-admin', WP_PLUGIN_URL.'/events-manager/includes/css/events_manager_admin.css');
}

/**
 * Loads script inline due to insertion of php values 
 */
function em_admin_general_script() {
	//TODO clean script up, remove dependency of php so it can be moved to js file.	
	// Check if the locale is there and loads it
	$locale_code = substr ( get_locale (), 0, 2 );	
	$show24Hours = 'true';
	// Setting 12 hours format for those countries using it
	if (preg_match ( "/en|sk|zh|us|uk/", $locale_code ))
		$show24Hours = 'false';
	?>
	<script type="text/javascript">
	 	//<![CDATA[        
	   // TODO: make more general, to support also latitude and longitude (when added)
	
		jQuery(document).ready( function($) {

			function updateIntervalDescriptor () { 
				$(".interval-desc").hide();
				var number = "-plural";
				if ($('input#recurrence-interval').val() == 1 || $('input#recurrence-interval').val() == "")
				number = "-singular"
				var descriptor = "span#interval-"+$("select#recurrence-frequency").val()+number;
				$(descriptor).show();
			}
			function updateIntervalSelectors () {
				$('p.alternate-selector').hide();   
				$('p#'+ $('select#recurrence-frequency').val() + "-selector").show();
			}			
			function updateShowHideRsvp () {
				if($('input#rsvp-checkbox').attr("checked")) {
					$("div#rsvp-data").fadeIn();
				} else {
					$("div#rsvp-data").hide();
				}
			}
			function updateShowHideRecurrence () {
				if( $('input#event-recurrence').attr("checked")) {
					$("#event_recurrence_pattern").fadeIn();
					$("#event-date-explanation").hide();
					$("#recurrence-dates-explanation").show();
					$("h3#recurrence-dates-title").show();
					$("h3#event-date-title").hide();     
				} else {
					$("#event_recurrence_pattern").hide();
					$("#recurrence-dates-explanation").hide();
					$("#event-date-explanation").show();
					$("h3#recurrence-dates-title").hide();
					$("h3#event-date-title").show();   
				}
			}		 
			$("#recurrence-dates-explanation").hide();
			$("#localised-date").show();
			$("#localised-end-date").show();
		
			$("#date-to-submit").hide();
			$("#end-date-to-submit").hide();
			  
		 	$("#start-time").timeEntry({spinnerImage: '', show24Hours: <?php echo $show24Hours; ?> });
			$("#end-time").timeEntry({spinnerImage: '', show24Hours: <?php echo $show24Hours; ?>});
		
			$('input.select-all').change(function(){
			 	if($(this).is(':checked'))
			 	$('input.row-selector').attr('checked', true);
			 	else
			 	$('input.row-selector').attr('checked', false);
			}); 
			
			updateIntervalDescriptor(); 
			updateIntervalSelectors();
			updateShowHideRecurrence();  
			updateShowHideRsvp();
			$('input#event-recurrence').change(updateShowHideRecurrence);  
			$('input#rsvp-checkbox').change(updateShowHideRsvp);
			   
			// recurrency elements   
			$('input#recurrence-interval').keyup(updateIntervalDescriptor);
			$('select#recurrence-frequency').change(updateIntervalDescriptor);
			$('select#recurrence-frequency').change(updateIntervalSelectors);
		    
			// hiding or showing notes according to their content	
			$('.postbox h3').prepend('<a class="togbox">+</a> ');
			$('#event_notes h3').click( function() {
				 $(this).parent().first().toggleClass('closed');
		    });
		
			// users cannot submit the event form unless some fields are filled
		   	function validateEventForm(){
		   		errors = "";
				var recurring = $("input[@name=repeated_event]:checked").val();
				requiredFields= new Array('event_name', 'localised_event_date', 'location_name','location_address','location_town');
				var localisedRequiredFields = {
					'event_name':"<?php _e ( 'Name', 'dbem' )?>", 
					'localised_event_date':"<?php	_e ( 'Date', 'dbem' )?>", 
					'location_name':"<?php _e ( 'Location', 'dbem' )?>",
					'location_address':"<?php _e ( 'Address', 'dbem' )?>",
					'location_town':"<?php _e ( 'Town', 'dbem' )?>"
				};		
				missingFields = new Array;
				for (var i in requiredFields) {
					if ($("input[@name=" + requiredFields[i]+ "]").val() == 0) {
						missingFields.push(localisedRequiredFields[requiredFields[i]]);
						$("input[@name=" + requiredFields[i]+ "]").css('border','2px solid red');
					} else {
						$("input[@name=" + requiredFields[i]+ "]").css('border','1px solid #DFDFDF');				
					}				
			   	}
			
				// 	alert('ciao ' + recurring+ " end: " + $("input[@name=localised_event_end_date]").val());     
			   	if (missingFields.length > 0) {	
				    errors = "<?php _e ( 'Some required fields are missing:', 'dbem' )?> " + missingFields.join(", ") + ".\n";
				}
				if(recurring && $("input[@name=localised_event_end_date]").val() == "") {
					errors = errors +  "<?php _e ( 'Since the event is repeated, you must specify an end date', 'dbem' )?>."; 
					$("input[@name=localised_event_end_date]").css('border','2px solid red');
				} else {
					$("input[@name=localised_event_end_date]").css('border','1px solid #DFDFDF');
				}
				if(errors != "") {
					alert(errors);
					return false;
				}
				return true; 
		   }
		   $('#eventForm').bind("submit", validateEventForm);
		});
		//]]>
	</script>
	<?php
}
?>