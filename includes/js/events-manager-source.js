jQuery(document).ready( function($){
	var load_ui_css = false; //load jquery ui css?
	/* Time Entry */
	$('#start-time').each(function(i, el){
		$(el).addClass('em-time-input em-time-start').next('#end-time').addClass('em-time-input em-time-end').parent().addClass('em-time-range');
	});
	if( $(".em-time-input").length > 0 ){
		em_setup_timepicker('body');
	}
	/* Calendar AJAX */
	$('.em-calendar-wrapper a').unbind("click");
	$('.em-calendar-wrapper a').undelegate("click");
	$('.em-calendar-wrapper').delegate('a.em-calnav, a.em-calnav', 'click', function(e){
		e.preventDefault();
		$(this).closest('.em-calendar-wrapper').prepend('<div class="loading" id="em-loading"></div>');
		var url = em_ajaxify($(this).attr('href'));
		$(this).closest('.em-calendar-wrapper').load(url, function(){$(this).trigger('em_calendar_load');});
	} );

	//Events Search
	$('.em-events-search-form select[name=country]').change( function(){
		$('.em-events-search select[name=state]').html('<option value="">'+EM.txt_loading+'</option>');
		$('.em-events-search select[name=region]').html('<option value="">'+EM.txt_loading+'</option>');
		$('.em-events-search select[name=town]').html('<option value="">'+EM.txt_loading+'</option>');
		var data = {
			action : 'search_states',
			country : $(this).val(),
			return_html : true
		};
		$('.em-events-search select[name=state]').load( EM.ajaxurl, data );
		data.action = 'search_regions';
		$('.em-events-search select[name=region]').load( EM.ajaxurl, data );
		data.action = 'search_towns';
		$('.em-events-search select[name=town]').load( EM.ajaxurl, data );
	});

	$('.em-events-search-form select[name=region]').change( function(){
		$('.em-events-search select[name=state]').html('<option value="">'+EM.txt_loading+'</option>');
		$('.em-events-search select[name=town]').html('<option value="">'+EM.txt_loading+'</option>');
		var data = {
			action : 'search_states',
			region : $(this).val(),
			country : $('.em-events-search-form select[name=country]').val(),
			return_html : true
		};
		$('.em-events-search select[name=state]').load( EM.ajaxurl, data );
		data.action = 'search_towns';
		$('.em-events-search select[name=town]').load( EM.ajaxurl, data );
	});

	$('.em-events-search-form select[name=state]').change( function(){
		$('.em-events-search select[name=town]').html('<option value="">'+EM.txt_loading+'</option>');
		var data = {
			action : 'search_towns',
			state : $(this).val(),
			region : $('.em-events-search-form select[name=region]').val(),
			country : $('.em-events-search-form select[name=country]').val(),
			return_html : true
		};
		$('.em-events-search select[name=town]').load( EM.ajaxurl, data );
	});
	
	//in order for this to work, you need the above classes to be present in your templates
	$(document).delegate('.em-events-search-form', 'submit', function(e){
    	if( this.search && this.search.value== EM.txt_search ){ this.search.value = ''; }
    	if( this.em_search && this.em_search.value== EM.txt_search){ this.em_search.value = ''; }
    	if( $('#em-wrapper .em-events-search-ajax').length == 1 ){
    		e.preventDefault();
			$('.em-events-search-form :submit').val(EM.txt_searching);
			$.ajax( EM.ajaxurl, {
	    		dataType : 'html',
	    		data : $(this).serialize(),
			    success : function(responseText) {
					$('.em-events-search-form :submit').val(EM.txt_search);
					$('#em-wrapper .em-events-search-ajax').replaceWith(responseText);
			    }
	    	});
			return false;
    	} 
	});
	if( $('#em-wrapper .em-events-search-ajax').length > 0 ){
		$(document).delegate('#em-wrapper .em-events-search-ajax a.page-numbers', 'click', function(e){
			e.preventDefault();
			var pageNo = $(this).attr('title');
			if( $('.em-events-search-form input[name="page"]').length > 0 ){
				$('.em-events-search-form input[name="page"]').val(pageNo);
			}else{
				$('.em-events-search-form').append('<input type="hidden" name="page" value="'+pageNo+'" />');
			}
			$('.em-events-search-form').trigger('submit');
			return false;
		});
	}
		
	/*
	 * ADMIN AREA AND PUBLIC FORMS (Still polishing this section up, note that form ids and classes may change accordingly)
	 */
	//Events List
		//Approve/Reject Links
		$(document).delegate('.em-event-delete', 'click', function(){
			if( !confirm("Are you sure you want to delete?") ){ return false; }
			var url = em_ajaxify( el.attr('href'));		
			var td = el.parents('td').first();
			td.html("Loading...");
			td.load( url );
			return false;
		});
	//Tickets
	if( $("#em-tickets-form").length > 0 ){
		load_ui_css = true;
		//Dialog/Overlay
		$("#em-tickets-form").dialog({
			modal : true,
			autoOpen: false,
			minWidth: 350,
			height: 'auto',
			buttons: [{
				text: EM.tickets_save,
				click: function(e){
					e.preventDefault();
					//Submitting ticket (Add/Edit)
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
						var slot = $('#em-tickets-body tr').first().clone().attr('id','em-tickets-row-'+ rowNo).appendTo($('#em-tickets-body'));
						var edit = false;
						slot.show();
					}
					var postData = {};
					var is_checked = false;
					$.each($('#em-tickets-form form *[name]'), function(index,el){
						el = $(el);
						if( el.attr('type') == 'checkbox' ){
							is_checked = el.is(':checked') ? 1:0;
							slot.find('input.'+el.attr('name')).attr({
								'value' : is_checked,
								'name' : 'em_tickets['+rowNo+']['+el.attr('name')+']'
							});
						}else{
							slot.find('input.'+el.attr('name')).attr({
								'value' : el.attr('value'),
								'name' : 'em_tickets['+rowNo+']['+el.attr('name')+']'
							});
						}
						if( el.attr('name') == 'ticket_start_pub'){
							slot.find('span.ticket_start').text(el.attr('value'));
						}else if( el.attr('name') == 'ticket_end_pub' ){
							slot.find('span.ticket_end').text(el.attr('value'));
						}else if( el.attr('name') == 'ticket_members' ){
							if( el.is(':checked') ){
								slot.find('span.ticket_name').prepend('* ');
							}
						}else{
							slot.find('span.'+el.attr('name')).text(el.attr('value'));
						}
					});
					//allow for others to hook into this
					$(document).triggerHandler('em_maps_tickets_edit', [slot, rowNo, edit]);
					//sort out dates and localization masking
					var start_pub = $("#em-tickets-form input[name=ticket_start_pub]").val();
					var end_pub = $("#em-tickets-form input[name=ticket_end_pub]").val();
					$('#em-tickets-form *[name]').attr('value','').removeAttr('checked');
					$('#em-tickets-form .close').trigger('click');
					$(this).dialog('close');
				}
			}]
		});
		$("#em-tickets-add").click(function(e){ e.preventDefault(); $('#em-tickets-form *[name]').attr('value','').removeAttr('checked'); $("#em-tickets-form").dialog('open'); });
		//Edit a Ticket
		$(document).delegate('.ticket-actions-edit', 'click', function(e){
			//trigger click
			e.preventDefault();
			$('#em-tickets-add').trigger('click');
			//populate form
			var rowId = $(this).parents('tr').first().attr('id');
			$('#em-tickets-form *[name]').attr('value','').removeAttr('checked');
			$.each( $('#'+rowId+' *[name]'), function(index,el){
				var el = $(el);
				var selector = el.attr('class');
				var input_field = $('#em-tickets-form *[name='+selector+']');
				if( input_field.attr('type') == 'checkbox' ){
					if( el.val() == 1 ){ input_field.attr('checked','checked'); }
					else{ input_field.removeAttr('checked'); }
				}else{
					input_field.attr('value',el.attr('value'));	
				}
			});
			$("#em-tickets-form input[name=prev_slot]").attr('value',rowId); //save the current slot number
			//refresh datepicker and values
			$("#em-tickets-form .em-date-input-loc").datepicker('refresh');
			$('#em-tickets-form input.em-date-input-loc').each(function(i,el){
				el = $(el);
				date_value = el.next('.em-date-input').val();
				if( date_value != '' ){
					date_formatted = $.datepicker.formatDate( EM.dateFormat, $.datepicker.parseDate('yy-mm-dd', date_value) );
					el.val(date_formatted);
				}
			});
			return false;
		});	
		//Delete a ticket
		$(document).delegate('.ticket-actions-delete', 'click', function(e){
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
	}
	//Manageing Bookings
	if( $('#em-bookings-table').length > 0 ){
		//Pagination link clicks
		$(document).delegate('#em-bookings-table .tablenav-pages a', 'click', function(){
			var el = $(this);
			var form = el.parents('#em-bookings-table form.bookings-filter');
			//get page no from url, change page, submit form
			var match = el.attr('href').match(/#[0-9]+/);
			if( match != null && match.length > 0){
				var pno = match[0].replace('#','');
				form.find('input[name=pno]').val(pno);
			}else{
				form.find('input[name=pno]').val(1);
			}
			form.trigger('submit');
			return false;
		});
		//Overlay Options
		var em_bookings_settings_dialog = {
			modal : true,
			autoOpen: false,
			minWidth: 500,
			height: 'auto',
			buttons: [{
				text: EM.bookings_settings_save,
				click: function(e){
					e.preventDefault();
					//we know we'll deal with cols, so wipe hidden value from main
					var match = $("#em-bookings-table form.bookings-filter [name=cols]").val('');
					var booking_form_cols = $('form#em-bookings-table-settings-form input.em-bookings-col-item');
					$.each( booking_form_cols, function(i,item_match){
						//item_match = $(item_match);
						if( item_match.value == 1 ){
							if( match.val() != ''){
								match.val(match.val()+','+item_match.name);
							}else{
								match.val(item_match.name);
							}
						}
					});
					//submit main form
					$('#em-bookings-table-settings').trigger('submitted'); //hook into this with bind()
					$('#em-bookings-table form.bookings-filter').trigger('submit');					
					$(this).dialog('close');
				}
			}]
		};
		var em_bookings_export_dialog = {
			modal : true,
			autoOpen: false,
			minWidth: 500,
			height: 'auto',
			buttons: [{
				text: EM.bookings_export_save,
				click: function(e){
					$(this).children('form').submit();
					$(this).dialog('close');
				}
			}]
		};
		if( $("#em-bookings-table-settings").length > 0 ){
			//Settings Overlay
			$("#em-bookings-table-settings").dialog(em_bookings_settings_dialog);
			$(document).delegate('#em-bookings-table-settings-trigger','click', function(e){ e.preventDefault(); $("#em-bookings-table-settings").dialog('open'); });
			//Export Overlay
			$("#em-bookings-table-export").dialog(em_bookings_export_dialog);
			$(document).delegate('#em-bookings-table-export-trigger','click', function(e){ e.preventDefault(); $("#em-bookings-table-export").dialog('open'); });
			var export_overlay_show_tickets = function(){
				if( $('#em-bookings-table-export-form input[name=show_tickets]').is(':checked') ){
					$('#em-bookings-table-export-form .em-bookings-col-item-ticket').show();
					$('#em-bookings-table-export-form #em-bookings-export-cols-active .em-bookings-col-item-ticket input').val(1);
				}else{
					$('#em-bookings-table-export-form .em-bookings-col-item-ticket').hide().find('input').val(0);					
				}
			};
			//Sync export overlay with table search field changes
			$('#em-bookings-table form select').each(function(i, el){
				$(el).change(function(e){
					var select_el = $(this);
					var input_par = $('#em-bookings-table-export-form input[name='+select_el.attr('name')+']');
					var input_par_selected = select_el.find('option:selected');
					input_par.val(input_par_selected.val());
				});
			});
			
			export_overlay_show_tickets();
			$('#em-bookings-table-export-form input[name=show_tickets]').click(export_overlay_show_tickets);
			//Sortables
			$( ".em-bookings-cols-sortable" ).sortable({
				connectWith: ".em-bookings-cols-sortable",
				update: function(event, ui) {
					if( ui.item.parents('ul#em-bookings-cols-active, ul#em-bookings-export-cols-active').length > 0 ){							
						ui.item.addClass('ui-state-highlight').removeClass('ui-state-default').children('input').val(1);
					}else{
						ui.item.addClass('ui-state-default').removeClass('ui-state-highlight').children('input').val(0);
					}
				}
			}).disableSelection();
			load_ui_css = true;
		}
		//Widgets and filter submissions
		$(document).delegate('#em-bookings-table form.bookings-filter', 'submit', function(e){
			var el = $(this);			
			//append loading spinner
			el.parents('#em-bookings-table').find('.table-wrap').first().append('<div id="em-loading" />');
			//ajax call
			$.post( EM.ajaxurl, el.serializeArray(), function(data){
				var root = el.parents('#em-bookings-table').first();
				root.replaceWith(data);
				//recreate overlays
				$('#em-bookings-table-export input[name=scope]').val(root.find('select[name=scope]').val());
				$('#em-bookings-table-export input[name=status]').val(root.find('select[name=status]').val());
				jQuery(document).triggerHandler('em_bookings_filtered', [data, root, el]);
			});
			return false;
		});
		//Approve/Reject Links
		$(document).delegate('.em-bookings-approve,.em-bookings-reject,.em-bookings-unapprove,.em-bookings-delete', 'click', function(){
			var el = $(this); 
			if( el.hasClass('em-bookings-delete') ){
				if( !confirm(EM.booking_delete) ){ return false; }
			}
			var url = em_ajaxify( el.attr('href'));		
			var td = el.parents('td').first();
			td.html(EM.txt_loading);
			td.load( url );
			return false;
		});
	}
	//Old Bookings Table - depreciating soon
	if( $('.em_bookings_events_table').length > 0 ){
		//Widgets and filter submissions
		$(document).delegate('.em_bookings_events_table form', 'submit', function(e){
			var el = $(this);
			var url = em_ajaxify( el.attr('action') );		
			el.parents('.em_bookings_events_table').find('.table-wrap').first().append('<div id="em-loading" />');
			$.get( url, el.serializeArray(), function(data){
				el.parents('.em_bookings_events_table').first().replaceWith(data);
			});
			return false;
		});
		//Pagination link clicks
		$(document).delegate('.em_bookings_events_table .tablenav-pages a', 'click', function(){		
			var el = $(this);
			var url = em_ajaxify( el.attr('href') );	
			el.parents('.em_bookings_events_table').find('.table-wrap').first().append('<div id="em-loading" />');
			$.get( url, function(data){
				el.parents('.em_bookings_events_table').first().replaceWith(data);
			});
			return false;
		});
	}
	
	//Manual Booking
	$('a.em-booking-button').click(function(e){
		e.preventDefault();
		var button = $(this);
		if( button.text() != EM.bb_booked && $(this).text() != EM.bb_booking){
			button.text(EM.bb_booking);
			var button_data = button.attr('id').split('_'); 
			$.ajax({
				url: EM.ajaxurl,
				dataType: 'jsonp',
				data: {
					event_id : button_data[1],
					_wpnonce : button_data[2],
					action : 'booking_add_one'
				},
				success : function(response, statusText, xhr, $form) {
					if(response.result){
						button.text(EM.bb_booked);
					}else{
						button.text(EM.bb_error);					
					}
					if(response.message != '') alert(response.message);
				},
				error : function(){ button.text(EM.bb_error); }
			});
		}
		return false;
	});	
	$('a.em-cancel-button').click(function(e){
		e.preventDefault();
		var button = $(this);
		if( button.text() != EM.bb_cancelled && button.text() != EM.bb_canceling){
			button.text(EM.bb_canceling);
			var button_data = button.attr('id').split('_'); 
			$.ajax({
				url: EM.ajaxurl,
				dataType: 'jsonp',
				data: {
					booking_id : button_data[1],
					_wpnonce : button_data[2],
					action : 'booking_cancel'
				},
				success : function(response, statusText, xhr, $form) {
					if(response.result){
						button.text(EM.bb_cancelled);
					}else{
						button.text(EM.bb_cancel_error);
					}
				},
				error : function(){ button.text(EM.bb_cancel_error); }
			});
		}
		return false;
	});  

	//Datepicker
	if( $('.em-date-single, .em-date-range, #em-date-start').length > 0 ){
		if( EM.locale != 'en' && EM.locale_data ){
			$.datepicker.setDefaults(EM.locale_data);
		}
		load_ui_css = true;
		
		//initialize legacy start/end dates, makes the following code compatible
		if( $('#em-date-start').length > 0 ){
			$('#em-date-start').addClass('em-date-input').parent().addClass('em-date-range');
			$("#em-date-start-loc").addClass('em-date-input-loc em-date-start');
			$('#em-date-end').addClass('em-date-input');
			$("#em-date-end-loc").addClass('em-date-input-loc em-date-end');
		}
		if( $(".em-ticket-form, #em-tickets-form").length > 0 ){
			$(".em-ticket-form, #em-tickets-form .start").addClass('em-date-input').parent().addClass('em-date-single');
			$(".em-ticket-form, #em-tickets-form .start-loc").addClass('em-date-input-loc').each(
				function(i,el){ $(el).prev('.start').insertAfter(el); 
			}); //reverse elements for comapatability
			$(".em-ticket-form, #em-tickets-form .end").addClass('em-date-input').parent().addClass('em-date-single');
			$(".em-ticket-form, #em-tickets-form .end-loc").addClass('em-date-input-loc').each(
				function(i,el){ $(el).prev('.end').insertAfter(el);
			}); //reverse elements for comapatability
		}
		em_setup_datepicker('body');
	}
	if( load_ui_css && EM.ui_css ){
		var script = document.createElement("link");
		script.id = 'jquery-ui-css';
		script.rel = "stylesheet";
		script.href = EM.ui_css;
		document.body.appendChild(script);
	}
	
	//previously in em-admin.php
	function updateIntervalDescriptor () { 
		$(".interval-desc").hide();
		var number = "-plural";
		if ($('input#recurrence-interval').val() == 1 || $('input#recurrence-interval').val() == "")
		number = "-singular";
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
	
	$('#em-wrapper input.select-all').change(function(){
	 	if($(this).is(':checked')){
			$('input.row-selector').attr('checked', true);
			$('input.select-all').attr('checked', true);
	 	}else{
			$('input.row-selector').attr('checked', false);
			$('input.select-all').attr('checked', false);
		}
	}); 
	
	updateIntervalDescriptor(); 
	updateIntervalSelectors();
	updateShowHideRecurrence();
	$('input#event-recurrence').change(updateShowHideRecurrence);
	   
	// recurrency elements   
	$('input#recurrence-interval').keyup(updateIntervalDescriptor);
	$('select#recurrence-frequency').change(updateIntervalDescriptor);
	$('select#recurrence-frequency').change(updateIntervalSelectors);

	/* Load any maps */	
	if( $('.em-location-map').length > 0 || $('.em-locations-map').length > 0 || $('#em-map').length > 0 ){
		if ( typeof google !== 'object' || typeof google.maps !== 'object' ){ 
			var script = document.createElement("script");
			script.type = "text/javascript";
			script.src = (EM.is_ssl) ? 'https://maps.google.com/maps/api/js?v=3.8&sensor=false&callback=em_maps':'http://maps.google.com/maps/api/js?v=3.4&sensor=false&callback=em_maps';
			document.body.appendChild(script);
		}else{
			em_maps();
		}
	}
	
	//Finally, add autocomplete here
	//Autocomplete
	if( jQuery( "#em-location-data input#location-name" ).length > 0 ){
		jQuery( "#em-location-data input#location-name" ).autocomplete({
			source: EM.locationajaxurl,
			minLength: 2,
			focus: function( event, ui ){
				jQuery("input#location-id" ).val( ui.item.value );
				return false;
			},			 
			select: function( event, ui ){
				jQuery("input#location-id" ).val(ui.item.id).trigger('change');
				jQuery("input#location-name" ).val(ui.item.value);
				jQuery('input#location-address').val(ui.item.address);
				jQuery('input#location-town').val(ui.item.town);
				jQuery('input#location-state').val(ui.item.state);
				jQuery('input#location-region').val(ui.item.region);
				jQuery('input#location-postcode').val(ui.item.postcode);
				if( ui.item.country == '' ){
					jQuery('select#location-country option:selected').removeAttr('selected');
				}else{
					jQuery('select#location-country option[value="'+ui.item.country+'"]').attr('selected', 'selected');
				}
				jQuery('#em-location-data input, #em-location-data select').css('background-color','#ccc').attr('readonly','readonly');
				jQuery('#em-location-reset').show();
				jQuery('#em-location-search-tip').hide();
				jQuery(document).triggerHandler('em_locations_autocomplete_selected', [event, ui]);
				return false;
			}
		}).data( "ui-autocomplete" )._renderItem = function( ul, item ) {
			html_val = "<a>" + item.label + '<br><span style="font-size:11px"><em>'+ item.address + ', ' + item.town+"</em></span></a>";
			return jQuery( "<li></li>" ).data( "item.autocomplete", item ).append(html_val).appendTo( ul );
		};
		jQuery('#em-location-reset a').click( function(){
			jQuery('#em-location-data input').css('background-color','#fff').val('').removeAttr('readonly');
			jQuery('#em-location-data select').css('background-color','#fff');
			jQuery('#em-location-data option:selected').removeAttr('selected');
			jQuery('input#location-id').val('');
			jQuery('#em-location-reset').hide();
			jQuery('#em-location-search-tip').show();
			jQuery('#em-map').hide();
			jQuery('#em-map-404').show();
			if(typeof(marker) !== 'undefined'){
				marker.setPosition(new google.maps.LatLng(0, 0));
				infoWindow.close();
				marker.setDraggable(true);
			}
			return false;
		});
		if( jQuery('input#location-id').val() != '0' && jQuery('input#location-id').val() != '' ){
			jQuery('#em-location-data input, #em-location-data select').css('background-color','#ccc').attr('readonly','readonly');
			jQuery('#em-location-reset').show();
			jQuery('#em-location-search-tip').hide();
		}
	}
	
});

function em_setup_datepicker(wrap){	
	wrap = jQuery(wrap);
	//default picker vals
	var datepicker_vals = { altFormat: "yy-mm-dd", changeMonth: true, changeYear: true, firstDay : EM.firstDay };
	if( EM.dateFormat != ''){
		datepicker_vals.dateFormat = EM.dateFormat;
	}
	jQuery(document).triggerHandler('em_datepicker', datepicker_vals);
	
	//apply datepickers
	dateDivs = wrap.find('.em-date-single, .em-date-range');
	if( dateDivs.length > 0 ){
		//apply datepickers to elements
		dateDivs.find('input.em-date-input-loc').each(function(i,dateInput){
			//init the datepicker
			var dateInput = jQuery(dateInput);
			var dateValue = dateInput.nextAll('input.em-date-input').first();
			var dateValue_value = dateValue.val();
			dateInput.datepicker(datepicker_vals);
			dateInput.datepicker('option', 'altField', dateValue);
			//now set the value
			if( dateValue_value ){
				var this_date_formatted = jQuery.datepicker.formatDate( EM.dateFormat, jQuery.datepicker.parseDate('yy-mm-dd', dateValue_value) );
				dateInput.val(this_date_formatted);
				dateValue.val(dateValue_value);
			}
			//add logic for texts
			dateInput.change(function(){
				if( jQuery(this).val() == '' ){
					jQuery(this).nextAll('.em-date-input').first().val('');
				}
			});
		});
		//deal with date ranges
		dateDivs.filter('.em-date-range').find('input.em-date-input-loc').each(function(i,dateInput){
			//finally, apply start/end logic to this field
			dateInput = jQuery(dateInput);
			if( dateInput.hasClass('em-date-start') ){
				dateInput.datepicker('option','onSelect', function( selectedDate ) {
					//get corresponding end date input, we expect ranges to be contained in .em-date-range with a start/end input element
					var startDate = jQuery(this);
					var endDate = startDate.parents('.em-date-range').find('.em-date-end').first();
					if( startDate.val() > endDate.val() && endDate.val() != '' ){
						endDate.datepicker( "setDate" , selectedDate );
					}
					endDate.datepicker( "option", 'minDate', selectedDate );
				});
			}else if( dateInput.hasClass('em-date-end') ){
				var startInput = dateInput.parents('.em-date-range').find('.em-date-start').first();
				if( startInput.val() != '' ){
					dateInput.datepicker('option', 'minDate', startInput.val());
				}
			}
		});
	}
}

function em_setup_timepicker(wrap){
	wrap = jQuery(wrap);
	wrap.find(".em-time-input").timePicker({
		show24Hours: EM.show24hours == 1,
		step:15
	});
	
	// Keep the duration between the two inputs.
	wrap.find(".em-time-range input.em-time-start").each( function(i, el){
		jQuery(el).data('oldTime', jQuery.timePicker(el).getTime());
	}).change( function() {
		var start = jQuery(this);
		var end = start.nextAll('.em-time-end');
		if (end.val()) { // Only update when second input has a value.
		    // Calculate duration.
			var oldTime = start.data('oldTime');
		    var duration = (jQuery.timePicker(end).getTime() - oldTime);
		    var time = jQuery.timePicker(start).getTime();
		    if( jQuery.timePicker(end).getTime() >= oldTime ){
			    // Calculate and update the time in the second input.
			    jQuery.timePicker(end).setTime(new Date(new Date(time.getTime() + duration)));
			}
		    start.data('oldTime', time); 
		}
	});
	// Validate.
	wrap.find(".em-time-range input.em-time-end").change(function() {
		var end = jQuery(this);
		var start = end.prevAll('.em-time-start');
		if( start.val() ){
			if( jQuery.timePicker(start).getTime() > jQuery.timePicker(this).getTime() && ( jQuery('.em-date-end').val().length == 0 || jQuery('.em-date-start').val() == jQuery('.em-date-end').val() ) ) { end.addClass("error"); }
			else { end.removeClass("error"); }
		}
	});
	//Sort out all day checkbox
	wrap.find('.em-time-range input.em-time-all-day').change(function(){
		var allday = jQuery(this);
		if( allday.is(':checked') ){
			allday.siblings('.em-time-input').css('background-color','#ccc');
		}else{
			allday.siblings('.em-time-input').css('background-color','#fff');
		}
	}).trigger('change');
}

/* Useful function for adding the em_ajax flag to a url, regardless of querystring format */
var em_ajaxify = function(url){
	if ( url.search('em_ajax=0') != -1){
		url = url.replace('em_ajax=0','em_ajax=1');
	}else if( url.search(/\?/) != -1 ){
		url = url + "&em_ajax=1";
	}else{
		url = url + "?em_ajax=1";
	}
	return url;
};

/*
 * MAP FUNCTIONS
 */
var maps = {};
var maps_markers = {};
var infowindow;
//Load single maps (each map is treated as a seperate map.
function em_maps() {
	//Find all the maps on this page
	jQuery('.em-location-map').each( function(index){
		el = jQuery(this);
		var map_id = el.attr('id').replace('em-location-map-','');
		em_LatLng = new google.maps.LatLng( jQuery('#em-location-map-coords-'+map_id+' .lat').text(), jQuery('#em-location-map-coords-'+map_id+' .lng').text());
		maps[map_id] = new google.maps.Map( document.getElementById('em-location-map-'+map_id), {
		    zoom: 14,
		    center: em_LatLng,
		    mapTypeId: google.maps.MapTypeId.ROADMAP,
		    mapTypeControl: false
		});
		var marker = new google.maps.Marker({
		    position: em_LatLng,
		    map: maps[map_id]
		});
		infowindow = new google.maps.InfoWindow({ content: jQuery('#em-location-map-info-'+map_id+' .em-map-balloon').get(0) });
		infowindow.open(maps[map_id],marker);
		maps[map_id].panBy(40,-70);
		
		//JS Hook for handling map after instantiation
		//Example hook, which you can add elsewhere in your theme's JS - jQuery(document).bind('em_maps_location_hook', function(){ alert('hi');} );
		jQuery(document).triggerHandler('em_maps_location_hook', [maps[map_id], infowindow, marker, map_id]);
	});
	jQuery('.em-locations-map').each( function(index){
		var el = jQuery(this);
		var map_id = el.attr('id').replace('em-locations-map-','');
		var em_data = jQuery.parseJSON( jQuery('#em-locations-map-coords-'+map_id).text() );
		jQuery.getJSON(document.URL, em_data , function(data){
			if(data.length > 0){
				  var myOptions = {
				    mapTypeId: google.maps.MapTypeId.ROADMAP
				  };
				  maps[map_id] = new google.maps.Map(document.getElementById("em-locations-map-"+map_id), myOptions);
				  maps_markers[map_id] = [];
				  
				  var minLatLngArr = [0,0];
				  var maxLatLngArr = [0,0];
				  
				  for (var i = 0; i < data.length; i++) {
					  if( !(data[i].location_latitude == 0 && data[i].location_longitude == 0) ){
						var latitude = parseFloat( data[i].location_latitude );
						var longitude = parseFloat( data[i].location_longitude );
						var location = new google.maps.LatLng( latitude, longitude );
						var marker = new google.maps.Marker({
						    position: location, 
						    map: maps[map_id]
						});
						maps_markers[map_id].push(marker);
						marker.setTitle(data[i].location_name);
						var myContent = '<div class="em-map-balloon"><div id="em-map-balloon-'+map_id+'" class="em-map-balloon-content">'+ data[i].location_balloon +'</div></div>';
						em_map_infobox(marker, myContent, maps[map_id]);
						
						//Get min and max long/lats
						minLatLngArr[0] = (latitude < minLatLngArr[0] || i == 0) ? latitude : minLatLngArr[0];
						minLatLngArr[1] = (longitude < minLatLngArr[1] || i == 0) ? longitude : minLatLngArr[1];
						maxLatLngArr[0] = (latitude > maxLatLngArr[0] || i == 0) ? latitude : maxLatLngArr[0];
						maxLatLngArr[1] = (longitude > maxLatLngArr[1] || i == 0) ? longitude : maxLatLngArr[1];
					  }
				  }
				  // Zoom in to the bounds
				  var minLatLng = new google.maps.LatLng(minLatLngArr[0],minLatLngArr[1]);
				  var maxLatLng = new google.maps.LatLng(maxLatLngArr[0],maxLatLngArr[1]);
				  var bounds = new google.maps.LatLngBounds(minLatLng,maxLatLng);
				  maps[map_id].fitBounds(bounds);
				  
				//Call a hook if exists
				jQuery(document).triggerHandler('em_maps_locations_hook', [maps[map_id], data, map_id]);
			}else{
				el.children().first().html('No locations found');
			}
		});
	});
	//Location stuff - only needed if inputs for location exist
	if( jQuery('select#location-select-id, input#location-address').length > 0 ){
		//load map info
		var refresh_map_location = function(){
			var location_latitude = jQuery('#location-latitude').val();
			var location_longitude = jQuery('#location-longitude').val();
			if( !(location_latitude == 0 && location_longitude == 0) ){
				var position = new google.maps.LatLng(location_latitude, location_longitude); //the location coords
				marker.setPosition(position);
				var mapTitle = (jQuery('input#location-name').length > 0) ? jQuery('input#location-name').val():jQuery('input#title').val();
				marker.setTitle( jQuery('input#location-name input#title, #location-select-id').first().val() );
				jQuery('#em-map').show();
				jQuery('#em-map-404').hide();
				google.maps.event.trigger(map, 'resize');
				map.setCenter(position);
				map.panBy(40,-55);
				infoWindow.setContent( 
					'<div id="location-balloon-content"><strong>' + 
					mapTitle + 
					'</strong><br/>' + 
					jQuery('#location-address').val() + 
					'<br/>' + jQuery('#location-town').val()+ 
					'</div>'
				);
				infoWindow.open(map, marker);
			} else {
    			jQuery('#em-map').hide();
    			jQuery('#em-map-404').show();
			}
		};
		
		//Add listeners for changes to address
		var get_map_by_id = function(id){
			if(jQuery('#em-map').length > 0){
				jQuery.getJSON(document.URL,{ em_ajax_action:'get_location', id:id }, function(data){
					if( data.location_latitude!=0 && data.location_longitude!=0 ){
						loc_latlng = new google.maps.LatLng(data.location_latitude, data.location_longitude);
						marker.setPosition(loc_latlng);
						marker.setTitle( data.location_name );
						marker.setDraggable(false);
						jQuery('#em-map').show();
						jQuery('#em-map-404').hide();
						map.setCenter(loc_latlng);
						map.panBy(40,-55);
						infoWindow.setContent( '<div id="location-balloon-content">'+ data.location_balloon +'</div>');
						infoWindow.open(map, marker);
						google.maps.event.trigger(map, 'resize');
					}else{
						jQuery('#em-map').hide();
						jQuery('#em-map-404').show();
					}
				});
			}
		};
		jQuery('#location-select-id, input#location-id').change( function(){get_map_by_id(jQuery(this).val());} );
		jQuery('#location-name, #location-town, #location-address, #location-state, #location-postcode, #location-country').change( function(){
			//build address
			var addresses = [ jQuery('#location-address').val(), jQuery('#location-town').val(), jQuery('#location-state').val(), jQuery('#location-postcode').val() ];
			var address = '';
			jQuery.each( addresses, function(i, val){
				if( val != '' ){
					address = ( address == '' ) ? address+val:address+', '+val;
				}
			});
			if( address == '' ){ //in case only name is entered, no address
				jQuery('#em-map').hide();
				jQuery('#em-map-404').show();
				return false;
			}
			//do country last, as it's using the text version
			if( jQuery('#location-country option:selected').val() != 0 ){
				address = ( address == '' ) ? address+jQuery('#location-country option:selected').text():address+', '+jQuery('#location-country option:selected').text();
			}
			if( address != '' && jQuery('#em-map').length > 0 ){
				geocoder.geocode( { 'address': address }, function(results, status) {
				    if (status == google.maps.GeocoderStatus.OK) {
						jQuery('#location-latitude').val(results[0].geometry.location.lat());
						jQuery('#location-longitude').val(results[0].geometry.location.lng());
					}
				    refresh_map_location();
				});
			}
		});
		
		//Load map
		if(jQuery('#em-map').length > 0){
			var em_LatLng = new google.maps.LatLng(0, 0);
			var map = new google.maps.Map( document.getElementById('em-map'), {
			    zoom: 14,
			    center: em_LatLng,
			    mapTypeId: google.maps.MapTypeId.ROADMAP,
			    mapTypeControl: false
			});
			var marker = new google.maps.Marker({
			    position: em_LatLng,
			    map: map,
			    draggable: true
			});
			infoWindow = new google.maps.InfoWindow({
			    content: ''
			});
			var geocoder = new google.maps.Geocoder();
			google.maps.event.addListener(infoWindow, 'domready', function() { 
				document.getElementById('location-balloon-content').parentNode.style.overflow=''; 
				document.getElementById('location-balloon-content').parentNode.parentNode.style.overflow=''; 
			});
			google.maps.event.addListener(marker, 'dragend', function() {
				var position = marker.getPosition();
				jQuery('#location-latitude').val(position.lat());
				jQuery('#location-longitude').val(position.lng());
				map.setCenter(position);
				map.panBy(40,-55);
			});
			if( jQuery('#location-select-id').length > 0 ){
				jQuery('#location-select-id').trigger('change');
			}else{
				refresh_map_location();
			}
			jQuery(document).triggerHandler('em_map_loaded', [map, infowindow, marker]);
		}
	}
}
  
function em_map_infobox(marker, message, map) {
  var iw = new google.maps.InfoWindow({ content: message });
  google.maps.event.addListener(marker, 'click', function() {
	if( infowindow ) infowindow.close();
	infowindow = iw;
    iw.open(map,marker);
  });
}

/* jQuery timePicker - http://labs.perifer.se/timedatepicker/ @ http://github.com/perifer/timePicker commit 100644 */
/*
 * A time picker for jQuery
 *
 * Dual licensed under the MIT and GPL licenses.
 * Copyright (c) 2009 Anders Fajerson
 * @name     timePicker
 * @author   Anders Fajerson (http://perifer.se)
 * @example  $("#mytime").timePicker();
 * @example  $("#mytime").timePicker({step:30, startTime:"15:00", endTime:"18:00"});
 *
 * Based on timePicker by Sam Collet (http://www.texotela.co.uk/code/jquery/timepicker/)
 *
 * Options:
 *   step: # of minutes to step the time by
 *   startTime: beginning of the range of acceptable times
 *   endTime: end of the range of acceptable times
 *   separator: separator string to use between hours and minutes (e.g. ':')
 *   show24Hours: use a 24-hour scheme
 */
(function($){
  $.fn.timePicker = function(options) {
    // Build main options before element iteration
    var settings = $.extend({}, $.fn.timePicker.defaults, options);

    return this.each(function() {
      $.timePicker(this, settings);
    });
  };

  $.timePicker = function (elm, settings) {
    var e = $(elm)[0];
    return e.timePicker || (e.timePicker = new jQuery._timePicker(e, settings));
  };

  $.timePicker.version = '0.3';

  $._timePicker = function(elm, settings) {

    var tpOver = false;
    var keyDown = false;
    var startTime = timeToDate(settings.startTime, settings);
    var endTime = timeToDate(settings.endTime, settings);
    var selectedClass = "selected";
    var selectedSelector = "li." + selectedClass;

    $(elm).attr('autocomplete', 'OFF'); // Disable browser autocomplete

    var times = [];
    var time = new Date(startTime); // Create a new date object.
    while(time <= endTime) {
      times[times.length] = formatTime(time, settings);
      time = new Date(time.setMinutes(time.getMinutes() + settings.step));
    }

    var $tpDiv = $('<div class="time-picker'+ (settings.show24Hours ? '' : ' time-picker-12hours') +'"></div>');
    var $tpList = $('<ul></ul>');

    // Build the list.
    for(var i = 0; i < times.length; i++) {
      $tpList.append("<li>" + times[i] + "</li>");
    }
    $tpDiv.append($tpList);
    // Append the timPicker to the body and position it.
    $tpDiv.appendTo('body').hide();

    // Store the mouse state, used by the blur event. Use mouseover instead of
    // mousedown since Opera fires blur before mousedown.
    $tpDiv.mouseover(function() {
      tpOver = true;
    }).mouseout(function() {
      tpOver = false;
    });

    $("li", $tpList).mouseover(function() {
      if (!keyDown) {
        $(selectedSelector, $tpDiv).removeClass(selectedClass);
        $(this).addClass(selectedClass);
      }
    }).mousedown(function() {
       tpOver = true;
    }).click(function() {
      setTimeVal(elm, this, $tpDiv, settings);
      tpOver = false;
    });

    var showPicker = function() {
      if ($tpDiv.is(":visible")) {
        return false;
      }
      $("li", $tpDiv).removeClass(selectedClass);

      // Position
      var elmOffset = $(elm).offset();
      $tpDiv.css({'top':elmOffset.top + elm.offsetHeight, 'left':elmOffset.left});

      // Show picker. This has to be done before scrollTop is set since that
      // can't be done on hidden elements.
      $tpDiv.show();

      // Try to find a time in the list that matches the entered time.
      var time = elm.value ? timeStringToDate(elm.value, settings) : startTime;
      var startMin = startTime.getHours() * 60 + startTime.getMinutes();
      var min = (time.getHours() * 60 + time.getMinutes()) - startMin;
      var steps = Math.round(min / settings.step);
      var roundTime = normaliseTime(new Date(0, 0, 0, 0, (steps * settings.step + startMin), 0));
      roundTime = (startTime < roundTime && roundTime <= endTime) ? roundTime : startTime;
      var $matchedTime = $("li:contains(" + formatTime(roundTime, settings) + ")", $tpDiv);

      if ($matchedTime.length) {
        $matchedTime.addClass(selectedClass);
        // Scroll to matched time.
        $tpDiv[0].scrollTop = $matchedTime[0].offsetTop;
      }
      return true;
    };
    // Attach to click as well as focus so timePicker can be shown again when
    // clicking on the input when it already has focus.
    $(elm).focus(showPicker).click(showPicker);
    // Hide timepicker on blur
    $(elm).blur(function() {
      if (!tpOver) {
        $tpDiv.hide();
      }
    });
    // Keypress doesn't repeat on Safari for non-text keys.
    // Keydown doesn't repeat on Firefox and Opera on Mac.
    // Using kepress for Opera and Firefox and keydown for the rest seems to
    // work with up/down/enter/esc.
    $(elm)['keydown'](function(e) {
      var $selected;
      keyDown = true;
      var top = $tpDiv[0].scrollTop;
      switch (e.keyCode) {
        case 38: // Up arrow.
          // Just show picker if it's hidden.
          if (showPicker()) {
            return false;
          };
          $selected = $(selectedSelector, $tpList);
          var prev = $selected.prev().addClass(selectedClass)[0];
          if (prev) {
            $selected.removeClass(selectedClass);
            // Scroll item into view.
            if (prev.offsetTop < top) {
              $tpDiv[0].scrollTop = top - prev.offsetHeight;
            }
          }
          else {
            // Loop to next item.
            $selected.removeClass(selectedClass);
            prev = $("li:last", $tpList).addClass(selectedClass)[0];
            $tpDiv[0].scrollTop = prev.offsetTop - prev.offsetHeight;
          }
          return false;
          break;
        case 40: // Down arrow, similar in behaviour to up arrow.
          if (showPicker()) {
            return false;
          };
          $selected = $(selectedSelector, $tpList);
          var next = $selected.next().addClass(selectedClass)[0];
          if (next) {
            $selected.removeClass(selectedClass);
            if (next.offsetTop + next.offsetHeight > top + $tpDiv[0].offsetHeight) {
              $tpDiv[0].scrollTop = top + next.offsetHeight;
            }
          }
          else {
            $selected.removeClass(selectedClass);
            next = $("li:first", $tpList).addClass(selectedClass)[0];
            $tpDiv[0].scrollTop = 0;
          }
          return false;
          break;
        case 13: // Enter
          if ($tpDiv.is(":visible")) {
            var sel = $(selectedSelector, $tpList)[0];
            setTimeVal(elm, sel, $tpDiv, settings);
          }
          return false;
          break;
        case 27: // Esc
          $tpDiv.hide();
          return false;
          break;
      }
      return true;
    });
    $(elm).keyup(function(e) {
      keyDown = false;
    });
    // Helper function to get an inputs current time as Date object.
    // Returns a Date object.
    this.getTime = function() {
      return timeStringToDate(elm.value, settings);
    };
    // Helper function to set a time input.
    // Takes a Date object or string.
    this.setTime = function(time) {
      elm.value = formatTime(timeToDate(time, settings), settings);
      // Trigger element's change events.
      $(elm).change();
    };

  }; // End fn;

  // Plugin defaults.
  $.fn.timePicker.defaults = {
    step:30,
    startTime: new Date(0, 0, 0, 0, 0, 0),
    endTime: new Date(0, 0, 0, 23, 30, 0),
    separator: ':',
    show24Hours: true
  };

  // Private functions.

  function setTimeVal(elm, sel, $tpDiv, settings) {
    // Update input field
    elm.value = $(sel).text();
    // Trigger element's change events.
    $(elm).change();
    // Keep focus for all but IE (which doesn't like it)
    if (!navigator.userAgent.match(/msie/i)) {
      elm.focus();
    }
    // Hide picker
    $tpDiv.hide();
  }

  function formatTime(time, settings) {
    var h = time.getHours();
    var hours = settings.show24Hours ? h : (((h + 11) % 12) + 1);
    var minutes = time.getMinutes();
    return formatNumber(hours) + settings.separator + formatNumber(minutes) + (settings.show24Hours ? '' : ((h < 12) ? ' AM' : ' PM'));
  }

  function formatNumber(value) {
    return (value < 10 ? '0' : '') + value;
  }

  function timeToDate(input, settings) {
    return (typeof input == 'object') ? normaliseTime(input) : timeStringToDate(input, settings);
  }

  function timeStringToDate(input, settings) {
    if (input) {
      var array = input.split(settings.separator);
      var hours = parseFloat(array[0]);
      var minutes = parseFloat(array[1]);

      // Convert AM/PM hour to 24-hour format.
      if (!settings.show24Hours) {
        if (hours === 12 && input.indexOf('AM') !== -1) {
          hours = 0;
        }
        else if (hours !== 12 && input.indexOf('PM') !== -1) {
          hours += 12;
        }
      }
      var time = new Date(0, 0, 0, hours, minutes, 0);
      return normaliseTime(time);
    }
    return null;
  }

  /* Normalise time object to a common date. */
  function normaliseTime(time) {
    time.setFullYear(2001);
    time.setMonth(0);
    time.setDate(0);
    return time;
  }

})(jQuery);