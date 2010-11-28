<?php
//TODO add a shortcode to link for a specific event, e.g. [event id=x]text[/event]

/**
 * Returns the html of an events calendar with events that match given query attributes. Accepts any event query attribute.
 * @param array $atts
 * @return string
 */
function em_get_calendar_shortcode($atts) { 
	$atts = (array) $atts;
	return EM_Calendar::output($atts);
}
add_shortcode('events_calendar', 'em_get_calendar_shortcode');

/**
 * Generates a map of locations that match given query attributes. Accepts any location query attributes. 
 * @param unknown_type $atts
 * @return string
 */
function em_get_locations_map_shortcode($atts){
	$clean_atts = EM_Locations::get_default_search($atts);
	$clean_atts['width'] = ( !empty($atts['width']) ) ? $atts['width']:450;
	$clean_atts['height'] = ( !empty($atts['height']) ) ? $atts['height']:300; 
	return EM_Map::get_global($atts);
}
add_shortcode('locations_map', 'em_get_locations_map_shortcode');
add_shortcode('locations-map', 'em_get_locations_map_shortcode'); //Depreciate this... confusing for wordpress 

/**
 * Shows a list of events according to given specifications. Accepts any event query attribute.
 * @param array $atts
 * @return string
 */
function em_get_events_list_shortcode($atts) {
	//TODO sort out attributes so it's consistent everywhere
	$atts = (array) $atts;
	return EM_Events::output ( $atts );
}
add_shortcode ( 'events_list', 'em_get_events_list_shortcode' );

/**
 * Returns list of locations according to given specifications. Accepts any location query attribute.
 */
function em_get_locations_list_shortcode( $atts ){
	$atts = (array) $atts;
	return EM_Locations::output( $atts );
}
add_shortcode('locations_list', 'em_get_locations_list_shortcode');

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
function em_get_url_shortcode(){
	return EM_URI;
}
add_shortcode ( 'events_url', 'em_get_url_shortcode');

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
function em_get_rss_url_shortcode(){
	return EM_RSS_URI;
}
add_shortcode ( 'events_rss_url', 'em_get_rss_url_shortcode');