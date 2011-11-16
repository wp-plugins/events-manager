<?php
class EM_Event_Post {
	function init(){
		global $wp_query;
		//Front Side Modifiers
		if( !is_admin() ){
			add_filter('the_content', array('EM_Event_Post','the_content'));
		}
		add_action('parse_query', array('EM_Event_Post','parse_query'));
	}	
	
	function the_content( $content ){
		global $post;
		if( $post->post_type == EM_POST_TYPE_EVENT ){
			$post = em_get_event($post);
			if( is_archive() ){
				$content = $post->output(get_option('dbem_event_list_item_format'));
			}else{
				$content = $post->output_single();
			}
		}
		return $content;
	}
	
	function parse_query( ){
		global $wp_query;
		if( $wp_query->query_vars['post_type'] == EM_POST_TYPE_EVENT && !in_array($wp_query->query_vars['post_status'],array('trash','pending','draft'))) {
			//Let's deal with the scope - default is future
			if( is_admin() ){
				$scope = $wp_query->query_vars['scope'] = (!empty($_REQUEST['scope'])) ? $_REQUEST['scope']:'future';
			}else{
				$scope = $wp_query->query_vars['scope'] = (!empty($_REQUEST['scope'])) ? $_REQUEST['scope']:'all'; //otherwise we'll get 404s for past events
			}
			$query = array();
			$time = current_time('timestamp');
			if ( preg_match ( "/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/", $scope ) ) {
				
			}elseif ($scope == "future"){
				$today = strtotime(date('Y-m-d', $time));
				if( get_option('dbem_events_current_are_past') ){
					$query[] = array( 'key' => '_start_ts', 'value' => $today, 'compare' => '>=' );
				}else{
					$query[] = array( 'key' => '_end_ts', 'value' => $today, 'compare' => '>=' );
				}
			}elseif ($scope == "past"){
				$today = strtotime(date('Y-m-d', $time));
				if( get_option('dbem_events_current_are_past') ){
					$query[] = array( 'key' => '_start_ts', 'value' => $today, 'compare' => '<' );
				}else{
					$query[] = array( 'key' => '_end_ts', 'value' => $today, 'compare' => '<' );
				}
			}elseif ($scope == "today"){
				$today = strtotime(date('Y-m-d', $time));
				if( get_option('dbem_events_current_are_past') ){
					//date must be only today
					$query[] = array( 'key' => '_start_ts', 'value' => $today, 'compare' => '=');
				}else{
					$query[] = array( 'key' => '_start_ts', 'value' => $today, 'compare' => '<=' );
					$query[] = array( 'key' => '_end_ts', 'value' => $today, 'compare' => '>=' );
				}
			}elseif ($scope == "tomorrow"){
				$tomorrow = strtotime(date('Y-m-d',$time+60*60*24));
				if( get_option('dbem_events_current_are_past') ){
					//date must be only tomorrow
					$query[] = array( 'key' => '_start_ts', 'value' => $tomorrow, 'compare' => '=');
				}else{
					$query[] = array( 'key' => '_start_ts', 'value' => $tomorrow, 'compare' => '<=' );
					$query[] = array( 'key' => '_end_ts', 'value' => $tomorrow, 'compare' => '>=' );
				}
			}elseif ($scope == "month"){
				$start_month = strtotime(date('Y-m-d',$time));
				$end_month = strtotime(date('Y-m-t',$time));
				if( get_option('dbem_events_current_are_past') ){
					$query[] = array( 'key' => '_start_ts', 'value' => array($start_month,$end_month), 'type' => 'numeric', 'compare' => 'BETWEEN');
				}else{
					$query[] = array( 'key' => '_start_ts', 'value' => $end_month, 'compare' => '<=' );
					$query[] = array( 'key' => '_end_ts', 'value' => $start_month, 'compare' => '>=' );
				}
			}elseif ($scope == "next-month"){
				$start_month_timestamp = strtotime('+1 month', $time); //get the end of this month + 1 day
				$start_month = strtotime(date('Y-m-1',$start_month_timestamp));
				$end_month = strtotime(date('Y-m-t',$start_month_timestamp));
				if( get_option('dbem_events_current_are_past') ){
					$query[] = array( 'key' => '_start_ts', 'value' => array($start_month,$end_month), 'type' => 'numeric', 'compare' => 'BETWEEN');
				}else{
					$query[] = array( 'key' => '_start_ts', 'value' => $end_month, 'compare' => '<=' );
					$query[] = array( 'key' => '_end_ts', 'value' => $start_month, 'compare' => '>=' );
				}
			}elseif( preg_match('/(\d\d?)\-months/',$scope,$matches) ){ // next x months means this month (what's left of it), plus the following x months until the end of that month.
				$months_to_add = $matches[1];
				$start_month = strtotime(date('Y-m-d',$time));
				$end_month = strtotime(date('Y-m-t',strtotime("+$months_to_add month", $time)));
				if( get_option('dbem_events_current_are_past') ){
					$query[] = array( 'key' => '_start_ts', 'value' => array($start_month,$end_month), 'type' => 'numeric', 'compare' => 'BETWEEN');
				}else{
					$query[] = array( 'key' => '_start_ts', 'value' => $end_month, 'compare' => '<=' );
					$query[] = array( 'key' => '_end_ts', 'value' => $start_month, 'compare' => '>=' );
				}
			}
		  	if( !empty($query) && is_array($query) ){
				$wp_query->query_vars['meta_query'] = $query;
		  	}
		  	$wp_query->query_vars['orderby'] = 'meta_value_num';
		  	$wp_query->query_vars['order'] = 'ASC';
		  	$wp_query->query_vars['meta_key'] = '_start_ts';
		}elseif( $wp_query->query_vars['post_type'] == EM_POST_TYPE_EVENT ){
			$wp_query->query_vars['scope'] = 'all';
			if( $wp_query->query_vars['post_status'] == 'pending' ){
			  	$wp_query->query_vars['orderby'] = 'meta_value_num';
			  	$wp_query->query_vars['order'] = 'ASC';
			  	$wp_query->query_vars['meta_key'] = '_start_ts';
			}
		}
	}
}
EM_Event_Post::init();