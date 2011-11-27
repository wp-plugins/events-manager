<?php
class EM_Location_Post {
	function init(){
		//Front Side Modifiers
		if( !is_admin() ){
			//override single page with formats? 
			if( get_option('dbem_cp_locations_formats') ){
				add_filter('the_content', array('EM_Location_Post','the_content'));
			}
			//display as page template?
			if( get_option('dbem_cp_locations_template_page') ){
				add_filter('single_template',array('EM_Location_Post','single_template'));
			}
			add_action('parse_query', array('EM_Location_Post','parse_query'));
		}
	}	
	
	/**
	 * Overrides the default post format of a location and can display a location as a page, which uses the page.php template.
	 * @param string $template
	 * @return string
	 */
	function single_template($template){
		global $post;
		if( $post->post_type == EM_POST_TYPE_LOCATION ){
			$template = locate_template(array('page.php','index.php'),false);
		}
		return $template;
	}
	
	function the_content( $content ){
		global $post;
		if( $post->post_type == EM_POST_TYPE_LOCATION ){
			$post = em_get_location($post);
			if( is_archive() ){
				$content = $post->output(get_option('dbem_location_list_item_format'));
			}else{
				$content = $post->output_single();
			}
		}
		return $content;
	}
	
	function parse_query( ){
		global $wp_query;
		if( $wp_query->query_vars['post_type'] == EM_POST_TYPE_LOCATION && empty($wp_query->query_vars['location']) ) {
		  	if( get_option('dbem_locations_default_archive_orderby') == 'title'){
		  		$wp_query->query_vars['orderby'] = 'title';
		  	}else{
			  	$wp_query->query_vars['orderby'] = 'meta_value_num';
			  	$wp_query->query_vars['meta_key'] = get_option('dbem_locations_default_archive_orderby','_location_name');	  		
		  	}
			$wp_query->query_vars['order'] = get_option('dbem_locations_default_archive_orderby','ASC');
		}
	}
}
EM_Location_Post::init();