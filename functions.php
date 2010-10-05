<?php
function em_hello_to_new_user() {
	if ( get_option ( 'dbem_hello_to_user' ) == 1 ) {
		$current_user = wp_get_current_user ();
		$advice = sprintf ( __ ( "<p>Hey, <strong>%s</strong>, welcome to <strong>Events Manager</strong>! We hope you like it around here.</p> 
		<p>Now it's time to insert events lists through  <a href='%s' title='Widgets page'>widgets</a>, <a href='%s' title='Template tags documentation'>template tags</a> or <a href='%s' title='Shortcodes documentation'>shortcodes</a>.</p>
		<p>By the way, have you taken a look at the <a href='%s' title='Change settings'>Settings page</a>? That's where you customize the way events and locations are displayed.</p>
		<p>What? Tired of seeing this advice? I hear you, <a href='%s' title='Don't show this advice again'>click here</a> and you won't see this again!</p>", 'dbem' ), $current_user->display_name, get_bloginfo ( 'url' ) . '/wp-admin/widgets.php', 'http://davidebenini.it/wordpress-plugins/events-manager#template-tags', 'http://davidebenini.it/wordpress-plugins/events-manager#shortcodes', get_bloginfo ( 'url' ) . '/wp-admin/admin.php?page=events-manager-options', get_bloginfo ( 'url' ) . '/wp-admin/admin.php?page=events-manager/events-manager.php&disable_hello_to_user=true' );
		?>
		<div id="message" class="updated">
			<?php echo $advice; ?>
		</div>
		<?php
	}
}


function url_exists($url) {
	
	if ((strpos ( $url, "http" )) === false)
		$url = "http://" . $url;
		// FIXME ripristina la linea seguente e VEDI DI SISTEMARE!!!!
	// if (is_array(@get_headers($url))) {
	if (true)
		return true;
	else
		return false;
}

/**
 * Gets all WP users
 * @return array
 */
function em_get_wp_users() {
	global $wpdb;
	$sql = "SELECT display_name, ID FROM $wpdb->users";  
	$users = $wpdb->get_results($sql, ARRAY_A);  
	$indexed_users = array();
	foreach($users as $user) 
		$indexed_users[$user['ID']] = $user['display_name'];
 	return $indexed_users;
}

/*
 * UI Helpers
 * previously dbem_UI_helpers.php functions
 */

function dbem_option_items($array, $saved_value) {
	$output = "";
	foreach($array as $key => $item) {    
		$selected ='';
		if ($key == $saved_value)
			$selected = "selected='selected'";
		$output .= "<option value='$key' $selected >$item</option>\n";
	
	} 
	echo $output;
}

function dbem_checkbox_items($name, $array, $saved_values, $horizontal = true) { 
	$output = "";
	foreach($array as $key => $item) {
		$checked = "";
		if (in_array($key, $saved_values))
			$checked = "checked='checked'";  
		$output .=  "<input type='checkbox' name='$name' value='$key' $checked /> $item ";
		if(!$horizontal)	
			$output .= "<br/>\n";
	}
	echo $output;
	
}

function dbem_options_input_text($title, $name, $description) {
	?>
	<tr valign="top" id='<?php echo $name;?>_row'>
		<th scope="row"><?php _e($title, 'dbem') ?></th>
	    <td>
			<input name="<?php echo $name ?>" type="text" id="<?php echo $title ?>" style="width: 95%" value="<?php echo htmlspecialchars(get_option($name), ENT_QUOTES); ?>" size="45" /><br />
						<?php _e($description, 'dbem') ?>
			</td>
		</tr>
	<?php
}
function dbem_options_input_password($title, $name, $description) {
	?>
	<tr valign="top" id='<?php echo $name;?>_row'>
		<th scope="row"><?php _e($title, 'dbem') ?></th>
	    <td>
			<input name="<?php echo $name ?>" type="password" id="<?php echo $title ?>" style="width: 95%" value="<?php echo get_option($name); ?>" size="45" /><br />
						<?php echo $description; ?>
			</td>
		</tr>
	<?php
}

function dbem_options_textarea($title, $name, $description) {
	?>
	<tr valign="top" id='<?php echo $name;?>_row'>
		<th scope="row"><?php _e($title,'dbem')?></th>
			<td><textarea name="<?php echo $name ?>" id="<?php echo $name ?>" rows="6" cols="60"><?php echo htmlspecialchars(get_option($name), ENT_QUOTES);?></textarea><br/>
				<?php echo $description; ?></td>
		</tr>
	<?php
}

function dbem_options_radio_binary($title, $name, $description) {
		$list_events_page = get_option($name); ?>
		 
	   	<tr valign="top" id='<?php echo $name;?>_row'>
	   		<th scope="row"><?php _e($title,'dbem'); ?></th>
	   		<td>   
				<input id="<?php echo $name ?>_yes" name="<?php echo $name ?>" type="radio" value="1" <?php if($list_events_page) echo "checked='checked'"; ?> /><?php _e('Yes'); ?> <br />
				<input  id="<?php echo $name ?>_no" name="<?php echo $name ?>" type="radio" value="0" <?php if(!$list_events_page) echo "checked='checked'"; ?> /><?php _e('No'); ?> <br />
				<?php echo $description; ?>
			</td>
	   	</tr>
<?php	
}  
function dbem_options_select($title, $name, $list, $description) {
	$option_value = get_option($name);
	if( $name == 'dbem_events_page' && !is_object(get_page($option_value)) ){
		$option_value = 0; //Special value
	}
	?>
   	<tr valign="top" id='<?php echo $name;?>_row'>
   		<th scope="row"><?php _e($title,'dbem'); ?></th>
   		<td>   
			<select name="<?php echo $name; ?>" > 
				<?php foreach($list as $key => $value) : ?>   
 				<option value='<?php echo $key ?>' <?php echo ("$key" == $option_value) ? "selected='selected' " : ''; ?>>
 					<?php echo $value; ?>
 				</option>
				<?php endforeach; ?>
			</select> <br/>
			<?php echo $description; ?>
		</td>
   	</tr>
	<?php	
}
// got from http://davidwalsh.name/php-email-encode-prevent-spam
function dbem_ascii_encode($e)  
{  
    for ($i = 0; $i < strlen($e); $i++) { $output .= '&#'.ord($e[$i]).';'; }  
    return $output;  
}
?>