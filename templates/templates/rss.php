<?php
/*
 * RSS Page
 * This page handles the even RSS feed.
 * You can override this file by and copying it to yourthemefolder/plugins/events-manager/templates/ and modifying as necessary.
 * 
 */ 
header ( "Content-type: text/xml" );
echo "<?xml version='1.0'?>\n";
?>
<rss version="2.0">
	<channel>
		<title><?php echo htmlentities(get_option ( 'dbem_rss_main_title' )); ?></title>
		<link><?php	echo get_permalink ( get_option('dbem_events_page') ); ?></link>
		<description><?php echo htmlentities(get_option('dbem_rss_main_description')); ?></description>
		<docs>http://blogs.law.harvard.edu/tech/rss</docs>
		<generator>Weblog Editor 2.0</generator>
				
		<?php
		$description_format = str_replace ( ">", "&gt;", str_replace ( "<", "&lt;", get_option ( 'dbem_rss_description_format' ) ) );
		//$EM_Events = new EM_Events( array('limit'=>5, 'owner'=>false) );
		$EM_Events = EM_Events::get( array('scope'=>'future', 'owner'=>false) );
		
		foreach ( $EM_Events as $EM_Event ) {
			$description = $EM_Event->output( get_option ( 'dbem_rss_description_format' ), "rss");
			$description = ent2ncr(convert_chars(strip_tags($description))); //Some RSS filtering
			?>
			<item>
				<title><?php echo $EM_Event->output( get_option('dbem_rss_title_format'), "rss" ); ?></title>
				<link><?php echo $EM_Event->output('#_EVENTURL'); ?></link>
				<description><?php echo $description; ?></description>
			</item>
			<?php
		}
		?>
		
	</channel>
</rss>