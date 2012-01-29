<?php 
/* 
 * This file generates the input fields for an event with a single ticket and settings set to not show a table for single tickets (default setting)
 * If you would like to modify this file, copy it to wp-content/themes/yourtheme/plugins/events-manager/forms/bookingform
 * and you will be able to override this file without it getting overwritten each time you update the plugin.
 */

/* @var $EM_Ticket EM_Ticket */
/* @var $EM_Event EM_Event */
global $allowedposttags;
?>
<?php if(!empty($EM_Ticket->ticket_description)): //show description if there is one ?>
	<p class="ticket-desc"><?php echo wp_kses($EM_Ticket->ticket_description,$allowedposttags); ?></p>
<?php endif; ?>
<?php if( !$EM_Event->is_free() ): //only show price if event is not free ?>
	<p><label><?php _e('Price','dbem') ?></label><strong><?php echo $EM_Ticket->get_price(true); ?></strong></p>
<?php endif; ?>
<?php do_action('em_booking_form_ticket_field', $EM_Ticket); //do not delete ?>
<?php if( $EM_Ticket->get_available_spaces() > 1 && ( empty($EM_Ticket->ticket_max) || $EM_Ticket->ticket_max > 1 ) ): //more than one space available ?>				
	<p>
		<label for='em_tickets'><?php _e('Spaces', 'dbem') ?></label>
		<?php 
			$default = !empty($_REQUEST['em_tickets'][$EM_Ticket->ticket_id]['spaces']) ? $_REQUEST['em_tickets'][$EM_Ticket->ticket_id]['spaces']:0;
			$spaces_options = $EM_Ticket->get_spaces_options(false,$default);
			if( $spaces_options ){
				echo $spaces_options;
			}else{
				echo "<strong>".__('N/A','dbem')."</strong>";
			}
		?>
	</p>
<?php else: //if only one space or ticket max spaces per booking is 1 ?>
	<input type="hidden" name="em_tickets[<?php echo $EM_Ticket->ticket_id ?>][spaces]" value="1" class="em-ticket-select" />
<?php endif; ?>
<?php do_action('em_booking_form_ticket_footer', $EM_Ticket); //do not delete ?>