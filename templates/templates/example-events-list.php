<?php
/*
 * Default Events List Template
 * This page displays a list of events, which overrides the default EM_Event::output() function and within em_content when displaying events.
 * You can override the default display settings pages by removing 'example-' at the start of this file name and moving it to yourthemefolder/plugins/events-manager/templates/
 * You can display events however you wish, there are a few variables made available to you:
 * 
 * $events - array of EM_Event objects for this list
 * $events_count -  count($events)
 * $limit - number of events to show on the page (for pagination purposes, you can change that in settings page)
 * $offset - number of records to skip in the $events array (for pagination purposes)
 * $page - page number (for pagination purposes)
 * $args - the args passed onto EM_Events::output()
 * 
 */ 
	if ( $events_count > 0 ) {
		$event_count = 0;
		$events_shown = 0;
		?>
		<ul class="em-events-list">
		<?php
		foreach ( $events as $EM_Event ) {
			if( ($events_shown < $limit || empty($limit)) && ($event_count >= $offset || $offset === 0) ){
				?>
				<li><?php echo $EM_Event->output('#_EVENTLINK'); ?></li>
				<?php
				$events_shown++;
			}
			$event_count++;
		}
		?>
		</ul>
		<?php
		//Pagination (if needed/requested)
		if( !empty($args['pagination']) && !empty($limit) && $events_count > $limit ){
			//Show the pagination links (unless there's less than $limit events)
			$page_link_template = preg_replace('/(&|\?)page=\d+/i','',$_SERVER['REQUEST_URI']);
			$page_link_template = em_add_get_params($page_link_template, array('page'=>'%PAGE%'));
			echo apply_filters('em_events_output_pagination', em_paginate( $page_link_template, $events_count, $limit, $page), $page_link_template, $events_count, $limit, $page);
		}
	} else {
		?>
		Sorry, no events here!
		<?php
	}