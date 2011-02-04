<?php
function em_admin_categories_page() {      
	global $wpdb, $EM_Category;
	
	if( !empty($_REQUEST['action']) ){
		if( $_REQUEST['action'] == "save") {
			// save (add/update) category
			if( empty($EM_Category) || !is_object($EM_Category) ){
				$EM_Category = new EM_Category(); //blank category
				$success_message = __('The category has been added.', 'dbem');
			}else{
				$success_message = __('The category has been updated.', 'dbem');
			}
			$EM_Category->get_post();
			if ( $EM_Category->validate() ) {
				$EM_Category->save(); //FIXME better handling of db write fails when saving category
				em_categories_table_layout($success_message);
			} else {
				?>
				<div id='message' class='error '>
					<p>
						<strong><?php _e( "Ach, there's a problem here:", "dbem" ) ?></strong><br /><br /><?php echo implode('<br />', $EM_Category->errors); ?>
					</p>
				</div>
				<?php  
				em_categories_edit_layout();
			}
		} elseif( $_REQUEST['action'] == "edit" ){
			em_categories_edit_layout();
		} elseif( $_REQUEST['action'] == "delete" ){
			//delelte category
			EM_Categories::delete($_REQUEST['categories']);
			//FIXME no result verification when deleting various categories
			$message = __('Categories Deleted', "dbem" );
			em_categories_table_layout($message);
		}
	}else{
		em_categories_table_layout($message);
	}
} 

function em_categories_table_layout($message = "") {
	$categories = EM_Categories::get();
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
									<?php foreach ($categories as $EM_Category) : ?>
									<tr>
										<td><input type='checkbox' class ='row-selector' value='<?php echo $EM_Category->id ?>' name='categories[]'/></td>
										<td><a href='<?php echo get_bloginfo('wpurl') ?>/wp-admin/admin.php?page=events-manager-categories&amp;action=edit&amp;category_id=<?php echo $EM_Category->id ?>'><?php echo htmlspecialchars($EM_Category->id, ENT_QUOTES); ?></a></td>
										<td><a href='<?php echo get_bloginfo('wpurl') ?>/wp-admin/admin.php?page=events-manager-categories&amp;action=edit&amp;category_id=<?php echo $EM_Category->id ?>'><?php echo htmlspecialchars($EM_Category->name, ENT_QUOTES); ?></a></td>
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
								<input type='hidden' name='action' value='save' />
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
	global $EM_Category;
	if( !is_object($EM_Category) ){
		$EM_Category = new EM_Category();
	}
	//check that user can access this page
	if( is_object($EM_Category) && !$EM_Category->can_manage() ){
		?>
		<div class="wrap"><h2><?php _e('Unauthorized Access','dbem'); ?></h2><p><?php _e('You do not have the rights to manage this event.','dbem'); ?></p></div>
		<?php
		return;
	}
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
			<input type='hidden' name='action' value='save' />
			<input type='hidden' name='category_id' value='<?php echo $EM_Category->id ?>'/>
		
			<table class='form-table'>
				<tr class='form-field form-required'>
					<th scope='row' valign='top'><label for='category_name'><?php echo __('Category name', 'dbem') ?></label></th>
					<td><input name='category_name' id='category-name' type='text' value='<?php echo $EM_Category->name ?>' size='40'  /><br />
		           <?php echo __('The name of the category', 'dbem') ?></td>
				</tr>
			</table>
			<p class='submit'><input type='submit' class='button-primary' name='submit' value='<?php echo __('Update category', 'dbem') ?>' /></p>
		</form>
	</div>
	<?php
}
?>