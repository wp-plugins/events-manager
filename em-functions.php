<?php
function em_hello_to_new_user() {
	if ( get_option ( 'dbem_hello_to_user' ) == 1 ) {
		$current_user = wp_get_current_user ();
		$advice = sprintf ( __ ( "<p>Hey, <strong>%s</strong>, welcome to <strong>Events Manager</strong>! We hope you like it around here.</p> 
		<p>Now it's time to insert events lists through  <a href='%s' title='Widgets page'>widgets</a>, <a href='%s' title='Template tags documentation'>template tags</a> or <a href='%s' title='Shortcodes documentation'>shortcodes</a>.</p>
		<p>By the way, have you taken a look at the <a href='%s' title='Change settings'>Settings page</a>? That's where you customize the way events and locations are displayed.</p>
		<p>What? Tired of seeing this advice? I hear you, <a href='%s' title='Don't show this advice again'>click here</a> and you won't see this again!</p>", 'dbem' ), $current_user->display_name, get_bloginfo ( 'url' ) . '/wp-admin/widgets.php', 'http://wp-events-plugin.com/documentation/template-tags/', 'http://wp-events-plugin.com/documentation/shortcodes/', get_bloginfo ( 'url' ) . '/wp-admin/admin.php?page=events-manager-options', get_bloginfo ( 'url' ) . '/wp-admin/admin.php?page=events-manager&disable_hello_to_user=true' );
		?>
		<div id="message" class="updated">
			<?php echo $advice; ?>
		</div>
		<?php
	}
}

/**
 * Takes a few params and determins a pagination link structure
 * @param string $link
 * @param int $total
 * @param int $limit
 * @param int $page
 * @param int $pagesToShow
 * @return string
 */
function em_paginate($link, $total, $limit, $page=1, $pagesToShow=10){
	if($limit > 0){
		$maxPages = ceil($total/$limit); //Total number of pages
		$startPage = ($page <= $pagesToShow) ? 1 : $pagesToShow * (floor($page/$pagesToShow)) ; //Which page to start the pagination links from (in case we're on say page 12 and $pagesToShow is 10 pages)
		$placeholder = urlencode('%PAGE%');
		$link = str_replace('%PAGE%', urlencode('%PAGE%'), $link); //To avoid url encoded/non encoded placeholders
	    //Add the back and first buttons
		    $string = ($page>1 && $startPage != 1) ? '<a class="prev page-numbers" href="'.str_replace($placeholder,1,$link).'">&lt;&lt;</a> ' : '';
		    $string .= ($page>1) ? ' <a class="prev page-numbers" href="'.str_replace($placeholder,$page-1,$link).'">&lt;</a> ' : '';
		//Loop each page and create a link or just a bold number if its the current page
		    for ($i = $startPage ; $i < $startPage+$pagesToShow && $i <= $maxPages ; $i++){
	            if($i == $page){
	                $string .= ' <strong><span class="page-numbers current">'.$i.'</span></strong>';
	            }else{
	                $string .= ' <a class="page-numbers" href="'.str_replace($placeholder,$i,$link).'">'.$i.'</a> ';                
	            }
		    }
		//Add the forward and last buttons
		    $string .= ($page < $maxPages) ? ' <a class="next page-numbers" href="'.str_replace($placeholder,$page+1,$link).'">&gt;</a> ' :' ' ;
		    $string .= ($i-1 < $maxPages) ? ' <a class="next page-numbers" href="'.str_replace($placeholder,$maxPages,$link).'">&gt;&gt;</a> ' : ' ';
		//Return the string
		    return $string;
	}
}

/**
 * Takes a url and appends GET params (supplied as an assoc array), it automatically detects if you already have a querystring there
 * @param string $url
 * @param array $params
 * @param bool $html
 * @param bool $encode
 * @return string
 */
function em_add_get_params($url, $params=array(), $html=true, $encode=true){	
	//Splig the url up to get the params and the page location
	$url_parts = explode('?', $url);
	$url = $url_parts[0];
	$url_params_dirty = array();
	if(count($url_parts) > 1){
		$url_params_dirty = $url_parts[1];
	}
	//get the get params as an array
	if( strstr($url_params_dirty, '&amp;') !== false ){
		$url_params_dirty = explode('&amp;', $url_params_dirty);
	}else{
		$url_params_dirty = explode('&', $url_params_dirty);		
	}
	//split further into associative array
	$url_params = array();
	foreach($url_params_dirty as $url_param){
		$url_param = explode('=', $url_param);
		$url_params[$url_param[0]] = $url_param[1];
	}
	//Merge it together
	$params = array_merge($url_params, $params);
	//Now build the array back up.
	$count = 0;
	foreach($params as $key=>$value){
		$value = ($encode) ? urlencode($value):$value;
		if( $count == 0 ){
			$url .= "?{$key}=".$value;
		}else{
			$url .= ($html) ? "&amp;{$key}=".$value:"&{$key}=".$value;
		}
		$count++;
	}
	return $url;
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

function em_option_items($array, $saved_value) {
	$output = "";
	foreach($array as $key => $item) {    
		$selected ='';
		if ($key == $saved_value)
			$selected = "selected='selected'";
		$output .= "<option value='$key' $selected >$item</option>\n";
	
	} 
	echo $output;
}

function em_checkbox_items($name, $array, $saved_values, $horizontal = true) { 
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

function em_options_input_text($title, $name, $description) {
	?>
	<tr valign="top" id='<?php echo $name;?>_row'>
		<th scope="row"><?php _e($title, 'dbem') ?></th>
	    <td>
			<input name="<?php echo $name ?>" type="text" id="<?php echo $title ?>" style="width: 95%" value="<?php echo htmlspecialchars(get_option($name), ENT_QUOTES); ?>" size="45" /><br />
			<em><?php echo $description; ?></em>
		</td>
	</tr>
	<?php
}
function em_options_input_password($title, $name, $description) {
	?>
	<tr valign="top" id='<?php echo $name;?>_row'>
		<th scope="row"><?php _e($title, 'dbem') ?></th>
	    <td>
			<input name="<?php echo $name ?>" type="password" id="<?php echo $title ?>" style="width: 95%" value="<?php echo get_option($name); ?>" size="45" /><br />
			<em><?php echo $description; ?></em>
		</td>
	</tr>
	<?php
}

function em_options_textarea($title, $name, $description) {
	?>
	<tr valign="top" id='<?php echo $name;?>_row'>
		<th scope="row"><?php _e($title,'dbem')?></th>
			<td>
				<textarea name="<?php echo $name ?>" id="<?php echo $name ?>" rows="6" cols="60"><?php echo htmlspecialchars(get_option($name), ENT_QUOTES);?></textarea><br/>
				<em><?php echo $description; ?></em>
			</td>
		</tr>
	<?php
}

function em_options_radio_binary($title, $name, $description) {
		$list_events_page = get_option($name); ?>
		 
	   	<tr valign="top" id='<?php echo $name;?>_row'>
	   		<th scope="row"><?php _e($title,'dbem'); ?></th>
	   		<td>  
	   			<?php _e('Yes'); ?> <input id="<?php echo $name ?>_yes" name="<?php echo $name ?>" type="radio" value="1" <?php if($list_events_page) echo "checked='checked'"; ?> />&nbsp;&nbsp;&nbsp;
				<?php _e('No'); ?> <input  id="<?php echo $name ?>_no" name="<?php echo $name ?>" type="radio" value="0" <?php if(!$list_events_page) echo "checked='checked'"; ?> />
				<br/><em><?php echo $description; ?></em>
			</td>
	   	</tr>
<?php	
}  
function em_options_select($title, $name, $list, $description) {
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
			<em><?php echo $description; ?></em>
		</td>
   	</tr>
	<?php	
}
// got from http://davidwalsh.name/php-email-encode-prevent-spam
function em_ascii_encode($e){  
	$output = '';
    for ($i = 0; $i < strlen($e); $i++) { $output .= '&#'.ord($e[$i]).';'; }  
    return $output;  
}
?>