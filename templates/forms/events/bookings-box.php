<?php
global $EM_Event;
?>
<p>
	<input id="rsvp-checkbox" name='event_rsvp' value='1' type='checkbox' <?php echo ($EM_Event->rsvp) ? 'checked="checked"' : ''; ?> />
	<?php _e ( 'Enable registration for this event', 'dbem' )?>
</p>
<div id='rsvp-data'>
	<p>
		<?php _e ( 'Spaces','dbem' ); ?> :
		<input id="spaces-input" type="text" name="event_spaces" size='5' value="<?php echo ($EM_Event->get_spaces() > 0) ? $EM_Event->get_spaces():10 ?>" /><br />
		<em><?php _e('This is a limit for total number of booking spaces.','dbem'); ?></em>
	</p>
	<!-- START RSVP Stats -->
	<?php
		if ($EM_Event->rsvp ) {
			$available_spaces = $EM_Event->get_bookings()->get_available_spaces();
			$booked_spaces = $EM_Event->get_bookings()->get_booked_spaces();
				
			if ( count($EM_Event->get_bookings()->bookings) > 0 ) {
				?>
				<p>
					<strong><?php echo __('Available Spaces','dbem').': '.$EM_Event->get_bookings()->get_available_spaces(); ?></strong><br />
					<strong><?php echo __('Confirmed Spaces','dbem').': '.$EM_Event->get_bookings()->get_booked_spaces(); ?></strong><br />
					<strong><?php echo __('Pending Spaces','dbem').': '.$EM_Event->get_bookings()->get_pending_spaces(); ?></strong>
			 	</p>
		 	    
		 	 	<div id='major-publishing-actions'>  
					<div id='publishing-action'> 
						<a id='printable' href='<?php echo get_bloginfo('wpurl') . "/wp-admin/admin.php?page=events-manager-bookings&event_id=".$EM_Event->id ?>'><?php _e('manage bookings','dbem')?></a><br />
						<a target='_blank' href='<?php echo get_bloginfo('wpurl') . "/wp-admin/admin.php?page=events-manager-bookings&action=bookings_report&event_id=".$EM_Event->id ?>'><?php _e('printable view','dbem')?></a>
						<a href='<?php echo get_bloginfo('wpurl') . "/wp-admin/admin.php?page=events-manager-bookings&action=export_csv&event_id=".$EM_Event->id ?>'><?php _e('export csv','dbem')?></a>
						<br class='clear'/>             
			        </div>
					<br class='clear'/>    
				</div>
				<?php                                                     
			} else {
				?>
				<p><em><?php _e('No responses yet!')?></em></p>
				<?php
			} 
		}
	?>
	<!-- END RSVP Stats -->
</div>