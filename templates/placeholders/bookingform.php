<?php  
/* @var $EM_Event EM_Event */   
global $EM_Notices;
	$booked_places_options = array();
	for ( $i = 1; $i <= 10; $i++ ) {
		$booking_spaces = (!empty($_POST['booking_spaces']) && $_POST['booking_spaces'] == $i) ? 'selected="selected"':'';
		array_push($booked_places_options, "<option value='$i' $booking_spaces>$i</option>");
	}
	$EM_Tickets = $EM_Event->get_bookings()->get_tickets();					
?>
<div id="em-booking">
	<a name="em-booking"></a>
	<?php // We are firstly checking if the user has already booked a ticket at this event, if so offer a link to view their bookings. ?>
	<?php if( $EM_Event->get_bookings()->has_booking() ): ?>
		<p><?php echo sprintf(__('You are currently attending this event. <a href="%s">Manage my bookings</a>','dbem'), em_get_my_bookings_url()); ?></p>
	<?php elseif( !$EM_Event->rsvp ): ?>
		<p><?php _e('Online bookings are not available for this event.','dbem'); ?></p>
	<?php elseif( $EM_Event->start < current_time('timestamp') ): ?>
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
				<?php if( count($EM_Tickets->tickets) > 1 || get_option('dbem_bookings_tickets_single_form') ): ?>
					<table class="em-tickets" cellspacing="0" cellpadding="0">
						<tr>
							<td><?php _e('Ticket Type','dbem') ?></td>
							<?php if( !$EM_Event->is_free() ): ?>
							<td><?php _e('Price','dbem') ?></td>
							<?php endif; ?>
							<td><?php _e('Spaces','dbem') ?></td>
						</tr>
						<?php foreach( $EM_Tickets->tickets as $EM_Ticket ): ?>
							<?php if( $EM_Ticket->is_available() || get_option('dbem_bookings_tickets_show_unavailable') ): ?>
							<tr class="em-ticket" id="em-ticket-<?php echo $EM_Ticket->id; ?>">
								<td><?php echo $EM_Ticket->output_property('name'); ?><?php if(!empty($EM_Ticket->description)) :?><br><span class="ticket-desc"><?php echo $EM_Ticket->description; ?></span><?php endif; ?></td>
								<?php if( !$EM_Event->is_free() ): ?>
								<td><?php echo $EM_Ticket->get_price(true); ?></td>
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
							<?php do_action('em_booking_form_tickets_loop', $EM_Ticket); ?>
							<?php endif; ?>
						<?php endforeach; ?>
					</table>		
				<?php endif; ?>
				<?php do_action('em_booking_form_after_tickets'); ?>
				<?php if( is_user_logged_in() || (get_option('dbem_bookings_anonymous') && !is_user_logged_in()) ): ?>
				<div class='em-booking-form-details'>
				
					<?php $EM_Ticket = $EM_Tickets->get_first(); ?>
					
					<?php if( is_object($EM_Ticket) && count($EM_Tickets->tickets) == 1 && !get_option('dbem_bookings_tickets_single_form') ): ?>
						<?php if(!empty($EM_Ticket->description)) :?><p class="ticket-desc"><?php echo $EM_Ticket->description; ?></p><?php endif; ?>
						<?php if( !$EM_Event->is_free() ): ?>
							<p>
								<label><?php _e('Price','dbem') ?></label><strong><?php echo $EM_Ticket->get_price(true); ?></strong>
							</p>
						<?php endif; ?>						
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
					<?php if( !is_user_logged_in() && apply_filters('em_booking_form_show_register_form',true) ): ?>
						<?php //User can book an event without registering, a username will be created for them based on their email and a random password will be created. ?>
						<input type="hidden" name="register_user" value="1" />
						<p>
							<label for='user_name'><?php _e('Name','dbem') ?></label>
							<input type="text" name="user_name" id="user_name" class="input" />
						</p>
						<p>
							<label for='user_phone'><?php _e('Phone','dbem') ?></label>
							<input type="text" name="user_phone" id="user_phone"" class="input" />
						</p>
						<p>
							<label for='user_email'><?php _e('E-mail','dbem') ?></label> 
							<input type="text" name="user_email" id="user_email" class="input"  />
						</p>
						<?php do_action('register_form'); ?>					
					<?php endif; ?>		
					<p>
						<label for='booking_comment'><?php _e('Comment', 'dbem') ?></label>
						<textarea name='booking_comment'><?php echo !empty($_POST['booking_comment']) ? $_POST['booking_comment']:'' ?></textarea>
					</p>
					<?php do_action('em_booking_form_after_user_details'); ?>					
					<div class="em-booking-buttons">
						<?php echo apply_filters('em_booking_form_buttons', '<input type="submit" class="em-booking-submit" value="'.__('Send your booking', 'dbem').'" />', $EM_Event); ?>
					 	<input type='hidden' name='action' value='booking_add'/>
					 	<input type='hidden' name='event_id' value='<?php echo $EM_Event->id; ?>'/>
					 	<input type='hidden' name='_wpnonce' value='<?php echo wp_create_nonce('booking_add'); ?>'/>
					</div>
				</div>
			<?php else: ?>
			<p class="em-booking-form-details"><?php _e('You must log in before you make a booking.','dbem'); ?></p>
			<?php endif; ?>
			</form>	
			<?php if( !is_user_logged_in() && get_option('dbem_bookings_login_form') ): ?>
				<div class="em-booking-login">
	        		<form class="em-booking-login-form" action="<?php echo site_url('wp-login.php', 'login_post'); ?>" method="post">
			            <p><?php _e('Log in if you already have an account with us.','dbem'); ?>
			            <p>
			            	<label><?php _e( 'Username','dbem' ) ?></label>
	                        <input type="text" name="log" class="input" value="" />
						</p>
						<p>
							<label><?php _e( 'Password','dbem' ) ?></label>
			                <input type="password" name="pwd" class="input" value="" />
			            </p>
			            <?php do_action('login_form'); ?>
	                    <input type="submit" name="wp-submit" id="em_wp-submit" value="<?php _e('Log In', 'dbem'); ?>" tabindex="100" />
	                    <input name="rememberme" type="checkbox" id="em_rememberme" value="forever" /> <label><?php _e( 'Remember Me','dbem' ) ?></label>
                        <input type="hidden" name="redirect_to" value="http://<?php echo $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'] ?>#em-booking" />
	                    <br />
	                    <?php
                            //Signup Links
                            if ( get_option('users_can_register') ) {
                                echo "<br />";  
                                if ( function_exists('bp_get_signup_page') ) { //Buddypress
                                	$register_link = bp_get_signup_page();
                                }elseif ( file_exists( ABSPATH."/wp-signup.php" ) ) { //MU + WP3
                                    $register_link = site_url('wp-signup.php', 'login');
                                } else {
                                    $register_link = site_url('wp-login.php?action=register', 'login');
                                }
                                ?>
                                <a href="<?php echo $register_link ?>"><?php _e('Sign Up','dbem') ?></a>&nbsp;&nbsp;|&nbsp;&nbsp; 
                                <?php
                            }
                        ?>	                    
	                    <a href="<?php echo site_url('wp-login.php?action=lostpassword', 'login') ?>" title="<?php _e('Password Lost and Found', 'dbem') ?>"><?php _e('Lost your password?', 'dbem') ?></a>                        
	               </form>
				</div>
			<?php endif; ?>
			<br class="clear" style="clear:left;" />
		<?php elseif( count($EM_Tickets->tickets) == 0 ): ?>
			<div><?php _e('No more tickets available at this time.','dbem'); ?></div>
		<?php endif; ?>  
	<?php endif; ?>
</div>
<?php ob_start(); ?>
<script type="text/javascript">
	jQuery(document).ready( function($){
		var em_booking_doing_ajax = false;
		$('#em-booking-form').submit( function(e){
			e.preventDefault();
			$.ajax({
				url: EM.ajaxurl,
				dataType: 'jsonp',
				data:$('#em-booking-form').serializeArray(),
				type:'post',
				beforeSend: function(formData, jqForm, options) {
					if(em_booking_doing_ajax){
						alert('<?php _e('Please wait while the booking is being submitted.','dbem'); ?>');
						return false;
					}
					em_booking_doing_ajax = true;
					$('.em-booking-message').remove();
					$('#em-booking').append('<div id="em-loading"></div>');
				},
				success : function(response, statusText, xhr, $form) {
					$('#em-loading').remove();
					if(response.result){
						$('#em-booking-form').fadeOut( 'fast', function(){
							$('<div class="em-booking-message-success em-booking-message">'+response.message+'</div>').insertBefore('#em-booking-form');
							$(this).remove();
							$('.em-booking-login').remove();
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
					em_booking_doing_ajax = false;
				}
			});	
			return false;
		});							
	});
</script>
<?php echo apply_filters( 'em_booking_form_js', ob_get_clean(), $EM_Event ); ?>