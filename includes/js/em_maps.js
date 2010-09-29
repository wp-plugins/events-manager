function em_load_map( callback ) {
	var script = document.createElement("script");
	script.setAttribute("src", "http://maps.google.com/maps/api/js?sensor=false&callback="+callback);
	script.setAttribute("type", "text/javascript");
	document.documentElement.firstChild.appendChild(script);
}

//Load a map on a single page
function em_map_single() {
	em_LatLng = new google.maps.LatLng(latitude, longitude);
	var map = new google.maps.Map( document.getElementById('em-location-map'), {
	    zoom: 14,
	    center: em_LatLng,
	    mapTypeId: google.maps.MapTypeId.ROADMAP,
	    mapTypeControl: false
	});
	var infowindow = new google.maps.InfoWindow({ content: document.getElementById('em-location-map-info').firstChild });
	var marker = new google.maps.Marker({
	    position: em_LatLng,
	    map: map
	});
	infowindow.open(map,marker);
}

//Load a map for multiple locations
function em_map_global(){
	jQuery.getJSON(document.URL,{ajax: 'true', query:'GlobalMapData', eventful:eventful}, function(data){
		//Load a map on a single page
		//TODO create an option for where to center the map, for now we just use the first event
		var map = new google.maps.Map( document.getElementById('em-locations-map'), {
		    zoom: 13,
		    center: new google.maps.LatLng(data[0].location_latitude, data[0].location_longitude),
		    mapTypeId: google.maps.MapTypeId.ROADMAP,
		    mapTypeControl: false
		});
		var markers = [];
		var infoWindow = new google.maps.InfoWindow;
		for( i=0 ; i<data.length; i++ ){
			latitude = data[i].location_latitude;
			longitude = data[i].location_longitude;
			balloon = '<div id="location-balloon-content">'+ data[i].location_balloon +'</div>';
			markers[i] = new google.maps.Marker({
			    position: new google.maps.LatLng( parseFloat(latitude), parseFloat(longitude) ),
			    map: map,
			    title: data[i].location_name
			});
			google.maps.event.addListener(markers[i], 'click', function() {
		        infoWindow.setContent( balloon );
		        infoWindow.open(map, markers[i]);
		    });
			google.maps.event.addListener(infoWindow, 'domready', function() { 
				document.getElementById('location-balloon-content').parentNode.style.overflow=''; 
				document.getElementById('location-balloon-content').parentNode.parentNode.style.overflow=''; 
			});
		}
	});
}