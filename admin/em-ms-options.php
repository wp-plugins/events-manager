<?php

/**
 * Displays network-related options in the network admin section
 * @uses em_options_save() to save settings 
 */
function em_ms_admin_options_page() {
	global $wpdb;
	//TODO place all options into an array
	$events_placeholders = '<a href="'.EM_ADMIN_URL .'&amp;events-manager-help#event-placeholders">'. __('Event Related Placeholders','dbem') .'</a>';
	$locations_placeholders = '<a href="'.EM_ADMIN_URL .'&amp;events-manager-help#location-placeholders">'. __('Location Related Placeholders','dbem') .'</a>';
	$bookings_placeholders = '<a href="'.EM_ADMIN_URL .'&amp;events-manager-help#booking-placeholders">'. __('Booking Related Placeholders','dbem') .'</a>';
	$categories_placeholders = '<a href="'.EM_ADMIN_URL .'&amp;events-manager-help#category-placeholders">'. __('Category Related Placeholders','dbem') .'</a>';
	$events_placeholder_tip = " ". sprintf(__('This accepts %s and %s placeholders.','dbem'),$events_placeholders, $locations_placeholders);
	$locations_placeholder_tip = " ". sprintf(__('This accepts %s placeholders.','dbem'), $locations_placeholders);
	$categories_placeholder_tip = " ". sprintf(__('This accepts %s placeholders.','dbem'), $categories_placeholders);
	$bookings_placeholder_tip = " ". sprintf(__('This accepts %s, %s and %s placeholders.','dbem'), $bookings_placeholders, $events_placeholders, $locations_placeholders);
	
	$save_button = '<tr><th>&nbsp;</th><td><p class="submit" style="margin:0px; padding:0px; text-align:right;"><input type="submit" id="dbem_options_submit" name="Submit" value="'. __( 'Save Changes', 'dbem') .' ('. __('All','dbem') .')" /></p></ts></td></tr>';
	//Do some multisite checking here for reuse
	?>	
	<script type="text/javascript" charset="utf-8">
		jQuery(document).ready(function($){
			var close_text = '<?php _e('Collapse All','dbem'); ?>';
			var open_text = '<?php _e('Expand All','dbem'); ?>';
			var open_close = $('<a href="#" style="display:block; float:right; clear:right; margin:10px;">'+open_text+'</a>');
			$('#em-options-title').before(open_close);
			open_close.click( function(e){
				e.preventDefault();
				if($(this).text() == close_text){
					$(".postbox").addClass('closed');
					$(this).text(open_text);
				}else{
					$(".postbox").removeClass('closed');
					$(this).text(close_text);
				} 
			});
			$(".postbox > h3").click(function(){ $(this).parent().toggleClass('closed'); });
			$(".postbox").addClass('closed');
			//For rewrite titles
			$('input:radio[name=dbem_disable_title_rewrites]').live('change',function(){
				checked_check = $('input:radio[name=dbem_disable_title_rewrites]:checked');
				if( checked_check.val() == 1 ){
					$('#dbem_title_html_row').show();
				}else{
					$('#dbem_title_html_row').hide();	
				}
			});
			$('input:radio[name=dbem_disable_title_rewrites]').trigger('change');
			$('.nav-tab-wrapper .nav-tab').click(function(){
				$('.nav-tab-wrapper .nav-tab').removeClass('nav-tab-active');
				el = $(this);
				elid = el.attr('id');
				$('.em-menu-group').hide(); 
				$('.'+elid).show();
				el.addClass('nav-tab-active');
				$(".postbox").addClass('closed');
				open_close.text(open_text);
			});
		});
	</script>
	<style type="text/css">.postbox h3 { cursor:pointer; }</style>
	<div class="wrap">		
		<div id='icon-options-general' class='icon32'><br /></div>
		<h2 class="nav-tab-wrapper">
			<a href="#" id="em-menu-general" class="nav-tab nav-tab-active"><?php _e('General','dbem'); ?></a>
			<a href="#" id="em-menu-pages" class="nav-tab"><?php _e('Pages','dbem'); ?></a>
		</h2>
		<h3 id="em-options-title"><?php _e ( 'Event Manager Options', 'dbem' ); ?></h3>
		<form id="em-options-form" method="post" action="">
			<div class="metabox-holder">         
			<!-- // TODO Move style in css -->
			<div class='postbox-container' style='width: 99.5%'>
			<div id="">
		  
		  	<div class="em-menu-general em-menu-group">
				<div  class="postbox " >
					<div class="handlediv" title="<?php __('Click to toggle', 'dbem'); ?>"><br /></div><h3><span><?php _e ( 'Multi Site Options', 'dbem' ); ?></span></h3>
					<div class="inside">
			            <table class="form-table">
							<?php 
							em_options_radio_binary ( __( 'Enable global tables mode?', 'dbem'), 'dbem_ms_global_table', __( 'Setting this to yes will make all events save in the main site event tables (EM must also be activated). This allows you to share events across different blogs, such as showing events in your network whilst allowing users to display and manage their events within their own blog. Bear in mind that activating this will mean old events created on the sub-blogs will not be accessible anymore, and if you switch back they will be but new events created during global events mode will only remain on the main site.','dbem' ) );
							em_options_radio_binary ( __( 'Display global events on main blog?', 'dbem'), 'dbem_ms_global_events', __( 'Displays events from all sites on the network by default. You can still restrict events by blog using shortcodes and template tags coupled with the <code>blog</code> attribute. Requires global tables to be turned on.','dbem' ) );
							em_options_radio_binary ( __( 'Link sub-site events directly to sub-site?', 'dbem'), 'dbem_ms_global_events_links', __( 'When displaying global events on the main site you have the option of users viewing the event details on the main site or being directed to the sub-site.','dbem' ) );
							echo $save_button;
							?>
						</table>
						    
					</div> <!-- . inside --> 
				</div> <!-- .postbox --> 
				
				<?php 
				//including shared MS/non-MS boxes
				em_admin_option_box_caps();
				em_admin_option_box_image_sizes();
				em_admin_option_box_email();
				em_admin_option_box_anon_events();
				?>
				
				<?php do_action('em_ms_options_page_footer'); ?>
			</div> <!-- .em-menu-general -->
			
		  	<div class="em-menu-pages em-menu-group" style="display:none;">				
		  	
			</div> <!-- .em-menu-pages -->

			<p class="submit">
				<input type="submit" id="dbem_options_submit" name="Submit" value="<?php _e ( 'Save Changes' )?>" />
				<input type="hidden" name="em-submitted" value="1" />
				<input type="hidden" name="_wpnonce" value="<?php echo wp_create_nonce('events-manager-options'); ?>" />
			</p>  
			
			</div> <!-- .metabox-sortables -->
			</div> <!-- .postbox-container -->
			
			</div> <!-- .metabox-holder -->	
		</form>
	</div>
	<?php
}
?>