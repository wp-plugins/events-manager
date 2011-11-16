<?php
class EM_Event_Posts_Admin{
	function init(){
		global $pagenow;
		if( $pagenow == 'edit.php' && !empty($_REQUEST['post_type']) && $_REQUEST['post_type'] == EM_POST_TYPE_EVENT ){ //only needed for events list
			add_action('admin_head', array('EM_Event_Posts_Admin','admin_head'));
			//collumns
			add_filter('manage_edit-'.EM_POST_TYPE_EVENT.'_columns' , array('EM_Event_Posts_Admin','columns_add'));
			add_filter('manage_'.EM_POST_TYPE_EVENT.'_posts_custom_column' , array('EM_Event_Posts_Admin','columns_output'),10,2 );
		}
		add_action('restrict_manage_posts', array('EM_Event_Posts_Admin','restrict_manage_posts'));
	}
	
	function admin_head(){
		//quick hacks to make event admin table make more sense for events
		?>
		<script type="text/javascript">
			jQuery(document).ready( function($){
				$('.inline-edit-date').prev().css('display','none').next().css('display','none').next().css('display','none');
				$('.em-detach-link').click(function( event ){
					if( !confirm(EM.event_detach_warning) ){
						event.preventDefault();
						return false;
					}
				});
				$('.em-delete-recurrence-link').click(function( event ){
					if( !confirm(EM.delete_recurrence_warning) ){
						event.preventDefault();
						return false;
					}
				});
			});
		</script>
		<style>
			table.fixed{ table-layout:auto !important; }
			.tablenav select[name="m"] { display:none; }
		</style>
		<?php
	}
	
	function restrict_manage_posts(){
		global $wp_query;
		if( $wp_query->query_vars['post_type'] == EM_POST_TYPE_EVENT ){
			?>
			<select name="scope">
				<?php
				$scope = (!empty($wp_query->query_vars['scope'])) ? $wp_query->query_vars['scope']:'future';
				foreach ( em_get_scopes() as $key => $value ) {
					$selected = "";
					if ($key == $scope)
						$selected = "selected='selected'";
					echo "<option value='$key' $selected>$value</option>  ";
				}
				?>
			</select>
			<?php
			//Categories
            $terms = get_terms(EM_TAXONOMY_CATEGORY);	
            // output html for taxonomy dropdown filter
            echo '<select name="'.EM_TAXONOMY_CATEGORY.'" id="'.EM_TAXONOMY_CATEGORY.'" class="postform">';
            echo '<option value="">'.__('View all categories').'&nbsp;</option>';
            foreach ($terms as $term) {
                // output each select option line, check against the last $_GET to show the current option selected
                $selected = (!empty($_GET[EM_TAXONOMY_CATEGORY]) && $_GET[EM_TAXONOMY_CATEGORY] == $term->slug) ? 'selected="selected"':'';
                echo '<option value="'. $term->slug.'" '.$selected.'>'.$term->name.'</option>';
            }
            echo "</select>";
		}
	}
	
	function columns_add($columns) {
	    unset($columns['comments']);
	    unset($columns['date']);
	    unset($columns['author']);
	    return array_merge($columns, array( 
	    	'+'=>'', 
	    	'location' => __('Location'),
	    	'date-time' => __('Date and Time'),
	    	'author' => __('Owner','dbem'),
	    	'extra' => ''
	    ));
	}
	
	function columns_output( $column ) {
		global $post, $EM_Event;
		$EM_Event = em_get_event($post, 'post_id');
		/* @var $post EM_Event */
		switch ( $column ) {
			case '+':
				//get meta value to see if post has location, otherwise
				echo '<a href="'.admin_url().'edit.php?action=event_duplicate&amp;event_id='.$EM_Event->event_id.'">+</a>';
				break;
			case 'location':
				//get meta value to see if post has location, otherwise
				$EM_Location = $EM_Event->get_location();
				if( !empty($EM_Location->location_id) ){
					echo "<strong>" . $EM_Location->location_name . "</strong><br/>" . $EM_Location->location_address . " - " . $EM_Location->location_town;
				}else{
					echo __('None','dbem');
				}
				break;
			case 'date-time':
				//get meta value to see if post has location, otherwise
				$localised_start_date = date_i18n('D d M Y', $EM_Event->start);
				$localised_end_date = date_i18n('D d M Y', $EM_Event->end);
				echo $localised_start_date;
				echo ($localised_end_date != $localised_start_date) ? " - $localised_end_date":'';
				echo "<br />";
				//TODO Should 00:00 - 00:00 be treated as an all day event? 
				echo substr ( $EM_Event->start_time, 0, 5 ) . " - " . substr ( $EM_Event->end_time, 0, 5 );
				break;
			case 'extra':
				if( get_option('dbem_rsvp_enabled') == 1 && $EM_Event->rsvp == 1 && !$EM_Event->is_recurring()){
					?>
					<a href="<?php echo EM_ADMIN_URL; ?>&amp;page=events-manager-bookings&amp;event_id=<?php echo $EM_Event->id ?>"><?php echo __("Bookings",'dbem'); ?></a> &ndash;
					<?php _e("Booked",'dbem'); ?>: <?php echo $EM_Event->get_bookings()->get_booked_spaces()."/".$EM_Event->get_spaces(); ?>
					<?php if( get_option('dbem_bookings_approval') == 1 ): ?>
						| <?php _e("Pending",'dbem') ?>: <?php echo $EM_Event->get_bookings()->get_pending_spaces(); ?>
					<?php endif;
				}
				if ( $EM_Event->is_recurrence() ) {
					echo ($EM_Event->rsvp == 1) ? '<br />':'';
					$recurrence_delete_confirm = __('WARNING! You will delete ALL recurrences of this event, including booking history associated with any event in this recurrence. To keep booking information, go to the relevant single event and save it to detach it from this recurrence series.','dbem');
					?>
					<strong>
					<?php echo $EM_Event->get_recurrence_description(); ?> <br />
					</strong>
					<div class="row-actions">
						<a href="<?php echo admin_url(); ?>post.php?action=edit&amp;post=<?php echo $EM_Event->get_event_recurrence()->post_id ?>"><?php _e ( 'Reschedule Events', 'dbem' ); ?></a> | <span class="trash"><a class="em-delete-recurrence-link" href="<?php echo get_delete_post_link($EM_Event->get_event_recurrence()->post_id); ?>"><?php _e('Delete','dbem'); ?></a></span> | <a class="em-detach-link" href="<?php echo $EM_Event->get_detach_url(); ?>"><?php _e('Detach', 'dbem'); ?></a>
					</div>
					<?php
				}
				
				break;
		}
	}
}
EM_Event_Posts_Admin::init();

/*
 * Recurring Events
 */
class EM_Event_Recurring_Posts_Admin{
	function init(){
		global $pagenow;
		if( $pagenow == 'edit.php' && !empty($_REQUEST['post_type']) && $_REQUEST['post_type'] == 'event-recurring' ){
			add_action('admin_notices',array('EM_Event_Recurring_Posts_Admin','admin_notices'));
			add_action('admin_head', array('EM_Event_Recurring_Posts_Admin','admin_head'));
			//collumns
			add_filter('manage_edit-event-recurring_columns' , array('EM_Event_Recurring_Posts_Admin','columns_add'));
			add_filter('manage_posts_custom_column' , array('EM_Event_Recurring_Posts_Admin','columns_output'),10,1 );
		}
		if( !empty($_GET['page']) && $_GET['page'] == 'events-manager-recurrences' && !get_option('dbem_cp_events_recurring_show_menu')){
			add_action('admin_init',array('EM_Event_Recurring_Posts_Admin','redirect'));
		}
	}
	
	function admin_notices(){
		$warning = sprintf(__( 'Modifications to these events will cause all recurrences of each event to be deleted and recreated and previous bookings will be deleted! You can edit individual recurrences and detach them from recurring events by visiting the <a href="%s">events page</a>.', 'dbem' ), admin_url().'edit.php?post_type='.EM_POST_TYPE_EVENT);
		?><div class="updated"><p><?php echo $warning; ?></p></div><?php
	}
	
	function redirect(){
		wp_redirect(admin_url().'edit.php?post_type=event-recurring'); exit();
	}
	
	function admin_head(){
		//quick hacks to make event admin table make more sense for events
		?>
		<script type="text/javascript">
			jQuery(document).ready( function($){
				$('.inline-edit-date').prev().css('display','none').next().css('display','none').next().css('display','none');
				if(!EM.recurrences_menu){
					$('#menu-posts-'+EM.event_post_type+', #menu-posts-'+EM.event_post_type+' > a').addClass('wp-has-current-submenu');
				}
			});
		</script>
		<style>
			table.fixed{ table-layout:auto !important; }
			.tablenav select[name="m"] { display:none; }
		</style>
		<?php
	}
	
	function columns_add($columns) {
	    unset($columns['comments']);
	    unset($columns['date']);
	    unset($columns['author']);
	    return array_merge($columns, array(
	    	'location' => __('Location'),
	    	'date-time' => __('Date and Time'),
	    	'author' => __('Owner','dbem'),
	    ));
	}

	
	function columns_output( $column ) {
		global $post, $EM_Event;
		if( $post->post_type == 'event-recurring' ){
			$post = $EM_Event = em_get_event($post);
			/* @var $post EM_Event */
			switch ( $column ) {
				case 'location':
					//get meta value to see if post has location, otherwise
					$EM_Location = $EM_Event->get_location();
					if( !empty($EM_Location->location_id) ){
						echo "<strong>" . $EM_Location->location_name . "</strong><br/>" . $EM_Location->location_address . " - " . $EM_Location->location_town;
					}else{
						echo __('None','dbem');
					}
					break;
				case 'date-time':
					echo $EM_Event->get_recurrence_description();
					break;
			}
		}
	}	
}
EM_Event_Recurring_Posts_Admin::init();