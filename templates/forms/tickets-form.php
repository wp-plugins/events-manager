<?php 
/* 
 * Used in multiple (default) ticket mode. This is the form that appears as an overlay when a user chooses to create or edit a ticket in their event bookings.
 */ 
?>
<div id="em-tickets-form" style="display:none" title="<?php _e('Create a ticket', 'dbem'); ?>">
	<form action="" method="post">
		<fieldset>
			<div><label><?php _e('Name','dbem'); ?></label><input type="text" name="ticket_name" /></div>
			<div><label><?php _e('Description','dbem') ?></label><br /><textarea name="ticket_description"></textarea></div>
			<div><label><?php _e('Price','dbem') ?></label><input type="text" name="ticket_price" /></div>
			<div>
				<label><?php _e('Available ticket spaces','dbem') ?></label><input type="text" name="ticket_spaces" /><br />
			</div><br />
			<div><label><?php _e('Start date of ticket availability','dbem') ?></label><input type="hidden" name="ticket_start" class="start" /><input type="text" name="ticket_start_pub" class="start-loc" /></div>
			<div><label><?php _e('End date of ticket availability','dbem') ?></label><input type="hidden" name="ticket_end" class="end" /><input type="text" name="ticket_end_pub" class="end-loc" /></div>
			<div><label><?php _e('Minimum tickets required per booking','dbem') ?></label><input type="text" name="ticket_min" /></div>
			<div><label><?php _e('Maximum tickets required per booking','dbem') ?></label><input type="text" name="ticket_max" /></div>
			<?php do_action('em_tickets_edit_form_fields'); //do not delete, add your own fields here ?>
			<input type="hidden" name="ticket_id" />
			<input type="hidden" name="event_id" />
			<input type="hidden" name="prev_slot" />
		</fieldset>
	</form>
</div>