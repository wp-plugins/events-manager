<?php
/*
 * Default Location Single Template
 * This template could be used to show a single location
 * You can override the default display settings pages by removing 'example-' at the start of this file name and moving it to yourthemefolder/plugins/events-manager/templates/
 * 
 * This file is called within an EM_Location object, so $this corresponds to the example-event-single.php object we're displaying
 * 
 */ 

//this is one way, another could be to just directly use $this to get event data
ob_start();
?>
#_LOCATIONIMAGE
<p>
	#_LOCATIONNAME, #_LOCATIONADDRESS, #_LOCATIONTOWN<br/>
	ID - #_LOCATIONID<br />
	Links - #_LOCATIONPAGEURL or #_LOCATIONURL or #_LOCATIONLINK
</p>
#_LOCATIONEXCERPT
<br />
#_LOCATIONMAP
<br />
<h3>Upcoming Events at #_LOCATION</h3>
#_LOCATIONNEXTEVENTS			
<br />
<h3>Previous Events at #_LOCATION</h3>
#_LOCATIONPASTEVENTS			
<br />
<h3>All Events at #_LOCATION</h3>
#_LOCATIONALLEVENTS
<?php 
$format = ob_get_clean();
//now we just grab the format and output! we could throw in some conditions above and let EM handle the formatting
echo $this->output($format);