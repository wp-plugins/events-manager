//inserted at wp_head
jQuery(document).ready( function($) {
	$('a.em-calnav, a.em-calnav').live('click', function(e){
		e.preventDefault();
		$(this).closest('.em-calendar-wrapper').prepend('<div class="loading" id="em-loading"></div>');
		var url = em_ajaxify($(this).attr('href'));
		$(this).closest('.em-calendar-wrapper').load(url);
	} );
	var em_ajaxify = function(url){
		if ( url.search('em_ajax=0') != -1){
			url = url.replace('em_ajax=0','em_ajax=1');
		}else if( url.search(/\?/) != -1 ){
			url = url + "&em_ajax=1";
		}else{
			url = url + "?em_ajax=1";
		}
		return url;
	}
});