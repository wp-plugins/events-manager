//inserted at wp_head
jQuery(document).ready( function($) {
	$('.dbem-calendar a.em-calnav, .dbem-calendar-full a.em-calnav').live('click', function(e){
		e.preventDefault();
		$(this).parents('.em-calendar-wrapper').first().load($(this).attr('href'));		
	} );
});