<?php
function em_categories_subpanel() {      
	global $wpdb;
	
	if(isset($_GET['action']) && $_GET['action'] == "edit") { 
		// edit category  
		em_categories_edit_layout();
	} else {
		// Insert/Update/Delete Record
		$categories_table = $wpdb->prefix.EM_CATEGORIES_TABLE;
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
				//Run the query if we have an array of category ids with at least one value
				if( EM_Object::array_is_numeric($categories) ){
					$validation_result = $wpdb->query( "DELETE FROM $categories_table WHERE category_id =". implode(" OR category_id =", $categories) );
				}else{
					$validation_result = false;
					$message = "Couldn't delete the categories. Incorrect category IDs supplied. Please try agian.";
				}
			}
		}
		//die(print_r($_POST));
		if ( isset($validation_result) && is_numeric($validation_result) ) {
			$message = (isset($message)) ? $message : __("Successfully {$_POST['action']}ed category", "dbem");
			em_categories_table_layout($message);
		} elseif ( isset($validation_result) && $validation_result === false ) {
			$message = (isset($message)) ? $message : __("There was a problem {$_POST['action']}ing your category, please try again.");						   
			em_categories_table_layout($message);
		} else {
			// no action, just a categories list
			em_categories_table_layout();	
		}
	}
} 

function em_categories_table_layout($message = "") {
	$categories = EM_Category::get();
	$destination = get_bloginfo('url')."/wp-admin/admin.php"; 
	?>
	<div class='wrap nosubsub'>
		<div id='icon-edit' class='icon32'>
			<br/>
		</div>
  		<h2><?php echo __('Categories', 'dbem') ?></h2>
	 		
		<?php if($message != "") : ?>
			<div id='message' class='updated fade below-h2' style='background-color: rgb(255, 251, 204);'>
				<p><?php echo $message ?></p>
			</div>
		<?php endif; ?>
		
		<div id='col-container'>
			<!-- begin col-right -->   
			<div id='col-right'>
			 	<div class='col-wrap'>       
				 	 <form id='bookings-filter' method='post' action='<?php echo get_bloginfo('wpurl') ?>/wp-admin/admin.php?page=events-manager-categories'>
						<input type='hidden' name='action' value='delete'/>
						<?php if (count($categories)>0) : ?>
							<table class='widefat'>
								<thead>
									<tr>
										<th class='manage-column column-cb check-column' scope='col'><input type='checkbox' class='select-all' value='1'/></th>
										<th><?php echo __('ID', 'dbem') ?></th>
										<th><?php echo __('Name', 'dbem') ?></th>
									</tr> 
								</thead>
								<tfoot>
									<tr>
										<th class='manage-column column-cb check-column' scope='col'><input type='checkbox' class='select-all' value='1'/></th>
										<th><?php echo __('ID', 'dbem') ?></th>
										<th><?php echo __('Name', 'dbem') ?></th>
									</tr>             
								</tfoot>
								<tbody>
									<?php foreach ($categories as $this_category) : ?>
									<tr>
										<td><input type='checkbox' class ='row-selector' value='<?php echo $this_category['category_id'] ?>' name='categories[]'/></td>
										<td><a href='<?php echo get_bloginfo('wpurl') ?>/wp-admin/admin.php?page=events-manager-categories&amp;action=edit&amp;category_ID=<?php echo $this_category['category_id'] ?>'><?php echo htmlspecialchars($this_category['category_id'], ENT_QUOTES); ?></a></td>
										<td><a href='<?php echo get_bloginfo('wpurl') ?>/wp-admin/admin.php?page=events-manager-categories&amp;action=edit&amp;category_ID=<?php echo $this_category['category_id'] ?>'><?php echo htmlspecialchars($this_category['category_name'], ENT_QUOTES); ?></a></td>
									</tr>
									<?php endforeach; ?>
								</tbody>
	
							</table>
	
							<div class='tablenav'>
								<div class='alignleft actions'>
							 	<input class='button-secondary action' type='submit' name='doaction2' value='Delete'/>
								<br class='clear'/> 
								</div>
								<br class='clear'/>
							</div>
						<?php else: ?>
							<p><?php echo __('No categories have been inserted yet!', 'dbem'); ?></p>
						<?php endif; ?>
					</form>
				</div>
			</div>
			<!-- end col-right -->     
			
			<!-- begin col-left -->
			<div id='col-left'>
		  		<div class='col-wrap'>
					<div class='form-wrap'> 
						<div id='ajax-response'>
					  		<h3><?php echo __('Add category', 'dbem') ?></h3>
							<form name='add' id='add' method='post' action='admin.php?page=events-manager-categories' class='add:the-list: validate'>
								<input type='hidden' name='action' value='add' />
								<div class='form-field form-required'>
									<label for='category_name'><?php echo __('Category name', 'dbem') ?></label>
									<input id='category-name' name='category_name' id='category_name' type='text' size='40' />
									<p><?php echo __('The name of the category', 'dbem'); ?></p>
								</div>
								<p class='submit'><input type='submit' class='button' name='submit' value='<?php echo __('Add category', 'dbem') ?>' /></p>
							</form>
					  	</div>
					</div> 
				</div>    
			</div> 
			<!-- end col-left --> 		
		</div> 
  	</div>
  	<?php
}


function em_categories_edit_layout($message = "") {
	$category_id = $_GET['category_ID'];
	$category = EM_Category::get($category_id);
	?>
	<div class='wrap'>
		<div id='icon-edit' class='icon32'>
			<br/>
		</div>
			
		<h2><?php echo __('Edit category', 'dbem') ?></h2>  
 		
		<?php if($message != "") : ?>
		<div id='message' class='updated fade below-h2' style='background-color: rgb(255, 251, 204);'>
			<p><?php echo $message ?></p>
		</div>
		<?php endif; ?>

		<div id='ajax-response'></div>

		<form name='editcat' id='editcat' method='post' action='admin.php?page=events-manager-categories' class='validate'>
			<input type='hidden' name='action' value='edit' />
			<input type='hidden' name='category_ID' value='<?php echo $category['category_id'] ?>'/>
		
			<table class='form-table'>
				<tr class='form-field form-required'>
					<th scope='row' valign='top'><label for='category_name'><?php echo __('Category name', 'dbem') ?></label></th>
					<td><input name='category_name' id='category-name' type='text' value='<?php echo $category['category_name'] ?>' size='40'  /><br />
		           <?php echo __('The name of the category', 'dbem') ?></td>
				</tr>
			</table>
			<p class='submit'><input type='submit' class='button-primary' name='submit' value='<?php echo __('Update category', 'dbem') ?>' /></p>
		</form>
	</div>
	<?php
}
?>