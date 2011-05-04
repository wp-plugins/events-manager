<?php 
/**
 * Display function for the support page. here we can give links to forums and special upgrade instructions e.g. migration features 
 */
function em_admin_help_page(){
	global $wpdb;
	?>
	<div class="wrap">
		<div id="icon-events" class="icon32"><br /></div>
		<h2><?php _e('Getting Help for Events Manager','dbem'); ?></h2>
		<p>
			For further information on using this plugin, please view the <a href="http://wp-events-plugin.com/documentation/">documentation pages</a>. If you can't find what you're looking for in the documentation, you may find help on our <a href="http://wp-events-plugin.com/forums/">support forums</a>. 
		</p>
		<div class="em-docs">
			<h2><?php _e('Placeholders for customizing event pages','dbem'); ?></h2>
			<p><?php echo sprintf( __("In the <a href='%s'>settings page</a>, you'll find various textboxes where you can edit how event information looks, such as for event and location lists. Using the placeholders below, you can choose what information should be displayed.",'dbem'), 'admin.php?page=events-manager-options'); ?></p>
			<h3><a name="event-placeholders"><?php _e('Event Related Placeholders','dbem'); ?></a></h3>
			<?php echo em_docs_placeholders( array('type'=>'events') ); ?>
			<h3><a name="category-placeholders"><?php _e('Category Related Placeholders','dbem'); ?></a></h3>
			<?php echo em_docs_placeholders( array('type'=>'categories') ); ?>
			<h3><a name="location-placeholders"><?php _e('Location Related Placeholders','dbem'); ?></a></h3>
			<?php echo em_docs_placeholders( array('type'=>'locations') ); ?>
			<h3><a name="booking-placeholders"><?php _e('Booking Related Placeholders','dbem'); ?></a></h3>
			<?php echo em_docs_placeholders( array('type'=>'bookings') ); ?>
		</div>
		<?php
		//Is this a previously imported installation? 
		$old_table_name = $wpdb->prefix.'dbem_events';
		if( $wpdb->get_var("SHOW TABLES LIKE '$old_table_name'") == $old_table_name ){
			?>
			<hr style="margin:30px 10px;" />
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
	</div>
	<?php
}
?>