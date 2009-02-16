$j=jQuery.noConflict();   
console.log("single location map");

$j(document.body).unload(function() {
	if (GBrowserIsCompatible()) {
		GUnload();
	}
});


$j(document).ready(function() {
	loadMapScript(GMapsKey);
});

function loadMapScript(key) {
	var script = document.createElement("script");
	script.setAttribute("src", "http://maps.google.com/maps?file=api&v=2.x&key=" + key + "&c&async=2&callback=loadGMap");
	script.setAttribute("type", "text/javascript");
	document.documentElement.firstChild.appendChild(script);
}

function loadGMap() {
	if (GBrowserIsCompatible()) {
		map = new GMap2(document.getElementById("dbem-location-map"));
		point = new GLatLng(latitude, longitude);
		mapCenter= new GLatLng(point.lat()+0.005, point.lng()-0.003);
        map.setCenter(mapCenter, 14);
        var marker = new GMarker(point);
        map.addOverlay(marker);
		marker.openInfoWindowHtml(map_text);
	}
}