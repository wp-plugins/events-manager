<?php
//TODO add a shortcode to link for a specific event, e.g. [event id=x]text[/event]

/**
 * Returns the html of an events calendar.
 * @param array $atts
 * @return string
 */
function em_get_calendar_shortcode($atts) { 
	$atts = shortcode_atts(array( 'full' => 0, 'month' => '', 'year' => '', 'long_events' => 0 ), $atts);
	$return = '<div id="em-calendar-'.rand(100,200).'" class="em-calendar-wrapper">';
	$return .= EM_Calendar::get($atts);
	$return .= '</div>';	
	return $return;
}
add_shortcode('events_calendar', 'em_get_calendar_shortcode');

function em_get_locations_map_shortcode( $atts ){
	$atts = shortcode_atts( array('eventful' => 0, 'scope' => 'all', 'width' => 450, 'height' => 300), $atts ); 
	return EM_Map::get_global($atts);
}
add_shortcode('locations_map', 'em_get_locations_map_shortcode');
add_shortcode('locations-map', 'em_get_locations_map_shortcode'); //Depreciate this... confusing for wordpress 

/**
 * Shows a list of events according to given specifications 
 * @param array $atts
 * @return string
 */
function em_get_events_list_shortcode($atts) {
	//TODO sort out attributes so it's consistent everywhere
	$atts = shortcode_atts ( array ('limit' => 3, 'scope' => 'future', 'order' => 'ASC', 'format' => '', 'category' => '', 'location'=>'' ), $atts );
	$result = EM_Events::output ( $atts );
	return $result;
}
add_shortcode ( 'events_list', 'em_get_events_list_shortcode' );

/**
 * DO NOT DOCUMENT! This should be replaced with shortcodes events-link and events_uri
 * @param array $atts
 * @return string
 */
function em_get_events_page_shortcode($atts) {
	$atts = shortcode_atts ( array ('justurl' => 0, 'text' => '' ), $atts );
	if($atts['justurl']){
		return EM_URI;
	}else{
		return em_get_link($atts['text']);
	}
}
add_shortcode ( 'events_page', 'em_get_events_page_shortcode' );

/**
 * Shortcode for a link to events page. Default will show events page title in link text, if you use [events_link]text[/events_link] 'text' will be the link text
 * @param array $atts
 * @param string $text
 * @return string
 */
function em_get_link_shortcode($atts, $text='') {
	return em_get_link($text);
}
add_shortcode ( 'events_link', 'em_get_link_shortcode');

/**
 * Returns the uri of the events page only
 * @return string
 */
function em_get_uri_shortcode(){
	return EM_URI;
}
add_shortcode ( 'events_uri', 'em_get_uri_shortcode');

/**
 * CHANGE DOCUMENTATION! if you just want the url you should use shortcode events_rss_uri
 * @param array $atts
 * @return string
 */
function em_get_rss_link_shortcode($atts) {
	$atts = shortcode_atts ( array ('justurl' => 0, 'text' => 'RSS' ), $atts );
	if($atts['justurl']){
		return EM_RSS_URI;
	}else{
		return em_get_rss_link($atts['text']);
	}
}
add_shortcode ( 'events_rss_link', 'em_get_rss_link_shortcode' );

/**
 * Returns the uri of the events rss page only, takes no attributes.
 * @return string
 */
function em_get_rss_uri_shortcode(){
	return EM_RSS_URI;
}
add_shortcode ( 'events_rss_uri', 'em_get_rss_uri_shortcode');