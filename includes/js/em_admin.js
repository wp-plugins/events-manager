jQuery(document).ready( function($) {
	//Events List
		//Approve/Reject Links
		$('.em-event-delete').live('click', function(){
			if( !confirm("Are you sure you want to delete?") ){ return false; }
			var url = em_ajaxify( el.attr('href'));		
			var td = el.parents('td').first();
			td.html("Loading...");
			td.load( url );
			return false;
		});
	//Tickets
		//Tickets overlay
		var triggers = $("#em-tickets-add").overlay({
			mask: { 
				color: '#ebecff',
				loadSpeed: 200,
				opacity: 0.9
			},
			closeOnClick: true,
			onLoad: function(){
				$('#ui-datepicker-div').appendTo('#em-tickets-form form');
			}
		});
		//Submitting ticket (Add/Edit)
		$('#em-tickets-form form').submit(function(e){
			e.preventDefault();
			$('#em-tickets-intro').remove();
			//first we get the template to insert this to
			if( $('#em-tickets-form form input[name=prev_slot]').val() ){
				//grab slot and populate
				var slot = $('#'+$('#em-tickets-form form input[name=prev_slot]').val());
				var rowNo = slot.attr('id').replace('em-tickets-row-','');
				var edit = true;
			}else{
				//create copy of template slot, insert so ready for population
				var rowNo = $('#em-tickets-body').children('tr').length+1;
				var slot = $('#em-tickets-template').clone().attr('id','em-tickets-row-'+ rowNo).appendTo($('#em-tickets-body'));
				var edit = false;
				slot.show();
			}
			var postData = {};
			$.each($('#em-tickets-form form *[name]'), function(index,el){
				el = $(el);
				slot.find('input.'+el.attr('name')).attr({
					'value' : el.attr('value'),
					'name' : 'em_tickets['+rowNo+']['+el.attr('name')+']'
				});
				slot.find('span.'+el.attr('name')).text(el.attr('value'));
			});
			//sort out dates and localization masking
			var start_pub = $("#em-tickets-form input[name=ticket_start_pub]").val();
			var end_pub = $("#em-tickets-form input[name=ticket_end_pub]").val();
			$('#em-tickets-form *[name]').attr('value','');
			$('#em-tickets-form .close').trigger('click');
			return false;
		});
		//Edit a Ticket
		$('.ticket-actions-edit').live('click',function(e){
			//first, populate form, then, trigger click
			e.preventDefault();
			$('#em-tickets-add').trigger('click');
			var rowId = $(this).parents('tr').first().attr('id');
			$('#em-tickets-form *[name]').attr('value','');
			$.each( $('#'+rowId+' *[name]'), function(index,el){
				var el = $(el);
				var selector = el.attr('class');
				$('#em-tickets-form *[name='+selector+']').attr('value',el.attr('value'));
			});
			$("#em-tickets-form input[name=prev_slot]").attr('value',rowId);
			$("#em-tickets-form input[name=ticket_start_pub]").datepicker('refresh');
			$("#em-tickets-form input[name=ticket_end_pub]").datepicker('refresh');
	
			date_dateFormat = $("#localised-date").datepicker('option', 'dateFormat');
			if( $('#em-tickets-form input[name=ticket_start]').val() != '' || $('#em-tickets-form input[name=ticket_end]').val() != '' ){			
				start_date_formatted = $.datepicker.formatDate( date_dateFormat, $.datepicker.parseDate('yy-mm-dd', $('#em-tickets-form input[name=ticket_start]').val()) );
				end_date_formatted = $.datepicker.formatDate( date_dateFormat, $.datepicker.parseDate('yy-mm-dd', $('#em-tickets-form input[name=ticket_end]').val()) );
				$("#em-tickets-form input[name=ticket_start_pub]").val(start_date_formatted);
				$("#em-tickets-form input[name=ticket_end_pub]").val(end_date_formatted);
			}
			return false;
		});	
		//Delete a ticket
		$('.ticket-actions-delete').live('click',function(e){
			e.preventDefault();
			var el = $(this);
			var rowId = $(this).parents('tr').first().attr('id');
			if( $('#'+rowId+' input.ticket_id').attr('value') == '' ){
				//not saved to db yet, so just remove
				$('#'+rowId).remove();
			}else{
				//only will happen if no bookings made
				el.text('Deleting...');	
				$.getJSON( $(this).attr('href'), {'em_ajax_action':'delete_ticket', 'id':$('#'+rowId+' input.ticket_id').attr('value')}, function(data){
					if(data.result){
						$('#'+rowId).remove();
					}else{
						el.text('Delete');
						alert(data.error);
					}
				});
			}
			return false;
		});
	//Manageing Bookings
		//Widgets and filter submissions
		$('.em_bookings_events_table form, .em_bookings_pending_table form').live('submit', function(e){
			var el = $(this);
			var url = em_ajaxify( el.attr('action') );			
			el.parents('.wrap').find('.table-wrap').first().append('<div id="em-loading" />');
			$.get( url, el.serializeArray(), function(data){
				el.parents('.wrap').first().replaceWith(data);
			});
			return false;
		});
		//Pagination link clicks
		$('.em_bookings_events_table .tablenav-pages a, .em_bookings_pending_table .tablenav-pages a').live('click', function(){		
			var el = $(this);
			var url = em_ajaxify( el.attr('href') );	
			el.parents('.wrap').find('.table-wrap').first().append('<div id="em-loading" />');
			$.get( url, function(data){
				el.parents('.wrap').first().replaceWith(data);
			});
			return false;
		});
		//Approve/Reject Links
		$('.em-bookings-approve,.em-bookings-reject,.em-bookings-unapprove,.em-bookings-delete').live('click', function(){
			var el = $(this); 
			if( el.hasClass('em-bookings-delete') ){
				if( !confirm("Are you sure you want to delete?") ){ return false; }
			}
			var url = em_ajaxify( el.attr('href'));		
			var td = el.parents('td').first();
			td.html("Loading...");
			td.load( url );
			return false;
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
			altFormat: "yy-mm-dd",
			changeMonth: true,
			changeYear: true
		});
		if( $('#date-to-submit').val() != '' ){
			date_dateFormat = $("#localised-date").datepicker('option', 'dateFormat');
			start_date_formatted = $.datepicker.formatDate( date_dateFormat, $.datepicker.parseDate('yy-mm-dd', $('#date-to-submit').val()) );
			end_date_formatted = $.datepicker.formatDate( date_dateFormat, $.datepicker.parseDate('yy-mm-dd', $('#end-date-to-submit').val()) );
			$("#localised-date").val(start_date_formatted);
			$("#localised-end-date").val(end_date_formatted);
		}
		$("#em-tickets-form input[name=ticket_start_pub]").datepicker({
			altField: "#em-tickets-form input[name=ticket_start]", 
			altFormat: "yy-mm-dd",
			changeMonth: true,
			changeYear: true
		});
		$("#em-tickets-form input[name=ticket_end_pub]").datepicker({
			altField: "#em-tickets-form input[name=ticket_end]", 
			altFormat: "yy-mm-dd",
			changeMonth: true,
			changeYear: true
		});
	}
	
	//Location stuff - only needed if inputs for location exist
	if( $('select#location-select-id, input#location-name').length > 0 ){

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
		$('#location-town, #location-address, #location-state, #location-postcode, #location-country').change( function(){
			//build address
			var addresses = [ $('#location-address').val(), $('#location-town').val(), $('#location-state').val(), $('#location-postcode').val() ];
			var address = '';
			jQuery.each( addresses, function(i, val){
				if( val != '' ){
					address = ( address == '' ) ? address+val:address+', '+val;
				}
			});
			//do country last, as it's using the text version
			if( $('#location-country option:selected').val() != 0 ){
				address = ( address == '' ) ? address+$('#location-country option:selected').text():address+', '+$('#location-country option:selected').text();
			}
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
		
		//Finally, add autocomplete here
		//Autocomplete
		/* for jquery-ui-1.8.5
		$( "#event-form input#location-name" ).autocomplete({
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
		$( "#event-form input#location-name" ).autocomplete( '../wp-content/plugins/events-manager/admin/em-locations-search.php', {
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
				return item.value + '<br><span style="font-size:11px"><em>'+ item.address + ', ' + item.town+"</em></span>";
			},
			formatResult: function(item){
				return item.value;
			}
		}).result(function(e, item) {
			e.preventDefault();
			$("input#location-id" ).val(item.id);
			$("input#location-name" ).val(item.value);
			$('input#location-address').val(item.address);
			$('input#location-town').val(item.town);
			$('input#location-state').val(item.state);
			$('input#location-postcode').val(item.postcode);
			if( item.country == '' ){
				$('select#location-country option:selected').removeAttr('selected');
			}else{
				$('select#location-country option[value="'+item.country+'"]').attr('selected', 'selected');
			}
			get_map_by_id(item.id);
		});
	}

	//previously in em-admin.php
	function updateIntervalDescriptor () { 
		$(".interval-desc").hide();
		var number = "-plural";
		if ($('input#recurrence-interval').val() == 1 || $('input#recurrence-interval').val() == "")
		number = "-singular"
		var descriptor = "span#interval-"+$("select#recurrence-frequency").val()+number;
		$(descriptor).show();
	}
	function updateIntervalSelectors () {
		$('p.alternate-selector').hide();   
		$('p#'+ $('select#recurrence-frequency').val() + "-selector").show();
	}
	function updateShowHideRecurrence () {
		if( $('input#event-recurrence').attr("checked")) {
			$("#event_recurrence_pattern").fadeIn();
			$("#event-date-explanation").hide();
			$("#recurrence-dates-explanation").show();
			$("h3#recurrence-dates-title").show();
			$("h3#event-date-title").hide();     
		} else {
			$("#event_recurrence_pattern").hide();
			$("#recurrence-dates-explanation").hide();
			$("#event-date-explanation").show();
			$("h3#recurrence-dates-title").hide();
			$("h3#event-date-title").show();   
		}
	}		 
	$("#recurrence-dates-explanation").hide();
	$("#date-to-submit").hide();
	$("#end-date-to-submit").hide();
	
	$("#localised-date").show();
	$("#localised-end-date").show();

	$('input.select-all').change(function(){
	 	if($(this).is(':checked'))
	 	$('input.row-selector').attr('checked', true);
	 	else
	 	$('input.row-selector').attr('checked', false);
	}); 
	
	updateIntervalDescriptor(); 
	updateIntervalSelectors();
	updateShowHideRecurrence();
	$('input#event-recurrence').change(updateShowHideRecurrence);
	   
	// recurrency elements   
	$('input#recurrence-interval').keyup(updateIntervalDescriptor);
	$('select#recurrence-frequency').change(updateIntervalDescriptor);
	$('select#recurrence-frequency').change(updateIntervalSelectors);
    
	// hiding or showing notes according to their content	
	$('.postbox h3').prepend('<a class="togbox">+</a> ');
	$('#event_notes h3').click( function() {
		 $(this).parent().first().toggleClass('closed');
    });
});


//Take a url and add em_ajax param to it
function em_ajaxify(url){
	if ( url.search('em_ajax=0') != -1){
		url = url.replace('em_ajax=0','em_ajax=1');
	}else if( url.search(/\?/) != -1 ){
		url = url + "&em_ajax=1";
	}else{
		url = url + "?em_ajax=1";
	}
	return url;
}