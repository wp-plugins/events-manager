<?php 
function dbem_locations_autocomplete() {      
	if ((isset($_REQUEST['action']) && $_REQUEST['action'] == 'edit_event') || (isset($_REQUEST['page']) && $_REQUEST['page'] == 'new_event')) { 
		?>
		<link rel="stylesheet" href="../wp-content/plugins/events-manager/jquery-autocomplete/jquery.autocomplete.css" type="text/css"/>
    

		<script src="../wp-content/plugins/events-manager/jquery-autocomplete/lib/jquery.bgiframe.min.js" type="text/javascript"></script>
		<script src="../wp-content/plugins/events-manager/jquery-autocomplete/lib/jquery.ajaxQueue.js" type="text/javascript"></script> 

		<script src="../wp-content/plugins/events-manager/jquery-autocomplete/jquery.autocomplete.min.js" type="text/javascript"></script>

		<script type="text/javascript">
		//<![CDATA[
		$j=jQuery.noConflict();


		$j(document).ready(function() {
			var gmap_enabled = <?php echo get_option('dbem_gmap_is_active'); ?>; 
		 
			$j("input#location-name").autocomplete("../wp-content/plugins/events-manager/locations-search.php", {
				width: 260,
				selectFirst: false,
				formatItem: function(row) {
					item = eval("(" + row + ")");
					return item.name+'<br/><small>'+item.address+' - '+item.town+ '</small>';
				},
				formatResult: function(row) {
					item = eval("(" + row + ")");
					return item.name;
				} 

			});
			$j('input#location-name').result(function(event,data,formatted) {       
				item = eval("(" + data + ")"); 
				$j('input#location-address').val(item.address);
				$j('input#location-town').val(item.town);
				if(gmap_enabled) {   
					eventLocation = $j("input#location-name").val(); 
			    eventTown = $j("input#location-town").val(); 
					eventAddress = $j("input#location-address").val();
					
					loadMap(eventLocation, eventTown, eventAddress)
				} 
			});

		});	
		//]]> 

		</script>

		<?php

	}
}
add_action ('admin_head', 'dbem_locations_autocomplete');  

function dbem_cache_location($event){
	$related_location = dbem_get_location_by_name($event['location_name']);  
	if (!$related_location) {
		dbem_insert_location_from_event($event);
		return;
	} 
	if ($related_location->location_address != $event['location_address'] || $related_location->location_town != $event['location_town']  ) {
		dbem_insert_location_from_event($event);
	}      

}     

function dbem_get_location_by_name($name) {
	global $wpdb;	
	$sql = "SELECT location_id, 
	location_name, 
	location_address,
	location_town
	FROM ".$wpdb->prefix.LOCATIONS_TBNAME.  
	" WHERE location_name = '$name'";   
	$event = $wpdb->get_row($sql);	

	return $event;
}   

function dbem_insert_location_from_event($event) {
	global $wpdb;	
	$table_name = $wpdb->prefix.LOCATIONS_TBNAME;
	$wpdb->query("INSERT INTO ".$table_name." (location_name, location_address, location_town)
	VALUES ('".$event['location_name']."', '".$event['location_address']."','".$event['location_town']."')");

}

?>