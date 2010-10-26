<?php
//TODO expand em_category to be like other classes
class EM_Category extends EM_Object {
	
	function get( $category_id = false ){
		global $wpdb;
		$categories_table = $wpdb->prefix.EM_CATEGORIES_TABLE; 
		if( $category_id === false ){
			//No id supplied, so we return everything
			return $wpdb->get_results("SELECT * FROM $categories_table", ARRAY_A);
		}else{
			$sql = "SELECT * FROM $categories_table WHERE category_id ='$category_id'";   
		 	$category = $wpdb->get_row($sql, ARRAY_A);
			return $category;
		}
	}
}
?>