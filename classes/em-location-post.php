<?php
class EM_Location_Post {
	function init(){
		//Front Side Modifiers
		if( !is_admin() ){
			add_filter('the_content', array('EM_Location_Post','the_content'));
		}
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
}
EM_Location_Post::init();