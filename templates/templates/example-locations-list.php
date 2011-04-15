<?php
/*
 * Default Location List Template
 * This page displays a list of events, which overrides the default EM_Event::output() function and within em_content when displaying events.
 * You can override the default display settings pages by removing 'example-' at the start of this file name and moving it to yourthemefolder/plugins/events-manager/templates/
 * You can display events however you wish, there are a few variables made available to you:
 * 
 * $locations - array of EM_Location objects for this list
 * $location_count -  count($locations)
 * $limit - number of locations to show on the page (for pagination purposes, you can change that in settings page)
 * $offset - number of records to skip in the $locations array (for pagination purposes)
 * $page - page number (for pagination purposes)
 * $args - the args passed onto EM_Locations::output()
 * 
 */ 
if ( count($locations) > 0 ) {
	$location_count = 0;
	$locations_shown = 0;
	?>
	<ul>
	<?php
	foreach ( $locations as $EM_Location ) {
		if( ($locations_shown < $limit || empty($limit)) && ($location_count >= $offset || $offset === 0) ){
			?>
			<li><?php echo $EM_Location->output('#_LOCATIONLINK'); ?></li>
			<?php
			$locations_shown++;
		}
		$location_count++;
	}
	?>
	</ul>
	<?php
	//Pagination (if needed/requested)
	if( !empty($args['pagination']) && !empty($limit) && $locations_count >= $limit ){
		//Show the pagination links (unless there's less than 10 events
		$page_link_template = preg_replace('/(&|\?)page=\d+/i','',$_SERVER['REQUEST_URI']);
		$page_link_template = em_add_get_params($page_link_template, array('page'=>'%PAGE%'));
		echo apply_filters('em_events_output_pagination', em_paginate( $page_link_template, $locations_count, $limit, $page), $page_link_template, $locations_count, $limit, $page);
	}
} else {
	$output = get_option ( 'dbem_no_locations_message' );
}