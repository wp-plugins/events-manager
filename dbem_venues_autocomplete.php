<?php 
dbem_log('venues script loaded');
function dbem_venues_autocomplete() {      
	if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'edit_event') { 
	   ?>
	<link rel="stylesheet" href="../wp-content/plugins/events-manager/jquery-autocomplete/jquery.autocomplete.css" type="text/css"/>
	 
	 
	<script src="../wp-content/plugins/events-manager/jquery-autocomplete/lib/jquery.bgiframe.min.js" type="text/javascript"></script>
	<script src="../wp-content/plugins/events-manager/jquery-autocomplete/lib/jquery.ajaxQueue.js" type="text/javascript"></script> 
	                                                                                                         
	<script src="../wp-content/plugins/events-manager/jquery-autocomplete/jquery.autocomplete.js" type="text/javascript"></script>
	
	<script type="text/javascript">
	//<![CDATA[
		$j=jQuery.noConflict();
		 	

		 $j(document).ready(function() { 
						$j("#venue-input").autocomplete("../wp-content/plugins/events-manager/venues-search.php", {
							width: 260,
							selectFirst: false, 
							formatResult: function(data, value) {
								return value.split("<br/>")[0];
							}
						});
						$j('#venue-input').result(function(event,data,formatted) {
							var venueData = data[0].split("<br/>");     
							var locationValue = venueData[1].replace("<small>", "").replace("</small>", "").split(' <span>-</span> ');
							 
							$j('#address-input').val(locationValue[0]);
							$j('#town-input').val(locationValue[1]);
						});
						
			});	
		//]]> 
		
	</script>
	
	<?php
		
	}
}
add_action ('admin_head', 'dbem_venues_autocomplete');  

function dbem_cache_venue($event){
	dbem_log($event); 
	$related_venue = dbem_get_venue_by_name($event['event_venue']);  
	if (!$related_venue) {
		dbem_insert_venue_from_event($event);
		return;
	} 
	if ($related_venue->venue_address != $event['event_address'] || $related_venue->venue_town != $event['event_town']  ) {
		dbem_insert_venue_from_event($event);
	}      
	
}     

function dbem_get_venue_by_name($name) {
 	global $wpdb;	
	$sql = "SELECT venue_id, 
			   	venue_name, 
			  	venue_address,
			  	venue_town
				FROM ".$wpdb->prefix.VENUES_TBNAME.  
			  " WHERE venue_name = '$name'";   
	     
	dbem_log($sql);
	$event = $wpdb->get_row($sql);	
	   
	return $event;
}   

function dbem_insert_venue_from_event($event) {
	global $wpdb;	
	$table_name = $wpdb->prefix.VENUES_TBNAME;
  $wpdb->query("INSERT INTO ".$table_name." (venue_name, venue_address, venue_town)
					VALUES ('".$event['event_venue']."', '".$event['event_address']."','".$event['event_town']."')");
	
}

?>