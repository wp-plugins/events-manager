<?php
	global $EM_Event; 
	
	$days_names = array (1 => __ ( 'Monday' ), 2 => __ ( 'Tuesday' ), 3 => __ ( 'Wednesday' ), 4 => __ ( 'Thursday' ), 5 => __ ( 'Friday' ), 6 => __ ( 'Saturday' ), 0 => __ ( 'Sunday' ) );
	
	if ( !$EM_Event->id || $EM_Event->is_recurring() ) : ?>
	<input id="event-recurrence" type="checkbox" name="repeated_event" value="1" <?php echo ( $EM_Event->is_recurring() ) ? 'checked="checked"':'' ; ?> />
	<?php _e ( 'This event repeats', 'dbem' ); ?> 
		<select id="recurrence-frequency" name="recurrence_freq">
			<?php
				$freq_options = array ("daily" => __ ( 'Daily', 'dbem' ), "weekly" => __ ( 'Weekly', 'dbem' ), "monthly" => __ ( 'Monthly', 'dbem' ) );
				em_option_items ( $freq_options, $EM_Event->freq ); 
			?>
		</select>
		<?php _e ( 'every', 'dbem' )?>
		<input id="recurrence-interval" name='recurrence_interval' size='2' value='<?php echo $EM_Event->interval ; ?>' />
		<span class='interval-desc' id="interval-daily-singular">
		<?php _e ( 'day', 'dbem' )?>
		</span> <span class='interval-desc' id="interval-daily-plural">
		<?php _e ( 'days', 'dbem' ) ?>
		</span> <span class='interval-desc' id="interval-weekly-singular">
		<?php _e ( 'week on', 'dbem'); ?>
		</span> <span class='interval-desc' id="interval-weekly-plural">
		<?php _e ( 'weeks on', 'dbem'); ?>
		</span> <span class='interval-desc' id="interval-monthly-singular">
		<?php _e ( 'month on the', 'dbem' )?>
		</span> <span class='interval-desc' id="interval-monthly-plural">
		<?php _e ( 'months on the', 'dbem' )?>
		</span> 
	<p class="alternate-selector" id="weekly-selector">
		<?php
			$saved_bydays = ($EM_Event->is_recurring()) ? explode ( ",", $EM_Event->byday ) : array(); 
			em_checkbox_items ( 'recurrence_bydays[]', $days_names, $saved_bydays ); 
		?>
	</p>
	<p class="alternate-selector" id="monthly-selector" style="display:inline;">
		<select id="monthly-modifier" name="recurrence_byweekno">
			<?php
				$weekno_options = array ("1" => __ ( 'first', 'dbem' ), '2' => __ ( 'second', 'dbem' ), '3' => __ ( 'third', 'dbem' ), '4' => __ ( 'fourth', 'dbem' ), '-1' => __ ( 'last', 'dbem' ) ); 
				em_option_items ( $weekno_options, $EM_Event->byweekno  ); 
			?>
		</select>
		<select id="recurrence-weekday" name="recurrence_byday">
			<?php em_option_items ( $days_names, $EM_Event->byday  ); ?>
		</select>
		<?php _e('of each month','dbem'); ?>
		&nbsp;
	</p>
	
	<p id="recurrence-tip">
		<?php _e ( 'Check if your event happens more than once according to a regular pattern', 'dbem' )?>
	</p>
<?php elseif( $EM_Event->is_recurrence() ) : ?>
		<p>
			<?php echo $EM_Event->get_recurrence_description(); ?>
			<br />
			<a href="<?php bloginfo ( 'wpurl' )?>/wp-admin/admin.php?page=events-manager-event&amp;event_id=<?php echo $EM_Event->recurrence_id; ?>">
			<?php _e ( 'Reschedule', 'dbem' ); ?>
			</a>
			<input type="hidden" name="recurrence_id" value="<?php echo $EM_Event->recurrence_id; ?>" />
		</p>
<?php else : ?>
	<p><?php _e ( 'This is\'t a recurrent event', 'dbem' ) ?></p>
<?php endif; ?>