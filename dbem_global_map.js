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
			var max_latitude = -500;
			var min_latitude = 500;
			var max_longitude = -500;
			var min_longitude = 500;    
			
			
			var map = new GMap2(document.getElementById("dbem_global_map"));
			map.setCenter(new GLatLng(10.952397, 45.4213477), 10);

			// Add 10 markers to the map at random locations
			var bounds = map.getBounds();
			var southWest = bounds.getSouthWest();
			var northEast = bounds.getNorthEast();
			var lngSpan = northEast.lng() - southWest.lng();
			var latSpan = northEast.lat() - southWest.lat();
			for (var i = 0; i < 10; i++) {
			
			}
			
			$j.each(venues, function(i, item){
            	latitudes.push(item.venue_latitude);
  				longitudes.push(item.venue_longitude);
				if (parseFloat(item.venue_latitude) > parseFloat(max_latitude))
					max_latitude = item.venue_latitude;
				if (parseFloat(item.venue_latitude) < parseFloat(min_latitude))
					min_latitude = item.venue_latitude;
				if (parseFloat(item.venue_longitude) > parseFloat(max_longitude))
					max_longitude = item.venue_longitude;
				if (parseFloat(item.venue_longitude) < parseFloat(min_longitude))
					min_longitude = item.venue_longitude; 
				
				// var location = new GLatLng(parseFloat(item.venue_latitude), parseFloat(item.venue_longitude));
				// 			    map.addOverlay(new GMarker(location));	      
				
				var point = new GLatLng(southWest.lat() + latSpan * Math.random(), southWest.lng() + lngSpan * Math.random());
				map.addOverlay(new GMarker(point));                                                       
				});
			
			
			                    
			positionCACCA = new GLatLng(10.9933,45.4387);  
			console.log("loaded? " + map.isLoaded());
			console.log("marker:" + positionCACCA);
			map.addOverlay(new GMarker(positionCACCA), true);	
			console.log("Latitudes: " + latitudes + " MAX: " + max_latitude + " MIN: " + min_latitude);
			
			console.log("Longitudes: " + longitudes +  " MAX: " + max_longitude + " MIN: " + min_longitude);    
			center_x = (max_latitude - min_latitude)/2 + min_latitude;
			center_y = (max_longitude - min_longitude)/2;
			console.log("center: " + center_x + " - " + center_y) + min_longitude; 
            });
		
	 
      }
    }
 

function loadMapScript(key) {
      var script = document.createElement("script");
      script.setAttribute("src", "http://maps.google.com/maps?file=api&v=2.x&key=" + key + "&c&async=2&callback=loadGMap");
      script.setAttribute("type", "text/javascript");
      document.documentElement.firstChild.appendChild(script);
    }