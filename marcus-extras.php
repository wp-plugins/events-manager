<?php
define('DBEM_CATEGORIES_TBNAME', 'dbem_categories');

//Add the categories table
function dbem_create_categories_table() {
	
	global  $wpdb, $user_level;
	$table_name = $wpdb->prefix.DBEM_CATEGORIES_TBNAME;

	if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
		// Creating the events table
		$sql = "CREATE TABLE ".$table_name." (
			  category_id int(11) NOT NULL auto_increment,
			  category_name tinytext NOT NULL,
			  PRIMARY KEY  (category_id)
			);";
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);
	}
}

// Create the Manage Events and the Options submenus 
add_action('admin_menu','dbem_add_to_events_submenu', 100);     
function dbem_add_to_events_submenu () {
	if(function_exists('add_submenu_page')) {
		$file = dirname(__FILE__).DIRECTORY_SEPARATOR."events-manager.php";
		add_submenu_page($file, __('Event Categories','dbem'),__('Categories','dbem'), SETTING_CAPABILITY, "events-manager-categories", dbem_categories_subpanel);
  	}
}


function dbem_duplicate_event($event_ID) {
	global $wpdb, $EZSQL_ERROR;
	//First, duplicate.
	$event_table_name = $wpdb->prefix . EVENTS_TBNAME;
	$eventArray = $wpdb->get_row("SELECT * FROM {$event_table_name} WHERE event_id={$event_ID}", ARRAY_A );
	unset($eventArray['event_id']);
	$result = $wpdb->insert($event_table_name, $eventArray);
	if($result !== false){
		//Get the ID of the new item
		$event_ID = $wpdb->insert_id;
		$event = dbem_get_event ( $event_ID );
		$event['event_id'] = $event_ID;
		//Now we edit the duplicated item
		$title = __ ( "Edit Event", 'dbem' ) . " '" . $event ['event_name'] . "'";
		echo "<div id='message' class='updated below-h2'>You are now editing the duplicated event.</div>";
		dbem_event_form ( $event, $title, $event_ID );
	}else{
		echo "<div class='error'><p>There was an error duplicating the event. Try again maybe? Here are the errors:</p>";
		foreach($EZSQL_ERROR as $errorArray){
			echo "<p>{$errorArray['error_str']}</p>";
		}	
		echo "</div>";
		$scope = $_GET ['scope'];
		$offset = $_GET ['offset'];
		$order = $_GET ['order'];
		$limit = 20;
		$events = dbem_get_events ( $limit, $scope, $order, $offset );
		dbem_events_table ( $events, $limit, $title );
	}
}

function dbem_categories_subpanel() {      
	global $wpdb;
	
	if(isset($_GET['action']) && $_GET['action'] == "edit") { 
		// edit category  
		dbem_categories_edit_layout();
	} else {
		// Insert/Update/Delete Record
		$categories_table = $wpdb->prefix.DBEM_CATEGORIES_TBNAME;
		if( isset($_POST['action']) && $_POST['action'] == "edit" ) {
			// category update required  
			$category = array();
			$category['category_name'] = $_POST['category_name'];
			$validation_result = $wpdb->update( $categories_table, $category, array('category_id' => $_POST['category_ID']) );
		} elseif( isset($_POST['action']) && $_POST['action'] == "add" ) {
			// Add a new category
			$category = array();
			$category['category_name'] = $_POST['category_name'];
			$validation_result = $wpdb->insert($categories_table, $category);
		} elseif( isset($_POST['action']) && $_POST['action'] == "delete" ) {
			// Delete category or multiple
			$categories = $_POST['categories'];
			if(is_array($categories)){
				//Make sure the array is only numbers
				foreach ($categories as $cat_id){
					if(is_numeric($cat_id)){
						$cats[] = "category_id = $cat_id";
					}
				}
				//Run the query if we have an array of category ids
				if(count($cats > 0)){
					$validation_result = $wpdb->query( "DELETE FROM $categories_table WHERE ". implode(" OR ", $cats) );
				}else{
					$validation_result = false;
					$message = "Couldn't delete the categories. Incorrect category IDs supplied. Please try agian.";
				}
			}
		}
		//die(print_r($_POST));
		if ( is_numeric($validation_result) ) {
			$message = (isset($message)) ? $message : __("Successfully {$_POST['action']}ed category", "dbem");
			dbem_categories_table_layout($message);
		} elseif ( $validation_result === false ) {
			$message = (isset($message)) ? $message : __("There was a problem {$_POST['action']}ing your category, please try again.");						   
			dbem_categories_table_layout($message);
		} else {
			// no action, just a categories list
			dbem_categories_table_layout();	
		}
	}
} 

function dbem_categories_table_layout($message = "") {
	$categories = dbem_get_categories();
	$destination = get_bloginfo('url')."/wp-admin/admin.php"; 
	$table = "
		<div class='wrap nosubsub'>\n
			<div id='icon-edit' class='icon32'>
				<br/>
			</div>
 	 		<h2>".__('Categories', 'dbem')."</h2>\n ";   
	 		
			if($message != "") {
				$table .= "
				<div id='message' class='updated fade below-h2' style='background-color: rgb(255, 251, 204);'>
					<p>$message</p>
				</div>";
			}
			
			$table .= "
			<div id='col-container'>\n
			
				<?-- begin col-right -->\n   
				<div id='col-right'>\n
			 	 <div class='col-wrap'>\n       
				 	 <form id='bookings-filter' method='post' action='".get_bloginfo('url')."/wp-admin/admin.php?page=events-manager-categories'>\n
						<input type='hidden' name='action' value='delete'/>\n";
						
						if (count($categories)>0) {
							$table .= "<table class='widefat'>\n
								<thead>\n
									<tr>\n
										<th class='manage-column column-cb check-column' scope='col'><input type='checkbox' class='select-all' value='1'/></th>\n
										<th>".__('ID', 'dbem')."</th>\n
										<th>".__('Name', 'dbem')."</th>\n
									</tr>\n 
								</thead>\n
								<tfoot>\n
									<tr>\n
										<th class='manage-column column-cb check-column' scope='col'><input type='checkbox' class='select-all' value='1'/></th>\n
										<th>".__('ID', 'dbem')."</th>\n
										<th>".__('Name', 'dbem')."</th>\n
									</tr>\n             
								</tfoot>\n
								<tbody>\n";
							foreach ($categories as $this_category) {
								$table .= "		
									<tr>\n
									<td><input type='checkbox' class ='row-selector' value='".$this_category['category_id']."' name='categories[]'/></td>\n
									<td><a href='".get_bloginfo('url')."/wp-admin/admin.php?page=events-manager-categories&amp;action=edit&amp;category_ID=".$this_category['category_id']."'>".$this_category['category_id']."</a></td>\n
									<td><a href='".get_bloginfo('url')."/wp-admin/admin.php?page=events-manager-categories&amp;action=edit&amp;category_ID=".$this_category['category_id']."'>".$this_category['category_name']."</a></td>\n
									</tr>\n
								";
							}
							$table .= "
								</tbody>\n
	
							</table>\n
	
							<div class='tablenav'>\n
								<div class='alignleft actions'>\n
							 	<input class='button-secondary action' type='submit' name='doaction2' value='Delete'/>\n
								<br class='clear'/>\n 
								</div>\n
								<br class='clear'/>\n
							</div>\n";
						} else {
								$table .= "<p>".__('No categories have been inserted yet!', 'dbem');
						}
						 $table .= "
						</form>\n
					</div>\n
				</div> 
				<?-- end col-right -->\n     
				
				<?-- begin col-left -->
				<div id='col-left'>\n
			  	<div class='col-wrap'>\n
						<div class='form-wrap'>\n 
							<div id='ajax-response'/>
					  	<h3>".__('Add category', 'dbem')."</h3>\n
							 <form name='add' id='add' method='post' action='admin.php?page=events-manager-categories' class='add:the-list: validate'>\n
							 	<input type='hidden' name='action' value='add' />\n
							    <div class='form-field form-required'>\n
							    	<label for='category_name'>".__('Category name', 'dbem')."</label>\n
								 	<input id='category-name' name='category_name' id='category_name' type='text' value='".$new_category['category_name']."' size='40' />\n
								    <p>".__('The name of the category', 'dbem').".</p>\n
								 </div>\n
								 <p class='submit'><input type='submit' class='button' name='submit' value='".__('Add category', 'dbem')."' /></p>\n
							 </form>\n   

					  </div>\n
					</div>\n 
				</div>  
				<?-- end col-left -->\n   
			</div>\n 
  	</div>\n";                                                

	echo $table;  
}


function dbem_categories_edit_layout($message = "") {
	$category_id = $_GET['category_ID'];
	$category = dbem_get_category($category_id);
	$layout = "
	<div class='wrap'>
		<div id='icon-edit' class='icon32'>
			<br/>
		</div>
			
		<h2>".__('Edit category', 'dbem')."</h2>";   
 		
		if($message != "") {
			$layout .= "
		<div id='message' class='updated fade below-h2' style='background-color: rgb(255, 251, 204);'>
			<p>$message</p>
		</div>";
		}
		$layout .= "
		<div id='ajax-response'></div>

		<form name='editcat' id='editcat' method='post' action='admin.php?page=events-manager-categories' class='validate'>
		<input type='hidden' name='action' value='edit' />
		<input type='hidden' name='category_ID' value='".$category['category_id']."'/>";
		
		$layout .= "
			<table class='form-table'>
				<tr class='form-field form-required'>
					<th scope='row' valign='top'><label for='category_name'>".__('Category name', 'dbem')."</label></th>
					<td><input name='category_name' id='category-name' type='text' value='".$category['category_name']."' size='40'  /><br />
		           ".__('The name of the category', 'dbem')."</td>
				</tr>
			</table>
		<p class='submit'><input type='submit' class='button-primary' name='submit' value='".__('Update category', 'dbem')."' /></p>
		</form>
		   
   	
	</div>
			
	";  
	echo $layout;
}

function dbem_get_categories(){
	global $wpdb;
	$categories_table = $wpdb->prefix.DBEM_CATEGORIES_TBNAME; 
	return $wpdb->get_results("SELECT * FROM $categories_table", ARRAY_A);
}

function dbem_get_category($category_id) { 
	global $wpdb;
	$categories_table = $wpdb->prefix.DBEM_CATEGORIES_TBNAME; 
	$sql = "SELECT * FROM $categories_table WHERE category_id ='$category_id'";   
 	$category = $wpdb->get_row($sql, ARRAY_A);
	return $category;
}

function dbem_get_event_category($event_id) { 
	global $wpdb;
	$event_table = $wpdb->prefix.EVENTS_TBNAME; 
	$sql = "SELECT category_id, category_name FROM $event_table LEFT JOIN ".$wpdb->prefix.DBEM_CATEGORIES_TBNAME." ON category_id=event_category_id WHERE event_id ='$event_id'";
 	$category = $wpdb->get_row($sql, ARRAY_A);
	return $category;
}

?>