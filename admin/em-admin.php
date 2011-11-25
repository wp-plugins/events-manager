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
	//Count pending recurring events
	$events_recurring_num = '';
	$events_recurring_pending_count = EM_Events::count(array('status'=>0, 'recurring'=>1, 'scope'=>'all'));
	//TODO Add flexible permissions
	if($events_recurring_pending_count > 0){
		$events_recurring_num = '<span class="update-plugins count-'.$events_recurring_pending_count.'"><span class="plugin-count">'.$events_recurring_pending_count.'</span></span>';
	}
	$both_pending_count = apply_filters('em_items_pending_count', $events_pending_count + $bookings_pending_count + $events_recurring_pending_count);
	$both_num = ($both_pending_count > 0) ? '<span class="update-plugins count-'.$both_pending_count.'"><span class="plugin-count">'.$both_pending_count.'</span></span>':'';
  	// Add a submenu to the custom top-level menu:
   	$plugin_pages = array();
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
	if( !empty($events_recurring_num) && !empty($submenu['edit.php?post_type='.EM_POST_TYPE_EVENT]) ){ //Submenu Recurring Event Item
		//go through the menu array and modify the events menu if found
		foreach ( (array)$submenu['edit.php?post_type='.EM_POST_TYPE_EVENT] as $key => $submenu_item ) {
			if ( $submenu_item[2] == 'edit.php?post_type=event-recurring' ){
				$submenu['edit.php?post_type='.EM_POST_TYPE_EVENT][$key][0] = $submenu['edit.php?post_type='.EM_POST_TYPE_EVENT][$key][0]. $events_recurring_num;
				break;
			}
		}
	}
	/* Hack! Add location/recurrence isn't possible atm so this is a workaround */
	global $_wp_submenu_nopriv;
	if( $pagenow == 'post-new.php' && !empty($_REQUEST['post_type']) ){
		if( $_REQUEST['post_type'] == EM_POST_TYPE_LOCATION && !empty($_wp_submenu_nopriv['edit.php']['post-new.php']) && current_user_can('edit_locations') ){
			unset($_wp_submenu_nopriv['edit.php']['post-new.php']);
		}
		if( $_REQUEST['post_type'] == 'event-recurring' && !empty($_wp_submenu_nopriv['edit.php']['post-new.php']) && current_user_can('edit_recurring_events') ){
			unset($_wp_submenu_nopriv['edit.php']['post-new.php']);
		}
	}
}
add_action('admin_menu','em_admin_menu');

function em_ms_admin_menu(){
	add_menu_page( 'events-manager', __('Events Manager','dbem'), 'activate_plugins', 'events-manager-options', 'em_ms_admin_options_page', plugins_url('includes/images/calendar-16.png', dirname(dirname(__FILE__)).'/events-manager.php') );
}
add_action('network_admin_menu','em_ms_admin_menu');

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
					<p><?php echo sprintf ( __( 'Uh Oh! For some reason WordPress could not create an events page for you (or you just deleted it). Not to worry though, all you have to do is create an empty page, name it whatever you want, and select it as your events page in your <a href="%s">settings page</a>. Sorry for the extra step! If you know what you are doing, you may have done this on purpose, if so <a href="%s">ignore this message</a>', 'dbem'), EM_ADMIN_URL .'&amp;page=events-manager-options', $_SERVER['REQUEST_URI'].$dismiss_link_joiner.'em_dismiss_events_page=1' ); ?></p>
				</div>
				<?php		
			}
		}
		
		if( defined('EMP_VERSION') && EMP_VERSION < EM_PRO_MIN_VERSION ){ 
			?>
			<div id="em_page_error" class="updated">
				<p><?php _e('There is a newer version of Events Manager Pro which is required for this current version of Events Manager. Please go to the plugin website and download the latest update.','dbem'); ?></p>
			</div>
			<?php
		}
	
		if( is_multisite() && !empty($_REQUEST['page']) && $_REQUEST['page']=='events-manager-options' && is_super_admin() && get_option('dbem_ms_update_nag') ){
			if( !empty($_GET['disable_dbem_ms_update_nag']) ){
				delete_site_option('dbem_ms_update_nag');
			}else{
				?>
				<div id="em_page_error" class="updated">
					<p><?php echo sprintf(__('MultiSite options have moved <a href="%s">here</a>. <a href="%s">Dismiss message</a>','dbem'),admin_url().'network/admin.php?page=events-manager-options', $_SERVER['REQUEST_URI'].'&amp;disable_dbem_ms_update_nag=1'); ?></p>
				</div>
				<?php
			}
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