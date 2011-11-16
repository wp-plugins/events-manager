<?php
global $EM_Event, $post, $localised_date_formats;
$locale_code = substr ( get_locale (), 0, 2 );
$localised_date_format = $localised_date_formats[$locale_code];
$hours_locale_regexp = "H:i";
// Setting 12 hours format for those countries using it
if (preg_match ( "/en|sk|zh|us|uk/", $locale_code ))
	$hours_locale_regexp = "h:iA";

$required = "<i>*</i>";
?>
<div class="event-form-when" id="em-form-when">
	<div>
		<?php _e ( 'Starts on ', 'dbem' ); ?>					
		<input id="em-date-start-loc" type="text" />
		<input id="em-date-start" type="hidden" name="event_start_date" value="<?php echo $EM_Event->event_start_date ?>" />
		<?php _e('from','dbem'); ?>
		<input id="start-time" type="text" size="8" maxlength="8" name="event_start_time" value="<?php echo date( $hours_locale_regexp, strtotime($EM_Event->event_start_time) ); ?>" />
		<?php _e('to','dbem'); ?>
		<input id="end-time" type="text" size="8" maxlength="8" name="event_end_time" value="<?php echo date( $hours_locale_regexp, strtotime($EM_Event->event_end_time) ); ?>" />
		<?php _e('and ends on','dbem'); ?>
		<input id="em-date-end-loc" type="text" />
		<input id="em-date-end" type="hidden" name="event_end_date" value="<?php echo $EM_Event->event_end_date ?>" />
	</div>
	<span id='event-date-explanation'>
	<?php _e( 'This event spans every day between the beginning and end date, with start/end times applying to each day.', 'dbem' ); ?>
	</span>
</div>  
<?php if( false && get_option('dbem_recurrence_enabled') && $EM_Event->is_recurrence() ) : //in future, we could enable this and then offer a detach option alongside, which resets the recurrence id and removes the attachment to the recurrence set ?>
<input type="hidden" name="recurrence_id" value="<?php echo $EM_Event->recurrence_id; ?>" />
<?php endif;