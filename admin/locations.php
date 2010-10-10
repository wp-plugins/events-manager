<?php
/**
 * Looks at the request values, saves/updates and then displays the right menu in the admin
 * @return null
 */
function dbem_locations_page() {  
	//TODO EM_Location is globalized, use it fully here
	global $EM_Location;
	
	//First Delete any locations
	if(isset($_GET['page']) && $_GET['page'] == "locations") {
	  	if(isset($_GET['doaction2']) && $_GET['doaction2'] == "Delete") {
		  	if(isset($_GET['action2']) && $_GET['action2'] == "delete") {
				$locations = $_GET['locations'];
				foreach($locations as $location_id) {
				 	$EM_Location = new EM_Location($location_id);
					$EM_Location->delete();
				}
				?>
				<div id='message' class='updated'><p><?php _e( "Locations Deleted", "dbem" ) ?></p></div>
				<?php
			}
		}
	}
	//Now take further actions
	if(isset($_GET['action']) && $_GET['action'] == "edit") { 
		// edit location  
		dbem_admin_location();
	} else {
		if(isset($_POST['action']) && $_POST['action'] == "editedlocation") {
			// location update required
			$EM_Location->get_post();
			$validation_result = $EM_Location->validate();
			if ( $validation_result ) {
				$EM_Location->save();
				$message = __('The location has been updated.', 'dbem');
				dbem_admin_locations($message);
			} else {
				?>
				<div id='message' class='error '>
					<p>
						<strong><?php _e( "Ach, there's a problem here:", "dbem" ) ?></strong><br /><br /><?php echo implode('<br />', $EM_Location->errors); ?>
					</p>
				</div>
				<?php  
				unset($EM_Location);
				dbem_admin_location($message);
			}
		} elseif(isset($_POST['action']) && $_POST['action'] == "addlocation") {    
			$EM_Location = new EM_Location();
			$EM_Location->get_post();
			$validation_result = $EM_Location->validate();
			if ($validation_result) {   
				$EM_Location->save();
				dbem_admin_locations(__('The location has been added.', 'dbem'));
			} else {
				?>
				<div id='message' class='error '>
					<p>
						<strong><?php _e( "Ach, there's a problem here:", "dbem" ) ?></strong><br /><br /><?php echo implode('<br />', $EM_Location->errors); ?>
					</p>
				</div>
				<?php
				dbem_admin_locations('', true);
			}
		} else {  
			// no action, just a locations list
			dbem_admin_locations();
  		}
	} 
}  

function dbem_admin_locations($message='', $fill_fields = false) {
	global $EM_Location;
	$locations = EM_Locations::get();
	$new_location = (get_class($EM_Location) == 'EM_Location' && $fill_fields ) ? $EM_Location->to_array() : array(); //let's avoid php warning for empty object
	$destination = get_bloginfo('wpurl')."/wp-admin/admin.php";
	?>
		<div class='wrap'>
			<div id='icon-edit' class='icon32'>
				<br/>
			</div>
 	 		<h2><?php _e('Locations', 'dbem'); ?></h2>  

			<?php if($message != "") : ?>
				<div id='message' class='updated fade below-h2'>
					<p><?php echo $message ?></p>
				</div>
			<?php endif; ?>
			 		
			<div id='col-container'>
				<div id='col-right'>
			 	 <div class='col-wrap'>       
				 	 <form id='bookings-filter' method='get' action='<?php echo $destination ?>'>
						<input type='hidden' name='page' value='locations'/>
						<input type='hidden' name='action' value='addlocation'/>
						
						<?php if (count($locations)>0) : ?>
						<table class='widefat'>
							<thead>
								<tr>
									<th class='manage-column column-cb check-column' scope='col'><input type='checkbox' class='select-all' value='1'/></th>
									<th><?php echo __('Name', 'dbem') ?></th>
									<th><?php echo __('Address', 'dbem') ?></th>
									<th><?php echo __('Town', 'dbem') ?></th>                
								</tr> 
							</thead>
							<tfoot>
								<tr>
									<th class='manage-column column-cb check-column' scope='col'><input type='checkbox' class='select-all' value='1'/></th>
									<th><?php echo __('Name', 'dbem') ?></th>
									<th><?php echo __('Address', 'dbem') ?></th>
									<th><?php echo __('Town', 'dbem') ?></th>      
								</tr>             
							</tfoot>
							<tbody>
								<?php foreach ($locations as $location) : ?>	
								<tr>
									<td><input type='checkbox' class ='row-selector' value='<?php echo $location->id ?>' name='locations[]'/></td>
									<td><a href='<?php echo get_bloginfo('wpurl') ?>/wp-admin/admin.php?page=locations&amp;action=edit&amp;location_id=<?php echo $location->id ?>'><?php echo $location->name ?></a></td>
									<td><?php echo $location->address ?></td>
									<td><?php echo $location->location_town ?></td>                         
								</tr>
								<?php endforeach; ?>
							</tbody>

						</table>

						<div class='tablenav'>
							<div class='alignleft actions'>
							<input type='hidden' name='action2' value='delete'/>
						 	<input class='button-secondary action' type='submit' name='doaction2' value='Delete'/>
							<br class='clear'/> 
							</div>
							<br class='clear'/>
						</div>
						<?php else: ?>
							<p><?php echo __('No venues have been inserted yet!', 'dbem') ?></p>
						<?php endif; ?>
						</form>
					</div>
				</div>  <!-- end col-right -->     
				
				<div id='col-left'>
			  		<div class='col-wrap'>
						<div class='form-wrap'> 
							<div id='ajax-response'/>
						  	<h3><?php echo __('Add location', 'dbem') ?></h3>
							<form enctype='multipart/form-data' name='addlocation' id='locationForm' method='post' action='admin.php?page=locations' class='add:the-list: validate'>
								<input type='hidden' name='action' value='addlocation' />
															    <div class='form-field form-required'>
							      <label for='location_name'><?php echo __('Location name', 'dbem') ?></label>
								 	<input id='location-name' name='location_name' id='location_name' type='text' value='<?php echo $new_location['location_name'] ?>' size='40' />
								    <p><?php echo __('The name of the location', 'dbem') ?>.</p>
								 </div>
               
								 <div class='form-field'>
								   <label for='location_address'><?php echo __('Location address', 'dbem') ?></label>
								 	<input id='location-address' name='location_address' id='location_address' type='text' value='<?php echo $new_location['location_address'] ?>' size='40'  />
								    <p><?php echo __('The address of the location', 'dbem') ?>.</p>
								 </div>
               
								 <div class='form-field '>
								   <label for='location_town'><?php echo __('Location town', 'dbem') ?></label>
								 	<input id='location-town' name='location_town' id='location_town' type='text' value='<?php echo $new_location['location_town'] ?>' size='40'  />
								    <p><?php echo __('The town of the location', 'dbem') ?>.</p>
								 </div>   
								
							     <div class='form-field' style='display:none;'>
								   <label for='location_latitude'>LAT</label>
								 	<input id='location-latitude' name='location_latitude' type='text' value='<?php echo $new_location['location_latitude'] ?>' size='40'  />
								 </div>
								 <div class='form-field' style='display:none;'>
								   <label for='location_longitude'>LONG</label>
								 	<input id='location-longitude' name='location_longitude' type='text' value='<?php echo $new_location['location_longitude'] ?>' size='40'  />
								 </div>
								 
								 <?php if ( get_option('dbem_gmap_is_active') ) : ?>	
								 <div class="events-map">
						 		 	<div id='em-map-404' style='width: 450px; vertical-align:middle; text-align: center;'>
										<p><em><?php _e ( 'Location not found', 'dbem' ); ?></em></p>
									</div>
									<div id='em-map' style='width: 450px; height: 350px; display: none;'></div>
							 		<br style='clear:both;' />   
								 </div>
								 <?php endif; ?>
								
								 <div class='form-field'>
								   <label for='location_image'><?php echo __('Location image', 'dbem') ?></label>
								 	<input id='location-image' name='location_image' id='location_image' type='file' size='35' />
								    <p><?php echo __('Select an image to upload', 'dbem') ?>.</p>
								 </div>
								 
									<div id="poststuff">
										<label for='location_description'><?php _e('Location description', 'dbem') ?></label>
										<div class="inside">
											<div id="<?php echo user_can_richedit() ? 'postdivrich' : 'postdiv'; ?>" class="postarea">
												<?php the_editor($new_location['location_description']); ?>
											</div>
											<?php _e('A description of the Location. You may include any kind of info here.', 'dbem') ?>
										</div>
									</div>                 
								<p class='submit'><input type='submit' class='button' name='submit' value='<?php echo __('Add location', 'dbem') ?>' /></p>
							</form>
						</div>
					</div> 
				</div>  <!-- end col-left -->   
			</div> 
		</div>
  	</div>
  	<?php 
}

function dbem_admin_location($message = "") {
	global $EM_Location;
	?>
	<div class='wrap'>
		<div id='icon-edit' class='icon32'>
			<br/>
		</div>
		<h2><?php echo __('Edit location', 'dbem') ?></h2>   
 		
		<?php if($message != "") : ?>
			<div id='message' class='updated fade below-h2' style='background-color: rgb(255, 251, 204);'>
				<p><?php echo $message ?></p>
			</div>
		<?php endif; ?>
		<div id='ajax-response'></div>

		<form enctype='multipart/form-data' name='editcat' id='locationForm' method='post' action='admin.php?page=locations' class='validate'>
		<input type='hidden' name='action' value='editedlocation' />
		<input type='hidden' name='location_id' value='<?php echo $EM_Location->id ?>'/>
							<table class='form-table'>
				<tr class='form-field form-required'>
					<th scope='row' valign='top'><label for='location_name'><?php echo __('Location name', 'dbem') ?></label></th>
					<td><input name='location_name' id='location-name' type='text' value='<?php echo htmlspecialchars($EM_Location->name, ENT_QUOTES); ?>' size='40'  /><br />
		           <?php echo __('The name of the location', 'dbem') ?></td>
				</tr>

				<tr class='form-field'>
					<th scope='row' valign='top'><label for='location_address'><?php echo __('Location address', 'dbem') ?></label></th>
					<td><input name='location_address' id='location-address' type='text' value='<?php echo htmlspecialchars($EM_Location->address, ENT_QUOTES); ?>' size='40' /><br />
		            <?php echo __('The address of the location', 'dbem') ?>.</td>

				</tr>
				
				<tr class='form-field'>
					<th scope='row' valign='top'> <label for='location_town'><?php echo __('Location town', 'dbem') ?></label></th>
					<td><input name='location_town' id='location-town' type='text' value='<?php echo htmlspecialchars($EM_Location->town, ENT_QUOTES); ?>' size='40' /><br />
		            <?php echo __('The town where the location is located', 'dbem') ?>.</td>

				</tr>
			    
				 <tr style='display:none;'>
				  <td>Coordinates</td>
					<td><input id='location-latitude' name='location_latitude' id='location_latitude' type='text' value='<?php echo $EM_Location->latitude ?>' size='15'  />
					<input id='location-longitude' name='location_longitude' id='location_longitude' type='text' value='<?php echo $EM_Location->longitude ?>' size='15'  /></td>
				 </tr>
				 
				 <?php 	if (get_option('dbem_gmap_is_active')) { ?>
				<tr>
			 		<th scope='row' valign='top'><label for='location_map'><?php echo __('Location map', 'dbem') ?></label></th>
					<td>
				 		 	<div id='em-map-404' style='width: 450px; height: 350px; vertical-align:middle; text-align: center;'>
								<p><em><?php _e ( 'Location not found', 'dbem' ); ?></em></p>
							</div>
							<div id='em-map' style='width: 450px; height: 350px; display: none;'></div>
	 				</td>
	 			</tr>
	 			<?php
					}
				?>
				<tr class='form-field'>
					<th scope='row' valign='top'><label for='location_description'><?php _e('Location description', 'dbem') ?></label></th>
					<td>
						<div class="inside">
							<div id="<?php echo user_can_richedit() ? 'postdivrich' : 'postdiv'; ?>" class="postarea">
								<?php the_editor($EM_Location->description); ?>
							</div>
							<?php _e('A description of the Location. You may include any kind of info here.', 'dbem') ?>
						</div>
					</td>
				</tr>
				<tr class='form-field'>
					<th scope='row' valign='top'><label for='location_picture'><?php echo __('Location image', 'dbem') ?></label></th>
					<td>
						<?php if ($EM_Location->image_url != '') : ?> 
							<img src='<?php echo $EM_Location->image_url; ?>' alt='<?php echo $EM_Location->name ?>'/>
						<?php else : ?> 
							<?php echo __('No image uploaded for this location yet', 'debm') ?>
						<?php endif; ?>
					</td>
				</tr>
				<tr>
					<th scope='row' valign='top'><label for='location_image'><?php echo __('Upload/change picture', 'dbem') ?></label></th>
					<td><input id='location-image' name='location_image' id='location_image' type='file' size='40' /></td>
				</tr>
			</table>
			<p class='submit'><input type='submit' class='button-primary' name='submit' value='<?php echo __('Update location', 'dbem') ?>' /></p>
		</form>
	</div>
	<?php
}

?>