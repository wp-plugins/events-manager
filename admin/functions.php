<?php
/*Random Admin Functions*/

//This adds the tinymce editor
function dbem_tinymce(){
	add_action( 'admin_print_footer_scripts', 'wp_tiny_mce', 25 );
	wp_enqueue_script('post');
	if ( user_can_richedit() )
		wp_enqueue_script('editor');
	add_thickbox();
	wp_enqueue_script('media-upload');
	wp_enqueue_script('word-count');
	wp_enqueue_script('quicktags');	
}
add_action ( 'admin_init', 'dbem_tinymce' );

// ADMIN CSS for debug
function dbem_admin_css() {
	$css = "
	<style type='text/css'>
	.debug{
		color: green;
		background: #B7F98C;
		margin: 15px;
		padding: 10px;
		border: 1px solid #629948;
	}
	.switch-tab {
		background: #aaa;
		width: 100px;
		float: right;
		text-align: center;
		margin: 3px 1px 0 5px;
		padding: 2px;
	}
	.switch-tab a {
		color: #fff;
		text-decoration: none;
	}
	.switch-tab a:hover {
		color: #D54E21;
		
	} 
	#events-pagination {
		text-align: center; 
		
	}
	#events-pagination a {
		margin: 0 20px 0 20px;
		text-decoration: none;
		width: 80px;
		padding: 3px 0; 
		background: #FAF4B7;
		border: 1px solid #ccc;
		border-top: none;
	} 
	#new-event {
		float: left;
	 
	}
	</style>";
	echo $css;
}
add_action ( 'admin_print_scripts', 'dbem_admin_css' );