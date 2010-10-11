jQuery(document).ready( function($) {
    // Managing bookings delete operations 
	$('a.bookingdelbutton').click( function(){
		eventId = (jQuery(this).parents('table:first').attr('id').split("-"))[3]; 
		idToRemove = (jQuery(this).parents('tr:first').attr('id').split("-"))[1];     
		$.ajax({
	  	  type: "POST",
		    url: "admin.php?page=people&action=remove_booking",
		    data: "booking_id="+ idToRemove,
		    success: function(){	
				$('tr#booking-' + idToRemove).fadeOut('slow');
			  	$.getJSON("admin.php?page=people&dbem_ajax_action=booking_data",{id: eventId, ajax: 'true'}, function(data){
			  	  	booked = data[0].bookedSeats;
			  	    available = data[0].availableSeats; 
					$('td#booked-seats').text(booked);
					$('td#available-seats').text(available);          
			  	});  
		   	}
		});
	});
	
	//Attributes
	$('#mtm_add_tag').click( function(event){
		event.preventDefault();
		//Get All meta rows
			var metas = $('#mtm_body').children();
		//Copy first row and change values
			var metaCopy = $(metas[0]).clone(true);
			newId = metas.length + 1;
			metaCopy.attr('id', 'mtm_'+newId);
			metaCopy.find('a').attr('rel', newId);
			metaCopy.find('[name=mtm_1_ref]').attr({
				name:'mtm_'+newId+'_ref' ,
				value:'' 
			});
			metaCopy.find('[name=mtm_1_content]').attr({ 
				name:'mtm_'+newId+'_content' , 
				value:'' 
			});
			metaCopy.find('[name=mtm_1_name]').attr({ 
				name:'mtm_'+newId+'_name' ,
				value:'' 
			});
		//Insert into end of file
			$('#mtm_body').append(metaCopy);
		//Duplicate the last entry, remove values and rename id
	});	
	$('#mtm_body a').click( function(event){
		event.preventDefault();
		//Only remove if there's more than 1 meta tag
		if($('#mtm_body').children().length > 1){
			//Remove the item
			var parents = $(this).parents('#mtm_body tr').first().remove();
			//Renumber all the items
			$('#mtm_body').children().each( function(i){
				metaCopy = $(this);
				oldId = metaCopy.attr('id').replace('mtm_','');
				newId = i+1;
				metaCopy.attr('id', 'mtm_'+newId);
				metaCopy.find('a').attr('rel', newId);
				metaCopy.find('[name=mtm_'+ oldId +'_ref]').attr('name', 'mtm_'+newId+'_ref');
				metaCopy.find('[name=mtm_'+ oldId +'_content]').attr('name', 'mtm_'+newId+'_content');
				metaCopy.find('[name=mtm_'+ oldId +'_name]').attr( 'name', 'mtm_'+newId+'_name');
			});
		}else{
			metaCopy = $(this).parents('#mtm_body tr').first();
			metaCopy.find('[name=mtm_1_ref]').attr('value', '');
			metaCopy.find('[name=mtm_1_content]').attr('value', '');
			metaCopy.find('[name=mtm_1_name]').attr( 'value', '');
			alert("If you don't want any meta tags, just leave the text boxes blank and submit");
		}
	});
	
	//Datepicker
	if( $('#date-to-submit').length > 0 ){
		$("#localised-date").datepicker({
			altField: "#date-to-submit", 
			altFormat: "yy-mm-dd",
			changeMonth: true,
			changeYear: true
		});
		$("#localised-end-date").datepicker({
			altField: "#end-date-to-submit", 
			altFormat: "yy-mm-dd"
		});
		if( $('#date-to-submit').val() != '' ){
			date_dateFormat = $("#localised-date").datepicker('option', 'dateFormat');
			start_date_formatted = $.datepicker.formatDate( date_dateFormat, $.datepicker.parseDate('yy-mm-dd', $('#date-to-submit').val()) );
			end_date_formatted = $.datepicker.formatDate( date_dateFormat, $.datepicker.parseDate('yy-mm-dd', $('#end-date-to-submit').val()) );
			$("#localised-date").val(start_date_formatted);
			$("#localised-end-date").val(end_date_formatted);
		}
	}
	
	
	//Location stuff - only needed if inputs for location exist
	if( $('select#location-select-id, input#location-name').length > 0 ){
		//Autocomplete
		/* for jquery-ui-1.8.5
		$( "#eventForm input#location-name" ).autocomplete({
			source: '../wp-content/plugins/events-manager/admin/locations-search.php',
			minLength: 2,
			select: function( event, ui ) {  
				$("input#location-address").val(ui.item.address); 
				$("input#location-town").val(ui.item.town); 
				if($('#em-map').length > 0){
					get_map_by_id(ui.item.id);
				}
			}
		});
		*/
		$( "#eventForm input#location-name" ).autocomplete( '../wp-content/plugins/events-manager/admin/locations-search.php', {
			multiple: true,
			width: 350,
			scroll:false,
			selectFirst: false,
			dataType: "json",
			parse: function(data) {
				return $.map(data, function(row) {
					return {
						data: row,
						value: row.value,
						result: row.value
					}
				});
			},
			formatItem: function(item) {
				return item.value + '<br><span style="font-size:11px"><em>'+ item.address + ', ' + item.town;
			},
			formatResult: function(item){
				return item.value;
			}
		}).result(function(e, item) {
			e.preventDefault();
			$( "input#location-name" ).val(item.value);
			$('input#location-address').val(item.address);
			$('input#location-town').val(item.town);
			get_map_by_id(item.id);
		});

		//Load map
		if($('#em-map').length > 0){
			var em_LatLng = new google.maps.LatLng(0, 0);
			var map = new google.maps.Map( document.getElementById('em-map'), {
			    zoom: 14,
			    center: em_LatLng,
			    mapTypeId: google.maps.MapTypeId.ROADMAP,
			    mapTypeControl: false
			});
			var marker = new google.maps.Marker({
			    position: em_LatLng,
			    map: map
			});
			var infoWindow = new google.maps.InfoWindow({
			    content: ''
			});
			var geocoder = new google.maps.Geocoder();
			google.maps.event.addListener(infoWindow, 'domready', function() { 
				document.getElementById('location-balloon-content').parentNode.style.overflow=''; 
				document.getElementById('location-balloon-content').parentNode.parentNode.style.overflow=''; 
			});
		}
		
		//Add listeners for changes to address
		var get_map_by_id = function(id){
			if($('#em-map').length > 0){
				$.getJSON(document.URL,{ em_ajax_action:'get_location', id:id }, function(data){
					if( data.location_latitude!=0 && data.location_longitude!=0 ){
						loc_latlng = new google.maps.LatLng(data.location_latitude, data.location_longitude);
						marker.setPosition(loc_latlng);
						marker.setTitle( data.location_name );
						$('#em-map').show();
						$('#em-map-404').hide();
						map.setCenter(loc_latlng);
						map.panBy(40,-55);
						infoWindow.setContent( '<div id="location-balloon-content">'+ data.location_balloon +'</div>');
						infoWindow.open(map, marker);
						google.maps.event.trigger(map, 'resize');
					}else{
						$('#em-map').hide();
						$('#em-map-404').show();
					}
				});
			}
		}
		$('#location-select-id').change( function(){get_map_by_id($(this).val())} );
		$('#location-town, #location-address').change( function(){
			var address = $('#location-address').val() + ', ' + $('#location-town').val();
			if( address != '' && $('#em-map').length > 0 ){
				geocoder.geocode( { 'address': address }, function(results, status) {
				    if (status == google.maps.GeocoderStatus.OK) {
						marker.setPosition(results[0].geometry.location);
						marker.setTitle( $('#location-name, #location-select-id').first().val() );
						$('#location-latitude').val(results[0].geometry.location.lat());
						$('#location-longitude').val(results[0].geometry.location.lng());
	        			$('#em-map').show();
	        			$('#em-map-404').hide();
	        			google.maps.event.trigger(map, 'resize');
						map.setCenter(results[0].geometry.location);
						map.panBy(40,-55);
						infoWindow.setContent( 
							'<div id="location-balloon-content"><strong>' + 
							$('#location-name').val() + 
							'</strong><br/>' + 
							$('#location-address').val() + 
							'<br/>' + $('#location-town').val()+ 
							'</div>'
						);
						infoWindow.open(map, marker);
					} else {
	        			$('#em-map').hide();
	        			$('#em-map-404').show();
					}
				});
			}
		});
		$("input#location-town, select#location-select-id").triggerHandler('change');
	}
});