<?php
/* Figure this out
if( post_type_exists('location') || post_type_exists('location') ){
}else{
	function em_post_type_warning(){
		?>
		<div class="error"><p><?php _e('There is another plugin that has registered the post type "event" or "location". You must disable these plugins for Events Manager to work properly.','dbem') ?></p></div>
		<?php
	}
	add_action('admin_notices','em_post_type_warning');
	add_action('network_admin_notices','em_post_type_warning');
}
*/

function wp_events_plugin_template($template){
	global $post;
	if( $post->post_type == EM_POST_TYPE_EVENT && get_option('dbem_cp_events_template_page',true) ){
		$template = locate_template(array('page.php','index.php'),false);
	}
	if( $post->post_type == EM_POST_TYPE_LOCATION && get_option('dbem_cp_locations_template_page',true) ){
		$template = locate_template(array('page.php','index.php'),false);
	}
	return $template;
}
add_filter('single_template','wp_events_plugin_template');


//preset tags and slugs, overridable in wp-config.php
if( !defined('EM_POST_TYPE_EVENT') ) define('EM_POST_TYPE_EVENT','event');
if( !defined('EM_POST_TYPE_EVENT_SLUG') ) define('EM_POST_TYPE_EVENT_SLUG',get_option('dbem_cp_events_slug', 'events'));
if( !defined('EM_POST_TYPE_LOCATION') ) define('EM_POST_TYPE_LOCATION','location');
if( !defined('EM_POST_TYPE_LOCATION_SLUG') ) define('EM_POST_TYPE_LOCATION_SLUG',get_option('dbem_cp_locations_slug', 'locations'));
if( !defined('EM_TAXONOMY_CATEGORY') ) define('EM_TAXONOMY_CATEGORY','event-categories');
if( !defined('EM_TAXONOMY_CATEGORY_SLUG') ) define('EM_TAXONOMY_CATEGORY_SLUG',get_option('dbem_taxonomy_category_slug', 'event-categories'));
if( !defined('EM_TAXONOMY_TAG') ) define('EM_TAXONOMY_TAG','event-tags');
if( !defined('EM_TAXONOMY_TAG_SLUG') ) define('EM_TAXONOMY_TAG_SLUG',get_option('dbem_taxonomy_tag_slug', 'event-tags'));

/**
 * Temporary hack, show_ui has to be true for non-admins.... not sure why
 * @return boolean
 */
function dbem_cp_show_extra_menu(){
	return false;
}
add_action('pre_option_dbem_cp_events_recurring_show_menu','dbem_cp_show_extra_menu');
add_action('pre_option_dbem_cp_locations_show_menu','dbem_cp_show_extra_menu');

add_action('init','wp_events_plugin_init',1);
function wp_events_plugin_init(){	
	define('EM_ADMIN_URL',admin_url().'edit.php?post_type='.EM_POST_TYPE_EVENT); //we assume the admin url is absolute with at least one querystring
	register_taxonomy(EM_TAXONOMY_TAG,array(EM_POST_TYPE_EVENT,'event-recurring'),array( 
		'hierarchical' => false, 
		'public' => true,
		'show_ui' => true,
		'query_var' => true,
		'rewrite' => array('slug' => EM_TAXONOMY_TAG_SLUG),
		//'update_count_callback' => '',
		//'show_tagcloud' => true,
		//'show_in_nav_menus' => true,
		'label' => __('Event Tags'),
		'singular_label' => __('Event Tag'),
		'labels' => array(
			'name'=>__('Event Tags','dbem'),
			'singular_name'=>__('Event Tag','dbem'),
			'search_items'=>__('Search Event Tags','dbem'),
			'popular_items'=>__('Popular Event Tags','dbem'),
			'all_items'=>__('All Event Tags','dbem'),
			'parent_items'=>__('Parent Event Tags','dbem'),
			'parent_item_colon'=>__('Parent Event Tag:','dbem'),
			'edit_item'=>__('Edit Event Tag','dbem'),
			'update_item'=>__('Update Event Tag','dbem'),
			'add_new_item'=>__('Add New Event Tag','dbem'),
			'new_item_name'=>__('New Event Tag Name','dbem'),
			'seperate_items_with_commas'=>__('Seperate event tags with commas','dbem'),
			'add_or_remove_items'=>__('Add or remove events','dbem'),
			'choose_from_the_most_used'=>__('Choose from most used event tags','dbem'),
		),
		'capabilites' => array(
			'manage_terms' => 'manage_categories',
			'edit_terms' => 'manage_categories',
			'delete_terms' => 'manage_categories',
			'assign_terms' => 'manage_categories',
		)
	));	
	register_taxonomy(EM_TAXONOMY_CATEGORY,array(EM_POST_TYPE_EVENT,'event-recurring'),array( 
		'hierarchical' => true, 
		'public' => true,
		'show_ui' => true,
		'query_var' => true,
		'rewrite' => array('slug' => EM_TAXONOMY_CATEGORY_SLUG, 'hierarchical' => true),
		//'update_count_callback' => '',
		//'show_tagcloud' => true,
		//'show_in_nav_menus' => true,
		'label' => __('Event Categories','dbem'),
		'singular_label' => __('Event Category','dbem'),
		'labels' => array(
			'name'=>__('Event Categories','dbem'),
			'singular_name'=>__('Event Category','dbem'),
			'search_items'=>__('Search Event Categories','dbem'),
			'popular_items'=>__('Popular Event Categories','dbem'),
			'all_items'=>__('All Event Categories','dbem'),
			'parent_items'=>__('Parent Event Categories','dbem'),
			'parent_item_colon'=>__('Parent Event Category:','dbem'),
			'edit_item'=>__('Edit Event Category','dbem'),
			'update_item'=>__('Update Event Category','dbem'),
			'add_new_item'=>__('Add New Event Category','dbem'),
			'new_item_name'=>__('New Event Category Name','dbem'),
			'seperate_items_with_commas'=>__('Seperate event categories with commas','dbem'),
			'add_or_remove_items'=>__('Add or remove events','dbem'),
			'choose_from_the_most_used'=>__('Choose from most used event categories','dbem'),
		),
		'capabilites' => array(
			'manage_terms' => 'manage_categories',
			'edit_terms' => 'manage_categories',
			'delete_terms' => 'manage_categories',
			'assign_terms' => 'manage_categories',
		)
	));	
	register_post_type(EM_POST_TYPE_EVENT, array(	
		'public' => true,
		'hierarchical' => false,
		'show_ui' => true,
		'show_in_menu' => true,
		'show_in_nav_menus'=>true,
		'can_export' => true,
		'exclude_from_search' => false,
		'publicly_queryable' => true,
		'rewrite' => array('slug' => EM_POST_TYPE_EVENT_SLUG,'with_front'=>false),
		'has_archive' => get_option('dbem_cp_events_has_archive', false),
		'supports' => apply_filters('em_cp_event_supports', array('custom-fields','title','editor','excerpt','comments','thumbnail','author')),
		'capability_type' => EM_POST_TYPE_EVENT,
		'capabilities' => array(
			'edit_post'=>'edit_events',
			'edit_posts'=>'edit_events',
			'edit_others_posts'=>'edit_others_events',
			'publish_posts'=>'publish_events',
			'read_post'=>'read_post',
			'read_private_posts'=>'read_private_posts',
			'delete_post'=>'delete_events'
		),
		'label' => __('Events','dbem'),
		'description' => __('Display events on your blog.','dbem'),
		'labels' => array (
			'name' => __('Events','dbem'),
			'singular_name' => __('Event','dbem'),
			'menu_name' => __('Events','dbem'),
			'add_new' => __('Add Event','dbem'),
			'add_new_item' => __('Add New Event','dbem'),
			'edit' => __('Edit','dbem'),
			'edit_item' => __('Edit Event','dbem'),
			'new_item' => __('New Event','dbem'),
			'view' => __('View','dbem'),
			'view_item' => __('View Event','dbem'),
			'search_items' => __('Search Events','dbem'),
			'not_found' => __('No Events Found','dbem'),
			'not_found_in_trash' => __('No Events Found in Trash','dbem'),
			'parent' => __('Parent Event','dbem'),
		),
		'menu_icon' => plugins_url('includes/images/calendar-16.png', __FILE__)
	));
	register_post_type('event-recurring', array(	
		'public' => false,
		'show_ui' => true,
		'show_in_menu' => get_option('dbem_cp_events_recurring_show_menu',false),
		'show_in_nav_menus'=>false,
		'publicly_queryable' => false,
		'exclude_from_search' => true,
		'has_archive' => false,
		'can_export' => true,
		'hierarchical' => false,
		'supports' => apply_filters('em_cp_event_supports', array('custom-fields','title','editor','excerpt','comments','thumbnail','author')),
		'capability_type' => 'event',
		'rewrite' => array('slug' => 'events-recurring','with_front'=>false),
		'capabilities' => array(
			'edit_post'=>'edit_recurrences',
			'edit_posts'=>'edit_recurrences',
			'edit_others_posts'=>'edit_others_events',
			'publish_posts'=>'publish_events',
			'read_post'=>'read_post',
			'read_private_posts'=>'read_private_posts',
			'delete_post'=>'delete_events'
		),
		'label' => __('Recurring Events','dbem'),
		'description' => __('Recurring Events Template','dbem'),
		'labels' => array (
			'name' => __('Recurring Events','dbem'),
			'singular_name' => __('Recurring Event','dbem'),
			'menu_name' => __('Recurring Events','dbem'),
			'add_new' => __('Add Recurring Event','dbem'),
			'add_new_item' => __('Add New Recurring Event','dbem'),
			'edit' => __('Edit','dbem'),
			'edit_item' => __('Edit Recurring Event','dbem'),
			'new_item' => __('New Recurring Event','dbem'),
			'view' => __('View','dbem'),
			'view_item' => __('Add Recurring Event','dbem'),
			'search_items' => __('Search Recurring Events','dbem'),
			'not_found' => __('No Recurring Events Found','dbem'),
			'not_found_in_trash' => __('No Recurring Events Found in Trash','dbem'),
			'parent' => __('Parent Recurring Event','dbem'),
		)
	));
	register_post_type(EM_POST_TYPE_LOCATION, array(	
		'public' => true,
		'hierarchical' => false,
		'show_ui' => true,
		'show_in_menu' => get_option('dbem_cp_locations_show_menu',false),
		'show_in_nav_menus'=>true,
		'can_export' => true,
		'exclude_from_search' => false,
		'publicly_queryable' => true,
		'rewrite' => array('slug' => EM_POST_TYPE_LOCATION_SLUG, 'with_front'=>false),
		'query_var' => true,
		'has_archive' => get_option('dbem_cp_locations_has_archive', false),
		'supports' => apply_filters('em_cp_location_supports', array('title','editor','excerpt','custom-fields','comments','thumbnail','author')),
		'capability_type' => EM_POST_TYPE_LOCATION,
		'capabilities' => array(
			'edit_post'=>'edit_locations',
			'edit_posts'=>'edit_locations',
			'edit_others_posts'=>'edit_others_locations',
			'publish_posts'=>'edit_locations',
			'read_post'=>'read_post',
			'read_private_posts'=>'read_private_posts',
			'delete_post'=>'delete_locations'
		),
		'label' => __('Locations','dbem'),
		'description' => __('Display locations on your blog.','dbem'),
		'labels' => array (
			'name' => __('Locations','dbem'),
			'singular_name' => __('Location','dbem'),
			'menu_name' => __('Locations','dbem'),
			'add_new' => __('Add Location','dbem'),
			'add_new_item' => __('Add New Location','dbem'),
			'edit' => __('Edit','dbem'),
			'edit_item' => __('Edit Location','dbem'),
			'new_item' => __('New Location','dbem'),
			'view' => __('View','dbem'),
			'view_item' => __('View Location','dbem'),
			'search_items' => __('Search Locations','dbem'),
			'not_found' => __('No Locations Found','dbem'),
			'not_found_in_trash' => __('No Locations Found in Trash','dbem'),
			'parent' => __('Parent Location','dbem'),
		)
	));
}