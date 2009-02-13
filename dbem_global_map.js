$j=jQuery.noConflict();   

$j(document).ready(function() {
	$j('p#provina').html('ok'); 
	
	$j.getJSON(document.URL,{ajax: 'true', query:'GMapKey'}, function(data){
  	  	GMapsKey = data.GMapsKey;
	   
	    loadMapScript(GMapsKey);
		
		
  		});
  	      	  

 	});


          
function loadGMap() {
      if (GBrowserIsCompatible()) {
        	
		var geocoder = new GClientGeocoder();
		var venues;
		$j.getJSON(document.URL,{ajax: 'true', query:'GlobalMapData'}, function(data){
	  	  	venues = data.venues;    
			var latitudes = new Array();
			var longitudes = new Array();
			var max_latitude = -500.1;
			var min_latitude = 500.1;
			var max_longitude = -500.1;
			var min_longitude = 500.1;    
			
			
			var map = new GMap2(document.getElementById("dbem_global_map"));
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
			
			$j.each(venues, function(i, item){
             	var point = new GLatLng(parseFloat(item.venue_latitude), parseFloat(item.venue_longitude));
				map.addOverlay(new GMarker(point));                                                       
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