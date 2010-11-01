var map;

function em_load_map( callback ) {
	var script = document.createElement("script");
	script.setAttribute("src", "http://maps.google.com/maps/api/js?sensor=false&callback="+callback);
	script.setAttribute("type", "text/javascript");
	document.documentElement.firstChild.appendChild(script);
}

//Load a map on a single page
function em_map_single() {
	em_LatLng = new google.maps.LatLng(em_latitude, em_longitude);
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

function em_map_global() {
	jQuery.getJSON(document.URL, em_query, function(data){
		if(data.length > 0){
			  var myLatlng = new google.maps.LatLng(data[0].location_latitude,data[0].location_longitude);
			  var myOptions = {
			    mapTypeId: google.maps.MapTypeId.ROADMAP
			  };
			  map = new google.maps.Map(document.getElementById("em-locations-map"), myOptions);
			  
			  var minLatLngArr = [0,0];
			  var maxLatLngArr = [0,0];
			  
			  for (var i = 0; i < data.length; i++) {
				var latitude = parseFloat( data[i].location_latitude );
				var longitude = parseFloat( data[i].location_longitude );
				var location = new google.maps.LatLng( latitude, longitude );
			    var marker = new google.maps.Marker({
			        position: location, 
			        map: map
			    });
			    marker.setTitle(data[i].location_name);
				var myContent = '<div class="em-map-balloon"><div id="content">'+ data[i].location_balloon +'</div></div>';
				em_map_infobox(marker, myContent);
				
				//Get min and max long/lats
				minLatLngArr[0] = (latitude < minLatLngArr[0] || i == 0) ? latitude : minLatLngArr[0];
				minLatLngArr[1] = (longitude < minLatLngArr[1] || i == 0) ? longitude : minLatLngArr[1];
				maxLatLngArr[0] = (latitude > maxLatLngArr[0] || i == 0) ? latitude : maxLatLngArr[0];
				maxLatLngArr[1] = (longitude > maxLatLngArr[1] || i == 0) ? longitude : maxLatLngArr[1];		
			  }
			  // Zoom in to the bounds
			  var minLatLng = new google.maps.LatLng(minLatLngArr[0],minLatLngArr[1]);
			  var maxLatLng = new google.maps.LatLng(maxLatLngArr[0],maxLatLngArr[1]);
			  var bounds = new google.maps.LatLngBounds(minLatLng,maxLatLng);
			  map.fitBounds(bounds);
		}else{
			jQuery('#em-locations-map').children().first().html('No locations found');
		}
	});
}
 
// The five markers show a secret message when clicked
// but that message is not within the marker's instance data
 
function em_map_infobox(marker, message) {
  var infowindow = new google.maps.InfoWindow({ content: message });
  google.maps.event.addListener(marker, 'click', function() {
    infowindow.open(map,marker);
  });
}