//inserted at wp_head
jQuery(document).ready( function($) {
	
	
	$('a.em-calnav, a.em-calnav').live('click', function(e){
		e.preventDefault();
		$(this).parents('.em-calendar-wrapper').first().prepend('<div class="loading" id="em-loading"></div>');
		$(this).parents('.em-calendar-wrapper').first().load($(this).attr('href'));		
	} );
	
	
});