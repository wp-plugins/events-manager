<!-- START General Search -->
<div class="em-search-geo em-search-field">
	<?php 
		/* This general search will find matches within event_name, event_notes, and the location_name, address, town, state and country. */
		$s_geo_default = esc_attr(get_option('dbem_search_form_geo_label', 'Near...'));
		$s = !empty($_REQUEST['geo']) ? esc_attr($_REQUEST['geo']):$s_geo_default;
	?>
	<input type="text" name="geo" class="em-search-geo" value="<?php echo $s; ?>" onfocus="if(this.value=='<?php echo $s_geo_default; ?>')this.value=''" onblur="if(this.value=='')this.value='<?php echo $s_geo_default; ?>'" />
	
	<input type="hidden" name="near" class="em-search-geo-coords" value="<?php if( !empty($_REQUEST['near']) ) echo esc_attr($_REQUEST['near']); ?>" />
</div>
<!-- WIP, will be moved into js file -->
    <script type="text/javascript">    
	    jQuery(document).ready(function($) {
	    	em_load_jquery_css();
	    	$( "input.em-search-geo" ).autocomplete({
	    		minLength: 3,
	    		autoFocus: true,
	    		delay: 500,
	    		source: function( request, response ){
	    			//do we have countryBias?
	    			$.ajax({
	    				url: EM.ajaxurl,
	    				dataType: "jsonp",
	    				data : {
		    				action:'geocoding_search',
		    				q: request.term,
		    				country: this.element.parents('form.em-search-form').find('input[name=country],select[name=country]').val()
	    				},
	    				success: function( data ){
	    					response( $.map( data.geonames, function( item ){
	    						return {
	    							label	: item.name + (item.adminName1 ? ", " + item.adminName1 : "") + (item.countryName ? ", " + item.countryName:''),
	    							value	: item.name + (item.countryName ? ", " + item.countryName:''),
	    							lat		: item.lat,
	    							lng		: item.lng
	    						};
	    					}));
	    				}
	    			});
	    		},
	    		select: function( event, ui ){
	    			var wrapper = $(this).closest('div.em-search');
	    			if ( ui.item ){
	    				wrapper.find("input.em-search-geo-coords").val( ui.item.lat + ',' + ui.item.lng );
	    				wrapper.find('.em-search-location').hide();
	    			}
	    		}
	    	}).change(function(){
	    		if( this.value == '' ){
	    			var wrapper = $(this).closest('div.em-search')
	    			wrapper.find('input.em-search-geo-coords').val('');
	    			wrapper.find('.em-search-location').show();
	    		}
	    	});
	    	/*
	    	if (navigator.geolocation) {
	    	    navigator.geolocation.getCurrentPosition(function(position){}); //could use this
			}
			*/
	    });
    </script>
<!-- END General Search -->