<?php
/**
 * Looks at the request values, saves/updates and then displays the right menu in the admin
 * @return null
 */
function em_admin_locations_page() {  
	//TODO EM_Location is globalized, use it fully here
	global $EM_Location;
	//Take actions
	if( !empty($_REQUEST['action']) || !empty($_REQUEST['location_id']) ){
		if( $_REQUEST['action'] == "edit" || $_REQUEST['action'] == "add" ) { 
			//edit/add location  
			em_admin_location();
		} elseif( $_REQUEST['action'] == "delete" ){
			//delelte location
			$locations = $_REQUEST['locations'];
			foreach($locations as $location_id) {
			 	$EM_Location = new EM_Location($location_id);
				$EM_Location->delete();
			}
			em_admin_locations(__('Locations Deleted', "dbem" ));
		} elseif( $_REQUEST['action'] == "save") {
			// save (add/update) location
			if( empty($EM_Location) || !is_object($EM_Location) ){
				$EM_Location = new EM_Location(); //blank location
				$success_message = __('The location has been added.', 'dbem');
			}else{
				$success_message = __('The location has been updated.', 'dbem');
			}
			$EM_Location->get_post();
			$validation_result = $EM_Location->validate();
			if ( $validation_result ) {
				$EM_Location->save(); //FIXME better handling of db write fails when saving location
				em_admin_locations($success_message);
			} else {
				?>
				<div id='message' class='error '>
					<p>
						<strong><?php _e( "Ach, there's a problem here:", "dbem" ) ?></strong><br /><br /><?php echo implode('<br />', $EM_Location->errors); ?>
					</p>
				</div>
				<?php  
				unset($EM_Location);
				em_admin_location();
			}
		}
	} else {  
		// no action, just a locations list
		em_admin_locations();
  	}
}  

function em_admin_locations($message='', $fill_fields = false) {
	$limit = ( !empty($_REQUEST['limit']) ) ? $_REQUEST['limit'] : 20;//Default limit
	$page = ( !empty($_REQUEST['pno']) ) ? $_REQUEST['pno']:1;
	$offset = ( $page > 1 ) ? ($page-1)*$limit : 0;
	$locations = EM_Locations::get();
	$locations_count = count($locations);
	?>
		<div class='wrap'>
			<div id='icon-edit' class='icon32'>
				<br/>
			</div>
 	 		<h2>
 	 			<?php _e('Locations', 'dbem'); ?>
 	 			<a href="admin.php?page=events-manager-locations&action=add" class="button add-new-h2"><?php _e('Add New') ?></a>
 	 		</h2>  

			<?php if($message != "") : ?>
				<div id='message' class='updated fade below-h2'>
					<p><?php echo $message ?></p>
				</div>
			<?php endif; ?>  
			  
		 	 <form id='bookings-filter' method='post' action=''>
				<input type='hidden' name='page' value='locations'/>
				<input type='hidden' name='limit' value='<?php echo $limit ?>' />	
				<input type='hidden' name='p' value='<?php echo $page ?>' />								
				<?php if ( $locations_count > 0 ) : ?>
				<div class='tablenav'>					
					<div class="alignleft actions">
						<select name="action">
							<option value="" selected="selected"><?php _e ( 'Bulk Actions' ); ?></option>
							<option value="delete"><?php _e ( 'Delete selected','dbem' ); ?></option>
						</select> 
						<input type="submit" value="<?php _e ( 'Apply' ); ?>" id="doaction2" class="button-secondary action" /> 
						<?php 
							//Pagination (if needed/requested)
							if( $locations_count >= $limit ){
								//Show the pagination links (unless there's less than 10 events
								$page_link_template = preg_replace('/(&|\?)p=\d+/i','',$_SERVER['REQUEST_URI']);
								$page_link_template = em_add_get_params($page_link_template, array('pno'=>'%PAGE%'));
								$locations_nav = em_paginate( $page_link_template, $locations_count, $limit, $page);
								echo $locations_nav;
							}
						?>
					</div>
				</div>
				<table class='widefat'>
					<thead>
						<tr>
							<th class='manage-column column-cb check-column' scope='col'><input type='checkbox' class='select-all' value='1'/></th>
							<th><?php _e('Name', 'dbem') ?></th>
							<th><?php _e('Address', 'dbem') ?></th>
							<th><?php _e('Town', 'dbem') ?></th>                
						</tr> 
					</thead>
					<tfoot>
						<tr>
							<th class='manage-column column-cb check-column' scope='col'><input type='checkbox' class='select-all' value='1'/></th>
							<th><?php _e('Name', 'dbem') ?></th>
							<th><?php _e('Address', 'dbem') ?></th>
							<th><?php _e('Town', 'dbem') ?></th>      
						</tr>             
					</tfoot>
					<tbody>
						<?php $i = 1; ?>
						<?php foreach ($locations as $EM_Location) : ?>	
							<?php if( $i >= $offset && $i <= $offset+$limit ): ?>
								<tr>
									<td><input type='checkbox' class ='row-selector' value='<?php echo $EM_Location->id ?>' name='locations[]'/></td>
									<td><a href='admin.php?page=events-manager-locations&amp;action=edit&amp;location_id=<?php echo $EM_Location->id ?>'><?php echo $EM_Location->name ?></a></td>
									<td><?php echo $EM_Location->address ?></td>
									<td><?php echo $EM_Location->town ?></td>                         
								</tr>
							<?php endif; ?>
							<?php $i++; ?> 
						<?php endforeach; ?>
					</tbody>
				</table>
				<?php else: ?>
				<p><?php _e('No venues have been inserted yet!', 'dbem') ?></p>
				<?php endif; ?>
			</form>
		</div>
  	<?php 
}

function em_admin_location($message = "") {
	global $EM_Location, $EM_Event;
	//check that user can access this page
	if( is_object($EM_Location) && !$EM_Location->can_manage() ){
		?>
		<div class="wrap"><h2><?php _e('Unauthorized Access','dbem'); ?></h2><p><?php _e('You do not have the rights to manage this location.','dbem'); ?></p></div>
		<?php
		return false;
	}	
	if( empty($EM_Location) || !is_object($EM_Location) ){
		$title = __('Add location', 'dbem');
		$EM_Location = new EM_Location();
	}else{
		$title = __('Edit location', 'dbem');
	}
	?>
	<form enctype='multipart/form-data' name='editcat' id='locationForm' method='post' action='admin.php?page=events-manager-locations' class='validate'>
		<input type='hidden' name='action' value='save' />
		<input type='hidden' name='location_id' value='<?php echo $EM_Location->id ?>'/>
		<div class='wrap'>
			<div id='icon-edit' class='icon32'>
				<br/>
			</div>
			<h2><?php echo $title ?></h2>   
	 		
			<?php if($message != "") : ?>
				<div id='message' class='updated fade below-h2' style='background-color: rgb(255, 251, 204);'>
					<p><?php echo $message ?></p>
				</div>
			<?php endif; ?>
			<div id='ajax-response'></div>
			
			<div id="poststuff" class="metabox-holder">
				<div id="post-body">
					<div id="post-body-content">
						<div id="location_name" class="stuffbox">
							<h3>
								<?php _e ( 'Location Name', 'dbem' ); ?>
							</h3>
							<div class="inside">
								<input name='location_name' id='location-name' type='text' value='<?php echo htmlspecialchars($EM_Location->name, ENT_QUOTES); ?>' size='40'  />
								<br />
								<?php _e('The name of the location', 'dbem') ?>
							</div>
						</div>
						<div id="location_coordinates" class="stuffbox" style='display: none;'>
							<h3>
								<?php _e ( 'Coordinates', 'dbem' ); ?>
							</h3>
							<div class="inside">
								<input id='location-latitude' name='location_latitude' type='text' value='<?php echo $EM_Location->latitude; ?>' size='15' />
								-
								<input id='location-longitude' name='location_longitude' type='text' value='<?php echo $EM_Location->longitude; ?>' size='15' />
							</div>
						</div>
						<div id="location_info" class="stuffbox">
							<h3>
								<?php _e ( 'Location', 'dbem' ); ?>
							</h3>
							<div class="inside">
								<table id="dbem-location-data">     
									<tr>
										<td style="padding-right:20px">
											<table>
												<tr>
													<th><?php _e ( 'Address:' )?>&nbsp;</th>
													<td>
														<input id="location-address" type="text" name="location_address" value="<?php echo htmlspecialchars($EM_Location->address, ENT_QUOTES); ; ?>" />
														<p><?php _e ( 'The address of the location where the event takes place. Example: 21, Dominick Street', 'dbem' )?></p>
													</td>
												</tr>
												<tr>
													<th><?php _e ( 'Town:' )?>&nbsp;</th>
													<td>
														<input id="location-town" type="text" name="location_town" value="<?php echo htmlspecialchars($EM_Location->town, ENT_QUOTES); ?>" />
														<p><?php _e ( 'The town where the location is located. If you\'re using the Google Map integration and want to avoid geotagging ambiguities include the country in the town field. Example: Verona, Italy.', 'dbem' )?></p>
													</td>
												</tr>
											</table>
										</td>
										<?php if ( get_option ( 'dbem_gmap_is_active' ) ) : ?>
										<td width="400">
											<div id='em-map-404' style='width: 400px; vertical-align:middle; text-align: center;'>
												<p><em><?php _e ( 'Location not found', 'dbem' ); ?></em></p>
											</div>
											<div id='em-map' style='width: 400px; height: 300px; display: none;'></div>
										</td>
										<?php endif; ?>
									</tr>
								</table>
							</div>
						</div>
								
						<div id="location_description" class="postbox">
							<h3>
								<?php _e ( 'Details', 'dbem' ); ?>
							</h3>
							<div class="inside">
								<div id="<?php echo user_can_richedit() ? 'postdivrich' : 'postdiv'; ?>" class="postarea">
									<?php the_editor($EM_Location->description ); ?>
								</div>
								<br />
								<?php _e ( 'Details about the location', 'dbem' )?>
							</div>
						</div>
									
						<div id="location_description" class="stuffbox">
							<h3>
								<?php _e ( 'Location image', 'dbem' ); ?>
							</h3>
							<div class="inside" style="padding:10px;">
									<?php if ($EM_Location->image_url != '') : ?> 
										<img src='<?php echo $EM_Location->image_url; ?>' alt='<?php echo $EM_Location->name ?>'/>
									<?php else : ?> 
										<?php _e('No image uploaded for this location yet', 'debm') ?>
									<?php endif; ?>
									<br /><br />
									<label for='location_image'><?php _e('Upload/change picture', 'dbem') ?></label> <input id='location-image' name='location_image' id='location_image' type='file' size='40' />
							</div>
						</div>
					</div>
				</div>
			</div>
			<p class='submit'><input type='submit' class='button-primary' name='submit' value='<?php _e('Update location', 'dbem') ?>' /></p>
		</div>
	</form>
	<?php
}

?>