<?php
/**
 * bp_em_screen_two()
 *
 * Sets up and displays the screen output for the sub nav item "em/screen-two"
 */
function bp_em_attending() {
	global $bp;
	/**
	 * If the user has not Accepted or Rejected anything, then the code above will not run,
	 * we can continue and load the template.
	 */
	do_action( 'bp_em_attending' );

	add_action( 'bp_template_title', 'bp_em_attending_title' );
	add_action( 'bp_template_content', 'bp_em_attending_content' );

	/* Finally load the plugin template file. */
	bp_core_load_template( apply_filters( 'bp_core_template_plugin', 'members/single/plugins' ) );
}

	function bp_em_attending_title() {
		_e( 'Attending', 'bp-em' );
	}

	function bp_em_attending_content() {
		global $bp;
		$EM_Person = new EM_Person($bp->displayed_user->id);
		$events = $EM_Person->get_events();
		$args = array(
			'format_header' => get_option('dbem_bp_events_list_format_header'),
			'format' => get_option('dbem_bp_events_list_format'),
			'format_footer' => get_option('dbem_bp_events_list_format_footer')
		);
		if( count($events) > 0 ){
			echo EM_Events::output($events, $args);
		}else{
			echo get_option('dbem_bp_events_list_none_format');
		}
	}