<?php 
function dbem_venues_autocomplete() {      
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
		 
			$j("input#venue-name").autocomplete("../wp-content/plugins/events-manager/venues-search.php", {
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
			$j('input#venue-name').result(function(event,data,formatted) {       
				item = eval("(" + data + ")"); 
				$j('input#venue-address').val(item.address);
				$j('input#venue-town').val(item.town);
				if(gmap_enabled) {   
					eventVenue = $j("input#venue-name").val(); 
			    eventTown = $j("input#venue-town").val(); 
					eventAddress = $j("input#venue-address").val();
					
					loadMap(eventVenue, eventTown, eventAddress)
				} 
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
	$related_venue = dbem_get_venue_by_name($event['venue_name']);  
	if (!$related_venue) {
		dbem_insert_venue_from_event($event);
		return;
	} 
	if ($related_venue->venue_address != $event['venue_address'] || $related_venue->venue_town != $event['venue_town']  ) {
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
	VALUES ('".$event['venue_name']."', '".$event['venue_address']."','".$event['venue_town']."')");

}

?>