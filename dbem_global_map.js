$j=jQuery.noConflict();   
console.log("eventful: " + eventful + " scope " + scope);

$j(document.body).unload(function() {
	if (GBrowserIsCompatible()) {
		GUnload();
	}
});


$j(document).ready(function() {
	loadMapScript(GMapsKey);
});



function loadGMap() {
	if (GBrowserIsCompatible()) {

		var locations;
		$j.getJSON(document.URL,{ajax: 'true', query:'GlobalMapData', eventful:eventful}, function(data){
			locations = data.locations;    
			var latitudes = new Array();
			var longitudes = new Array();
			var max_latitude = -500.1;
			var min_latitude = 500.1;
			var max_longitude = -500.1;
			var min_longitude = 500.1;    

			map = new GMap2(document.getElementById("dbem_global_map"));
			map.setCenter(new GLatLng(45.4213477,10.952397), 3);

			$j.each(locations, function(i, item){
				latitudes.push(item.location_latitude);
				longitudes.push(item.location_longitude);
				if (parseFloat(item.location_latitude) > max_latitude)
				max_latitude = parseFloat(item.location_latitude);
				if (parseFloat(item.location_latitude) < min_latitude)
				min_latitude = parseFloat(item.location_latitude);
				if (parseFloat(item.location_longitude) > max_longitude)
				max_longitude = parseFloat(item.location_longitude);
				if (parseFloat(item.location_longitude) < min_longitude)
				min_longitude = parseFloat(item.location_longitude); 


			});

			console.log("Latitudes: " + latitudes + " MAX: " + max_latitude + " MIN: " + min_latitude);
			console.log("Longitudes: " + longitudes +  " MAX: " + max_longitude + " MIN: " + min_longitude);    

			center_lat = min_latitude + (max_latitude - min_latitude)/2;
			center_lon = min_longitude + (max_longitude - min_longitude)/2;
			console.log("center: " + center_lat + " - " + center_lon) + min_longitude;

			lat_interval = max_latitude - min_latitude;

			//vertical compensation to fit in the markers
			vertical_compensation = lat_interval * 0.1;

			var locationsBound = new GLatLngBounds(new GLatLng(max_latitude + vertical_compensation,min_longitude),new GLatLng(min_latitude,max_longitude) );
			console.log(locationsBound);
			var locationsZoom = map.getBoundsZoomLevel(locationsBound);
			map.setCenter(new GLatLng(center_lat + vertical_compensation,center_lon), locationsZoom); 
			var letters = new Array('A','B','C','D','E','F','G','H');
			var customIcon = new GIcon(G_DEFAULT_ICON);

			$j.each(locations, function(i, item){
				var letter = letters[i];

				customIcon.image = "http://www.google.com/mapfiles/marker" + letter + ".png";

				markerOption = { icon:customIcon };
				var point = new GLatLng(parseFloat(item.location_latitude), parseFloat(item.location_longitude));
				var marker = new GMarker(point, markerOption);
				map.addOverlay(marker);
				var li_element = "<li id='location-"+item.location_id+"' style='list-style-type: upper-alpha'><a >"+ item.location_name+"</a></li>";
				$j('ol#dbem_locations_list').append(li_element);
				$j('li#location-'+item.location_id+' a').click(function(){
					displayLocationInfo(marker, item);
				});
				GEvent.addListener(marker, "click", function() {
					displayLocationInfo(marker, item);

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

function displayLocationInfo(marker, location) {
	var location_infos = "<strong>"+ location.location_name +"</strong><br/>" + location.location_address + ", " + location.location_town + "<br/><small><a href='" + events_page + "&location_id=" + location.location_id + "'>Details<a>";
	window.map.openInfoWindowHtml(marker.getLatLng(),location.location_baloon,  {pixelOffset: new GSize(0,-32)});
}