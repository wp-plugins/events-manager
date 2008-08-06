<?php
// List widget
function widget_dbem_list($args) {
  extract($args);

  $options = get_option("widget_dbem_list");
  if (!is_array( $options ))
	{
		$options = array(
      'title' => __('Events','dbem')
      ); 
  }      

  echo $before_widget;
  echo $before_title;
  echo $options['title'];
  echo $after_title;
  $events_list = dbem_get_events_list($options['limit'],$options['scope'],$options['order'] ,$options['format'], false);
 	if ($events_list == __('No events', 'dbem'))
		$events_list = "<li>$events_list</li>";
	echo "<ul>
					$events_list
		 	 </ul>";               
	
  echo $after_widget;
}

function dbem_list_control() 
{
  $options = get_option("widget_dbem_list");
  
  if (!is_array( $options ))
	{
		$options = array(
    'title' => _('Events', 'dbem'),
	  'limit' => '5',
	  'scope' => 'future',
	 	'order' => 'ASC', 
	  'format' => DEFAULT_WIDGET_EVENT_LIST_ITEM_FORMAT
    ); 
  }      
  
  if ($_POST['dbem_list-Submit']) 
  {
    $options['title'] = htmlspecialchars($_POST['dbem_list-WidgetTitle']);
    $options['limit'] = $_POST['dbem_list-WidgetLimit'];   
    $options['scope'] = $_POST['dbem_list-WidgetScope']; 
		$options['order'] = $_POST['dbem_list-WidgetOrder'];  
		$options['format'] = $_POST['dbem_list-WidgetFormat'];  
		update_option("widget_dbem_list", $options);
  }
  
	if ($options['scope'] == "all" ) 
		$allSelected = "selected='selected'";
	else 
		$allSelected = ""; 
  if ($options['scope'] == "past" ) 
		$pastSelected = "selected='selected'";
	else
  	$pastSelected = ""; 
	if ($options['scope'] == "future" ) 
		$futureSelected = "selected='selected'";
	else
		$futureSelected = ""; 
		
	if ($options['order'] == "ASC" ) 
		$ASCSelected = "selected='selected'";
	else 
		$ASCSelected = ""; 
  if ($options['order'] == "DESC" ) 
		$DESCSelected = "selected='selected'";
	else
  	$DESCSelected = ""; 
		
		
?>
  <p>
    <label for="dbem_list-WidgetTitle"><?php _e('Title'); ?>: </label>
    <input type="text" id="dbem_list-WidgetTitle" name="dbem_list-WidgetTitle" value="<?php echo $options['title'];?>" />
  </p>
  <p>
    <label for="dbem_list-WidgetTitle"><?php _e('Number of events','dbem'); ?>: </label>
    <input type="text" id="dbem_list-WidgetLimit" name="dbem_list-WidgetLimit" value="<?php echo $options['limit'];?>" />
  </p>
  <p>
    <label for="dbem_list-WidgetScope"><?php _e('Scope of the events','dbem'); ?>:</label><br/>
  	<select name="dbem_list-WidgetScope" >
   		<option value="future" <?php echo $futureSelected; ?>><?php _e('Future events','dbem'); ?></option>
   		<option value="all" <?php echo $allSelected; ?>><?php _e('All events','dbem'); ?></option>
   		<option value="past" <?php echo $pastSelected; ?>><?php _e('Past events','dbem'); ?>:</option>
    </select>
  </p>
	<p>
    <label for="dbem_list-WidgetOrder"><?php _e('Order of the events','dbem'); ?>:</label><br/>
  	<select name="dbem_list-WidgetOrder" >
   		<option value="ASC" <?php echo $ASCSelected; ?>><?php _e('Ascendant','dbem'); ?></option>
   		<option value="DESC" <?php echo $DESCSelected; ?>><?php _e('Descendant','dbem'); ?>:</option>
 </select>
  </p>
  <p>
    <label for="dbem_list-WidgetTitle"><?php _e('List item format','dbem'); ?>:</label>
    <textarea id="dbem_list-WidgetFormat" name="dbem_list-WidgetFormat" rows="5" cols="24"><?php echo $options['format'];?></textarea>
  </p>
  <input type="hidden" id="dbem_list-Submit" name="dbem_list-Submit" value="1" />
<?php
}

// Calendar widget






function widget_dbem_calendar($args) {
  extract($args);

  $options = get_option("widget_dbem_calendar");
  if (!is_array( $options ))
	{
		$options = array(
      'title' => _e('calendar','dbem')
      ); 
  }      

  echo $before_widget;
    echo $before_title;
      echo $options['title'];
    echo $after_title;
    //Our Widget Content
    $current_month =  date("m");   
		
    dbem_get_calendar($current_month);
		  	echo $after_widget;
}

function dbem_calendar_control() 
{
  $options = get_option("widget_dbem_calendar");
  
  if (!is_array( $options ))
	{
		$options = array(
      'title' => 'Calendar',
	  ); 
  }      
  
  if ($_POST['dbem_calendar-Submit']) 
  {
    $options['title'] = htmlspecialchars($_POST['dbem_calendar-WidgetTitle']);
   	update_option("widget_dbem_calendar", $options);
  }
  
?>
  <p>
    <label for="dbem_calendar-WidgetTitle"><?php _e('Title'); ?>:</label>
    <input type="text" id="dbem_calendar-WidgetTitle" name="dbem_calendar-WidgetTitle" value="<?php echo $options['title'];?>" />
    <input type="hidden" id="dbem_calendar-Submit" name="dbem_calendar-Submit" value="1" />
  </p>
<?php
}






                      


// widgets registration
function dbem_list_init()
{                                                                   
  // $widget_ops = array('classname' => 'widget_dbem_list', 'description' => __( "A list of the events") );
  //   wp_register_sidebar_widget('widget_dbem_list', __('Events List'), 'widget_dbem_list', $widget_ops);           
  register_sidebar_widget(__('Events List', 'dbem'), 'widget_dbem_list');
  register_widget_control(__('Events List', 'dbem'), 'dbem_list_control', 200, 200 );
  register_sidebar_widget(__('Events Calendar','dbem'), 'widget_dbem_calendar');
  register_widget_control(__('Events Calendar','dbem'), 'dbem_calendar_control', 300, 200 );     
}
add_action("plugins_loaded", "dbem_list_init");        
    





?>