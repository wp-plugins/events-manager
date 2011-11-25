<?php

class EM_Category_Taxonomy{
	function init(){
		if( !is_admin() ){
			add_filter('archive_template', array('EM_Category_Taxonomy','template'));
			add_filter('category_template', array('EM_Category_Taxonomy','template'));
			add_filter('parse_query', array('EM_Category_Taxonomy','parse_query'));
		}
	}
	/**
	 * Overrides archive pages e.g. locations, events, event categories, event tags based on user settings
	 * @param string $template
	 * @return string
	 */
	function template($template){
		global $wp_query;
		if( is_archive() ){
			if( !empty($wp_query->queried_object->taxonomy) && $wp_query->queried_object->taxonomy == EM_TAXONOMY_CATEGORY && get_option('dbem_cp_categories_formats', true)){
				$template = locate_template(array('page.php','index.php'),false);
				add_filter('the_content', array('EM_Category_Taxonomy','the_content'));
				$wp_query->posts = array();
				$wp_query->posts[0] = new stdClass();
				$wp_query->posts[0]->post_title = $wp_query->queried_object->name;
				$wp_query->posts[0]->post_content = '';
				$wp_query->post = $wp_query->posts[0];
				$wp_query->post_count = 1;
				$wp_query->found_posts = 1;
				$wp_query->max_num_pages = 1;
				//echo "<pre>"; print_r($wp_query); echo "</pre>";
			}
		}
		return $template;
	}
	
	function the_content($content){
		global $wp_query, $EM_Category;
		$EM_Category = new EM_Category($wp_query->queried_object);
		ob_start();
		em_locate_template('templates/category-single.php',true);
		return ob_get_clean();	
	}
	
	function parse_query( ){
		global $wp_query;
		if( !empty($wp_query->tax_query->queries[0]['taxonomy']) &&  $wp_query->tax_query->queries[0]['taxonomy'] == EM_TAXONOMY_CATEGORY) {
		  	if( get_option('dbem_categories_default_archive_orderby') == 'title'){
		  		$wp_query->query_vars['orderby'] = 'title';
		  	}else{
			  	$wp_query->query_vars['orderby'] = 'meta_value_num';
			  	$wp_query->query_vars['meta_key'] = get_option('dbem_categories_default_archive_orderby','_start_ts');
		  	}
			$wp_query->query_vars['order'] = get_option('dbem_categories_default_archive_order','ASC');
		}
	}
}
EM_Category_Taxonomy::init();