$j=jQuery.noConflict();   
console.log("eventful: " + eventful + " scope " + scope);

$j(document.body).unload(function() {
	if (GBrowserIsCompatible()) {
		GUnload();
	}
});


$j(document).ready(function() {
	loadMapScript(GMapsKey);
	// $j.getJSON(document.URL,{ajax: 'true', query:'GMapKey'}, function(data){
	// 	GMapsKey = data.GMapsKey;
	// 	loadMapScript(GMapsKey);
	// });
});



function loadGMap() {
	if (GBrowserIsCompatible()) {

		var geocoder = new GClientGeocoder();
		var venues;
		$j.getJSON(document.URL,{ajax: 'true', query:'GlobalMapData', eventful:eventful}, function(data){
			venues = data.venues;    
			var latitudes = new Array();
			var longitudes = new Array();
			var max_latitude = -500.1;
			var min_latitude = 500.1;
			var max_longitude = -500.1;
			var min_longitude = 500.1;    

			map = new GMap2(document.getElementById("dbem_global_map"));
			map.setCenter(new GLatLng(45.4213477,10.952397), 3);

			$j.each(venues, function(i, item){
				latitudes.push(item.venue_latitude);
				longitudes.push(item.venue_longitude);
				if (parseFloat(item.venue_latitude) > max_latitude)
				max_latitude = parseFloat(item.venue_latitude);
				if (parseFloat(item.venue_latitude) < min_latitude)
				min_latitude = parseFloat(item.venue_latitude);
				if (parseFloat(item.venue_longitude) > max_longitude)
				max_longitude = parseFloat(item.venue_longitude);
				if (parseFloat(item.venue_longitude) < min_longitude)
				min_longitude = parseFloat(item.venue_longitude); 


			});

			console.log("Latitudes: " + latitudes + " MAX: " + max_latitude + " MIN: " + min_latitude);
			console.log("Longitudes: " + longitudes +  " MAX: " + max_longitude + " MIN: " + min_longitude);    

			center_lat = min_latitude + (max_latitude - min_latitude)/2;
			center_lon = min_longitude + (max_longitude - min_longitude)/2;
			console.log("center: " + center_lat + " - " + center_lon) + min_longitude;

			lat_interval = max_latitude - min_latitude;

			//vertical compensation to fit in the markers
			vertical_compensation = lat_interval * 0.1;

			var venuesBound = new GLatLngBounds(new GLatLng(max_latitude + vertical_compensation,min_longitude),new GLatLng(min_latitude,max_longitude) );
			console.log(venuesBound);
			var venuesZoom = map.getBoundsZoomLevel(venuesBound);
			map.setCenter(new GLatLng(center_lat + vertical_compensation,center_lon), venuesZoom); 
			var letters = new Array('A','B','C','D','E','F','G','H');
			var customIcon = new GIcon(G_DEFAULT_ICON);

			$j.each(venues, function(i, item){
				var letter = letters[i];

				customIcon.image = "http://www.google.com/mapfiles/marker" + letter + ".png";

				markerOption = { icon:customIcon };
				var point = new GLatLng(parseFloat(item.venue_latitude), parseFloat(item.venue_longitude));
				var marker = new GMarker(point, markerOption);
				map.addOverlay(marker);
				var li_element = "<li id='venue-"+item.venue_id+"' style='list-style-type: upper-alpha'><a >"+ item.venue_name+"</a></li>";
				$j('ol#dbem_venues_list').append(li_element);
				$j('li#venue-'+item.venue_id+' a').click(function(){
					displayVenueInfo(marker, item);
				});
				GEvent.addListener(marker, "click", function() {
					displayVenueInfo(marker, item);

				});


			});



		});


	}
}


function loadMapScript(key) {
	var script = document.createElement("script");
	script.setAttribute("src", "http://maps.google.com/maps?file=api&v=2.x&key=" + key + "&c&async=2&callback=loadGMap");
	script.setAttribute("type", "text/javascript");
	document.documentElement.firstChild.appendChild(script);
}

function displayVenueInfo(marker, venue) {
	var venue_infos = "<strong>"+ venue.venue_name +"</strong><br/>" + venue.venue_address + ", " + venue.venue_town + "<br/><small><a href='" + events_page + "&venue_id=" + venue.venue_id + "'>Details<a>";
	window.map.openInfoWindowHtml(marker.getLatLng(),venue_infos,  {pixelOffset: new GSize(0,-32)});
}