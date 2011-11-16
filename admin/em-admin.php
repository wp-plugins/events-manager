<?php
//Admin functions
function em_admin_menu(){
	global $menu, $submenu, $pagenow;
	//Count pending bookings
	$bookings_num = '';
	$bookings_pending_count = apply_filters('em_bookings_pending_count',0);
	if( get_option('dbem_bookings_approval') == 1){ 
		$bookings_pending_count += count(EM_Bookings::get(array('status'=>'0'))->bookings);
	}
	if($bookings_pending_count > 0){
		$bookings_num = '<span class="update-plugins count-'.$bookings_pending_count.'"><span class="plugin-count">'.$bookings_pending_count.'</span></span>';
	}
	//Count pending events
	$events_num = '';
	$events_pending_count = EM_Events::count(array('status'=>0, 'scope'=>'all'));
	//TODO Add flexible permissions
	if($events_pending_count > 0){
		$events_num = '<span class="update-plugins count-'.$events_pending_count.'"><span class="plugin-count">'.$events_pending_count.'</span></span>';
	}
	$both_pending_count = apply_filters('em_items_pending_count', $events_pending_count + $bookings_pending_count);
	$both_num = ($both_pending_count > 0) ? '<span class="update-plugins count-'.$both_pending_count.'"><span class="plugin-count">'.$both_pending_count.'</span></span>':'';
  	// Add a submenu to the custom top-level menu:
   	$plugin_pages = array(); 
   	if( !get_option('dbem_cp_events_recurring_show_menu') ){
		$plugin_pages['recurrences'] = add_submenu_page('edit.php?post_type='.EM_POST_TYPE_EVENT, __('Recurring Events', 'dbem'), __('Recurring Events', 'dbem'), 'edit_recurrences', 'events-manager-recurrences', array('EM_Event_Posts_Admin','recurrence_redirect'));
   	}
   	if( !get_option('dbem_cp_locations_show_menu') ){
		$plugin_pages['locations'] = add_submenu_page('edit.php?post_type='.EM_POST_TYPE_EVENT, __('Locations', 'dbem'), __('Locations', 'dbem'), 'read_others_locations', 'events-manager-locations', array('EM_Event_Posts_Admin','location_redirect'));
   	}
	$plugin_pages['bookings'] = add_submenu_page('edit.php?post_type='.EM_POST_TYPE_EVENT, __('Bookings', 'dbem'), __('Bookings', 'dbem').$bookings_num, 'manage_bookings', 'events-manager-bookings', "em_bookings_page");
	$plugin_pages['options'] = add_submenu_page('edit.php?post_type='.EM_POST_TYPE_EVENT, __('Events Manager Settings','dbem'),__('Settings','dbem'), 'activate_plugins', "events-manager-options", 'em_admin_options_page');
	$plugin_pages['help'] = add_submenu_page('edit.php?post_type='.EM_POST_TYPE_EVENT, __('Getting Help for Events Manager','dbem'),__('Help','dbem'), 'activate_plugins', "events-manager-help", 'em_admin_help_page');
	$plugin_pages = apply_filters('em_create_events_submenu',$plugin_pages);
	//We have to modify the menus manually
	if( !empty($both_num) ){ //Main Event Menu
		//go through the menu array and modify the events menu if found
		foreach ( (array)$menu as $key => $parent_menu ) {
			if ( $parent_menu[2] == 'edit.php?post_type='.EM_POST_TYPE_EVENT ){
				$menu[$key][0] = $menu[$key][0]. $both_num;
				break;
			}
		}
	}
	if( !empty($events_num) && !empty($submenu['edit.php?post_type='.EM_POST_TYPE_EVENT]) ){ //Submenu Event Item
		//go through the menu array and modify the events menu if found
		foreach ( (array)$submenu['edit.php?post_type='.EM_POST_TYPE_EVENT] as $key => $submenu_item ) {
			if ( $submenu_item[2] == 'edit.php?post_type='.EM_POST_TYPE_EVENT ){
				$submenu['edit.php?post_type='.EM_POST_TYPE_EVENT][$key][0] = $submenu['edit.php?post_type='.EM_POST_TYPE_EVENT][$key][0]. $events_num;
				break;
			}
		}
	}
	//highlight location/recurrence menus - hack!
	global $pagenow, $post;
	if( !get_option('dbem_cp_events_recurring_show_menu') ){
		$is_rec_edit_page = ($pagenow == 'edit.php' && !empty($_GET['post_type']) && $_GET['post_type'] == 'event-recurring');
		$is_rec_post_page = (($pagenow == 'post.php' || $pagenow == 'post-new.php' ) && ((!empty($_GET['post_type']) && $_GET['post_type'] == 'event-recurring') || (!empty($_GET['post']) && get_post_type($_GET['post'])== 'event-recurring')));
	}
	if( !get_option('dbem_cp_locations_show_menu') && !$is_rec_edit_page && !$is_rec_edit_page ){ //don't need to proceed if any of the last two were true
		$is_loc_edit_page = ($pagenow == 'edit.php' && !empty($_GET['post_type']) && $_GET['post_type'] == EM_POST_TYPE_LOCATION);
		$is_loc_post_page = (($pagenow == 'post.php' || $pagenow == 'post-new.php' ) && ((!empty($_GET['post_type']) && $_GET['post_type'] == EM_POST_TYPE_LOCATION) || (!empty($_GET['post']) && get_post_type($_GET['post'])== EM_POST_TYPE_LOCATION)));
	}
	if( $is_rec_edit_page || $is_rec_post_page || $is_loc_edit_page || $is_loc_post_page ){
		foreach( (array)$submenu['edit.php?post_type='.EM_POST_TYPE_EVENT] as $key => $submenu_item ){
			if( $submenu_item[2] == 'events-manager-recurrences' && ($is_rec_edit_page || $is_rec_post_page) ){
				$submenu['edit.php?post_type='.EM_POST_TYPE_EVENT][$key][0] = "<strong style='color:black'>".$submenu['edit.php?post_type='.EM_POST_TYPE_EVENT][$key][0]."</strong>";
			}elseif( $submenu_item[2] == 'events-manager-locations' && ($is_loc_edit_page || $is_loc_post_page) ){
				$submenu['edit.php?post_type='.EM_POST_TYPE_EVENT][$key][0] = "<strong style='color:black'>".$submenu['edit.php?post_type='.EM_POST_TYPE_EVENT][$key][0]."</strong>";
			}
		}
	}
}
add_action('admin_menu','em_admin_menu');

/**
 * Generate warnings and notices in the admin area
 */
function em_admin_warnings() {
	global $EM_Notices;
	//If we're editing the events page show hello to new user
	$events_page_id = get_option ( 'dbem_events_page' );
	$dismiss_link_joiner = ( count($_GET) > 0 ) ? '&amp;':'?';
	
	if( current_user_can('activate_plugins') ){
		//New User Intro
		if (isset ( $_GET ['disable_hello_to_user'] ) && $_GET ['disable_hello_to_user'] == 'true'){
			// Disable Hello to new user if requested
			update_option('dbem_hello_to_user',0);
		}elseif ( get_option ( 'dbem_hello_to_user' ) ) {
			//FIXME update welcome msg with good links
			$advice = sprintf( __("<p>Events Manager is ready to go! It is highly recommended you read the <a href='%s'>Getting Started</a> guide on our site, as well as checking out the <a href='%s'>Settings Page</a>. <a href='%s' title='Don't show this advice again'>Dismiss</a></p>", 'dbem'), 'http://wp-events-plugin.com/documentation/getting-started/?utm_source=em&utm_medium=plugin&utm_content=installationlink&utm_campaign=plugin_links', EM_ADMIN_URL .'&amp;page=events-manager-options',  $_SERVER['REQUEST_URI'].$dismiss_link_joiner.'disable_hello_to_user=true');
			?>
			<div id="message" class="updated">
				<?php echo $advice; ?>
			</div>
			<?php
		}
	
		//If events page couldn't be created or is missing
		if( !empty($_GET['em_dismiss_events_page']) ){
			update_option('dbem_dismiss_events_page',1);
		}else{
			if ( !get_page($events_page_id) && !get_option('dbem_dismiss_events_page') ){
				?>
				<div id="em_page_error" class="updated">
					<p><?php echo sprintf ( __( 'Uh Oh! For some reason wordpress could not create an events page for you (or you just deleted it). Not to worry though, all you have to do is create an empty page, name it whatever you want, and select it as your events page in your <a href="%s">settings page</a>. Sorry for the extra step! If you know what you are doing, you may have done this on purpose, if so <a href="%s">ignore this message</a>', 'dbem'), EM_ADMIN_URL .'&amp;page=events-manager-options', $_SERVER['REQUEST_URI'].$dismiss_link_joiner.'em_dismiss_events_page=1' ); ?></p>
				</div>
				<?php		
			}
		}
		
		if( defined('EMP_VERSION') && EMP_VERSION < EM_PRO_MIN_VERSION ){ 
			?>
			<div id="em_page_error" class="updated">
				<p><?php __('There is a newer version of Events Manager Pro which is required for this current version of Events Manager. Please go to the plugin website and download the latest update.','dbem'); ?></p>
			</div>
			<?php
		}
	}
	//Warn about EM page edit
	if ( preg_match( '/(post|page).php/', $_SERVER ['SCRIPT_NAME']) && isset ( $_GET ['action'] ) && $_GET ['action'] == 'edit' && isset ( $_GET ['post'] ) && $_GET ['post'] == "$events_page_id") {
		$message = sprintf ( __ ( "This page corresponds to <strong>Events Manager</strong> events page. Its content will be overriden by Events Manager, although if you include the word CONTENTS (exactly in capitals) and surround it with other text, only CONTENTS will be overwritten. If you want to change the way your events look, go to the <a href='%s'>settings</a> page. ", 'dbem' ), EM_ADMIN_URL .'&amp;page=events-manager-options' );
		$notice = "<div class='error'><p>$message</p></div>";
		echo $notice;
	}
	echo $EM_Notices;		
}
add_action ( 'admin_notices', 'em_admin_warnings', 100 );

/**
 * Creates a wp-admin style navigation. All this does is wrap some html around the em_paginate function result to make it style correctly in the admin area.
 * @param string $link
 * @param int $total
 * @param int $limit
 * @param int $page
 * @param int $pagesToShow
 * @return string
 * @uses em_paginate()
 */
function em_admin_paginate($total, $limit, $page=1, $vars=false){				
	$return = '<div class="tablenav-pages">';
	$events_nav = paginate_links( array(
		'base' => add_query_arg( 'pno', '%#%' ),
		'format' => '',
		'total' => ceil($total / $limit),
		'current' => $page,
		'add_args' => $vars
	));
	$return .= sprintf( '<span class="displaying-num">' . __( 'Displaying %s&#8211;%s of %s', 'dbem') . ' </span>%s',
		number_format_i18n( ( $page - 1 ) * $limit + 1 ),
		number_format_i18n( min( $page * $limit, $total ) ),
		number_format_i18n( $total ),
		$events_nav
	);
	$return .= '</div>';
	return apply_filters('em_admin_paginate',$return,$total,$limit,$page,$vars);
}

/**
 * Called by admin_print_styles-(hook|page) action, created when adding menu items in events-manager.php  
 */
function em_admin_load_styles() {
	add_thickbox();
	wp_enqueue_style('em-ui-css', plugins_url('includes/css/jquery-ui-1.8.13.custom.css',dirname(__FILE__)));
	wp_enqueue_style('events-manager-admin', plugins_url('includes/css/events_manager_admin.css',dirname(__FILE__)));
}
?>