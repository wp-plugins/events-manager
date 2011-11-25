<?php
/**
 * Controls how events are queried and displayed via the WordPress Custom Post APIs
 * @author marcus
 *
 */
class EM_Event_Post {
	function init(){
		global $wp_query;
		//Front Side Modifiers
		if( !is_admin() ){
			//override single page with formats? 
			if( get_option('dbem_cp_events_formats') ){
				add_filter('the_content', array('EM_Event_Post','the_content'));
			}
			//display as page template?
			if( get_option('dbem_cp_events_template_page') ){
				add_filter('single_template',array('EM_Event_Post','single_template'));
			}
			//Override post template tags
			add_filter('the_date',array('EM_Event_Post','the_date'));
			add_filter('get_the_date',array('EM_Event_Post','the_date'),10,2);
			add_filter('the_category',array('EM_Event_Post','the_category'),10,3);
		}
		add_action('parse_query', array('EM_Event_Post','parse_query'));
	}
	
	/**
	 * Overrides the default post format of an event and can display an event as a page, which uses the page.php template.
	 * @param string $template
	 * @return string
	 */
	function single_template($template){
		global $post;
		if( $post->post_type == EM_POST_TYPE_EVENT ){
			$template = locate_template(array('page.php','index.php'),false);
		}
		return $template;
	}
	
	function the_content( $content ){
		global $post, $EM_Event;
		if( $post->post_type == EM_POST_TYPE_EVENT ){
			$EM_Event = em_get_event($post);
			if( is_archive() || is_search() ){
				if(get_option('dbem_cp_events_archive_formats')){
					$content = $EM_Event->output(get_option('dbem_event_list_item_format'));
				}
			}else{
				//general defaults
				$args = array(				
					'owner' => false,
					'pagination' => 1
				);
				ob_start();
				em_locate_template('templates/event-single.php',true, array('args'=>$args));
				$content = ob_get_clean();
			}
		}
		return $content;
	}
	
	function the_date( $the_date, $d = '' ){
		global $post;
		if( $post->post_type == EM_POST_TYPE_EVENT ){
			$EM_Event = em_get_event($post);
			if ( '' == $d ){
				$the_date = date(get_option('date_format'), $EM_Event->start);
			}else{
				$the_date = date($d, $EM_Event->start);
			}
		}
		return $the_date;
	}
	
	function the_category( $thelist, $separator = '', $parents='' ){
		global $post, $wp_rewrite;
		if( $post->post_type == EM_POST_TYPE_EVENT ){
			$EM_Event = em_get_event($post);
			$categories = $EM_Event->get_categories();
			if( empty($categories) ) return '';
			
			/* Copied from get_the_category_list function, with a few minor edits to make urls work, and removing parent stuff (for now) */
			$rel = ( is_object( $wp_rewrite ) && $wp_rewrite->using_permalinks() ) ? 'rel="category tag"' : 'rel="category"';

			$thelist = '';
			if ( '' == $separator ) {
				$thelist .= '<ul class="post-categories">';
				foreach ( $categories as $category ) {
					$thelist .= "\n\t<li>";
					switch ( strtolower( $parents ) ) {
						case 'multiple':
							$thelist .= '<a href="' . $category->get_url() . '" title="' . esc_attr( sprintf( __( "View all posts in %s" ), $category->name ) ) . '" ' . $rel . '>' . $category->name.'</a></li>';
							break;
						case 'single':
							$thelist .= '<a href="' . $category->get_url() . '" title="' . esc_attr( sprintf( __( "View all posts in %s" ), $category->name ) ) . '" ' . $rel . '>';
							$thelist .= $category->name.'</a></li>';
							break;
						case '':
						default:
							$thelist .= '<a href="' . $category->get_url() . '" title="' . esc_attr( sprintf( __( "View all posts in %s" ), $category->name ) ) . '" ' . $rel . '>' . $category->name.'</a></li>';
					}
				}
				$thelist .= '</ul>';
			} else {
				$i = 0;
				foreach ( $categories as $category ) {
					if ( 0 < $i )
						$thelist .= $separator;
					switch ( strtolower( $parents ) ) {
						case 'multiple':
							$thelist .= '<a href="' . $category->get_url() . '" title="' . esc_attr( sprintf( __( "View all posts in %s" ), $category->name ) ) . '" ' . $rel . '>' . $category->name.'</a>';
							break;
						case 'single':
							$thelist .= '<a href="' . $category->get_url() . '" title="' . esc_attr( sprintf( __( "View all posts in %s" ), $category->name ) ) . '" ' . $rel . '>';
							$thelist .= "$category->name</a>";
							break;
						case '':
						default:
							$thelist .= '<a href="' . $category->get_url() . '" title="' . esc_attr( sprintf( __( "View all posts in %s" ), $category->name ) ) . '" ' . $rel . '>' . $category->name.'</a>';
					}
					++$i;
				}
			}
			/* End copying */
		}
		return $thelist;
	}
	
	function parse_query( ){
		global $wp_query;
		if( ($wp_query->query_vars['post_type'] == EM_POST_TYPE_EVENT || $wp_query->query_vars['post_type'] == 'event-recurring') && !in_array($wp_query->query_vars['post_status'],array('trash','pending','draft'))) {
			//Let's deal with the scope - default is future
			if( is_admin() ){
				$scope = $wp_query->query_vars['scope'] = (!empty($_REQUEST['scope'])) ? $_REQUEST['scope']:'future';
				//TODO limit what a user can see admin side for events/locations/recurring events
			}else{
				if( empty($wp_query->query_vars['scope']) ){
					if( is_archive() ){
						$scope = $wp_query->query_vars['scope'] = get_option('dbem_events_page_scope');
					}else{
						$scope = $wp_query->query_vars['scope'] = 'all'; //otherwise we'll get 404s for past events
					}
				}else{
					$scope = $wp_query->query_vars['scope'];
				}
			}
			$query = array();
			$time = current_time('timestamp');
			if ( preg_match ( "/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/", $scope ) ) {
				$today = strtotime($scope);
				$tomorrow = $today + 60*60*24-1;
				if( get_option('dbem_events_current_are_past') ){
					$query[] = array( 'key' => '_start_ts', 'value' => array($today,$tomorrow), 'compare' => 'BETWEEN' );
				}else{
					$query[] = array( 'key' => '_start_ts', 'value' => $tomorrow, 'compare' => '<=' );
					$query[] = array( 'key' => '_end_ts', 'value' => $today, 'compare' => '>=' );
				}				
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
		  	if( !is_admin() && get_option('dbem_events_default_archive_orderby') == 'title'){
		  		$wp_query->query_vars['orderby'] = 'title';
		  	}else{
			  	$wp_query->query_vars['orderby'] = 'meta_value_num';
			  	$wp_query->query_vars['meta_key'] = '_start_ts';		  		
		  	}
			$wp_query->query_vars['order'] = get_option('dbem_events_default_archive_orderby','ASC');
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