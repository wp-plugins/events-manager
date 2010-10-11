<?php
function dbem_rss() {
	if (isset ( $_REQUEST ['dbem_rss'] ) && $_REQUEST ['dbem_rss'] == 'main') {
		header ( "Content-type: text/xml" );
		echo "<?xml version='1.0'?>\n";
		
		$events_page_id = get_option ( 'dbem_events_page' );
		$events_page_link = get_permalink ( $events_page_id );
		if (stristr ( $events_page_link, "?" ))
			$joiner = "&amp;";
		else
			$joiner = "?";
		
		?>
<rss version="2.0">
<channel>
<title><?php
		echo get_option ( 'dbem_rss_main_title' );
		?></title>
<link><?php
		echo $events_page_link;
		?></link>
<description><?php
		echo get_option ( 'dbem_rss_main_description' );
		?></description>
<docs>
http://blogs.law.harvard.edu/tech/rss
</docs>
<generator>
Weblog Editor 2.0
</generator>
	<?php
	$title_format = get_option ( 'dbem_rss_title_format' );
	$description_format = str_replace ( ">", "&gt;", str_replace ( "<", "&lt;", get_option ( 'dbem_rss_description_format' ) ) );
	$events = EM_Events::get( array('limit'=>5) );
	foreach ( $events as $event ) {
		$title = $event->output( $title_format, "rss" );
		$description = $event->output( $description_format, "rss");
		echo "<item>";
		echo "<title>$title</title>\n";
		echo "<link>$events_page_link" . $joiner . "event_id=" . $event->id . "</link>\n ";
		echo "<description>$description </description>\n";
		echo "</item>";
	}
	?>
</channel>
</rss>

<?php
		die ();
	}
}
add_action ( 'init', 'dbem_rss' );
?>