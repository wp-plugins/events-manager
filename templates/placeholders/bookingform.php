<?php  
	/* @var $this EM_Event */   
	global $EM_Notices;
	$booked_places_options = array();
	for ( $i = 1; $i <= 10; $i++ ) {
		$booking_spaces = (!empty($_POST['booking_spaces']) && $_POST['booking_spaces'] == $i) ? 'selected="selected"':'';
		array_push($booked_places_options, "<option value='$i' $booking_spaces>$i</option>");
	}
	$EM_Tickets = $this->get_bookings()->get_tickets();					
?>
<div id="em-booking">
	<a name="em-booking"></a>
	<?php // We are firstly checking if the user has already booked a ticket at this event, if so offer a link to view their bookings. ?>
	<?php if( $this->get_bookings()->has_booking() ): ?>
		<p><?php echo sprintf(__('You are currently attending this event. <a href="%s">Manage my bookings</a>','dbem'), em_get_my_bookings_url()); ?></p>
	<?php elseif( $this->start < current_time('timestamp') ): ?>
		<p><?php _e('Bookings are closed for this event.','dbem'); ?></p>
	<?php else: ?>
		<?php echo $EM_Notices; ?>		
		<?php if( count($EM_Tickets->tickets) > 0) : ?>
			<?php //Tickets exist, so we show a booking form. ?>
			<form id='em-booking-form' name='booking-form' method='post' action=''>
				<?php do_action('em_booking_form_before_tickets'); ?>
				<?php 
					/* Show Tickets
					 * If there's more than one ticket, we show them in a list. 
					 * If not, we'll only show one ddm for the number of seats and maybe a 
					 * price indicator if this event entrance has a price. 
					 * If for some reason you have more than one free ticket and no paid ones, 
					 * the price collumn will be ommited.
					 */
				?>
				<?php if( count($EM_Tickets->tickets) > 1 ): ?>
					<table class="em-tickets" cellspacing="0" cellpadding="0">
						<tr>
							<td><?php _e('Ticket Type','dbem') ?></td>
							<?php if( !$this->is_free() ): ?>
							<td><?php _e('Price','dbem') ?></td>
							<?php endif; ?>
							<td><?php _e('Spaces','dbem') ?></td>
						</tr>
						<?php foreach( $EM_Tickets->tickets as $EM_Ticket ): ?>
							<?php if( $EM_Ticket->is_available() || get_option('dbem_bookings_tickets_show_unavailable') ): ?>
							<tr>
								<td><?php echo $EM_Ticket->output_property('name'); ?></td>
								<?php if( !$this->is_free() ): ?>
								<td><?php echo $EM_Ticket->output_property('price'); ?></td>
								<?php endif; ?>
								<td>
									<?php 
										$spaces_options = $EM_Ticket->get_spaces_options();
										if( $spaces_options ){
											echo $spaces_options;
										}else{
											echo "<strong>".__('N/A','dbem')."</strong>";
										}
									?>
								</td>
							</tr>
							<?php endif; ?>
						<?php endforeach; ?>
					</table>		
				<?php endif; ?>
				<?php do_action('em_booking_form_after_tickets'); ?>
				<div class='em-booking-form-details'>
				
					<?php $EM_Ticket = $EM_Tickets->get_first(); ?>
					<?php if( is_object($EM_Ticket) && count($EM_Tickets->tickets) == 1 ): ?>
					<p>
						<label for='em_tickets'><?php _e('Spaces', 'dbem') ?></label>
						<?php 
							$spaces_options = $EM_Ticket->get_spaces_options(false);
							if( $spaces_options ){
								echo $spaces_options;
							}else{
								echo "<strong>".__('N/A','dbem')."</strong>";
							}
						?>
					</p>	
					<?php endif; ?>
					
					<?php //Here we have extra information required for the booking. ?>
					<?php do_action('em_booking_form_before_user_details'); ?>
					<p>
						<label for='booking_comment'><?php _e('Comment', 'dbem') ?></label>
						<textarea name='booking_comment'><?php echo !empty($_POST['booking_comment']) ? $_POST['booking_comment']:'' ?></textarea>
					</p>
					<?php do_action('em_booking_form_after_user_details'); ?>
					<?php if( get_option('dbem_bookings_anonymous') && !is_user_logged_in() ): ?>
						<?php //User can book an event without registering, a username will be created for them based on their email and a random password will be created. ?>
						<input type="hidden" name="register_user" value="1" />
						<p>
							<label for='user_email'><?php _e('E-mail','login-with-ajax') ?></label>
							<input type="text" name="user_email" id="user_email" class="input" size="25" tabindex="20" />
						</p>
						<?php do_action('register_form'); ?>					
					<?php endif; ?>							
					<div class="em-booking-buttons">
						<?php echo apply_filters('em_booking_form_buttons', '<input type="submit" class="em-booking-submit" value="'.__('Send your booking', 'dbem').'" />', $this); ?>
					 	<input type='hidden' name='action' value='booking_add'/>
					 	<input type='hidden' name='event_id' value='<?php echo $this->id; ?>'/>
					 	<input type='hidden' name='_wpnonce' value='<?php echo wp_create_nonce('booking_add'); ?>'/>
					</div>
				</div>
			</form>		
			<?php if( !is_user_logged_in() ): ?>
				<div class="em-booking-login">
	        		<form class="em-booking-login-form" action="<?php echo site_url('wp-login.php', 'login_post'); ?>" method="post">
			            <p><?php _e('Log in if you already have an account with us.','dbem'); ?>
			            <p>
			            	<label><?php _e( 'Username','login-with-ajax' ) ?></label>
	                        <input type="text" name="log" class="input" value="" />
						</p>
						<p>
							<label><?php _e( 'Password','login-with-ajax' ) ?></label>
			                <input type="password" name="pwd" class="input" value="" />
			            </p>
			            <?php do_action('login_form'); ?>
	                    <input type="submit" name="wp-submit" id="em_wp-submit" value="<?php _e('Log In'); ?>" tabindex="100" />
	                    <input name="rememberme" type="checkbox" id="em_rememberme" value="forever" /> <label><?php _e( 'Remember Me','login-with-ajax' ) ?></label>
	                    <br />
	                    <a id="LoginWithAjax_Links_Remember" href="<?php echo site_url('wp-login.php?action=lostpassword', 'login') ?>" title="<?php _e('Password Lost and Found') ?>"><?php _e('Lost your password?') ?></a>
	               </form>
				</div>
			<?php endif; ?>
			<br class="clear"/>
		<?php elseif( count($EM_Tickets->tickets) == 0 ): ?>
			<div><?php _e('No more tickets available at this time.','dbem'); ?></div>
		<?php endif; ?>  
	<?php endif; ?>
</div>
<?php ob_start(); ?>
<script type="text/javascript">
	jQuery(document).ready( function($){
		$('#em-booking-form').ajaxForm({
			url: EM.ajaxurl,
			dataType: 'jsonp',
			beforeSubmit: function(formData, jqForm, options) {
				$('.em-booking-message').remove();
				$('#em-booking-form').append('<div id="em-loading"></div>');
			},
			success : function(response, statusText, xhr, $form) {
				$('#em-loading').remove();
				if(response.result){
					$('#em-booking-form').fadeOut( 'fast', function(){
						$('<div class="em-booking-message-success em-booking-message">'+response.message+'</div>').insertBefore('#em-booking-form');
						$(this).remove();
					} );
				}else{
					if( response.errors != '' ){
						if( $.isArray() ){
							var error_msg;
							response.errors.each(function(i, el){ 
								error_msg = error_msg + el;
							});
							$('<div class="em-booking-message-error em-booking-message">'+response.errors+'</div>').insertBefore('#em-booking-form');
						}else{
							$('<div class="em-booking-message-error em-booking-message">'+response.errors+'</div>').insertBefore('#em-booking-form');							
						}
					}else{
						$('<div class="em-booking-message-error em-booking-message">'+response.message+'</div>').insertBefore('#em-booking-form');
					}					
				}
			}
		});								
	});
</script>
<?php echo apply_filters( 'em_booking_form_js', ob_get_clean(), $this ); ?>