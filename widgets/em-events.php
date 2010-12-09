<?php
/**
 * @author marcus
 * Standard events list widget
 */
class EM_Widget extends WP_Widget {
    /** constructor */
    function EM_Widget() {
    	$widget_ops = array('description' => __( "Display a list of events on Events Manager.", 'dbem') );
        parent::WP_Widget(false, $name = 'Events', $widget_ops);	
    }

    /** @see WP_Widget::widget */
    function widget($args, $instance) {		
    	echo $args['before_widget'];
	    echo $args['before_title'];
	    echo $instance['title'];
	    echo $args['after_title'];
	
		$events = EM_Events::get($instance);
		echo "<ul>";
		$li_wrap = !preg_match('/^<li>/i', trim($instance['format']));
		if ( count($events) > 0 ){
			foreach($events as $event){				
				if( $li_wrap ){
					echo '<li>'. $event->output($instance['format']) .'</li>';
				}else{
					echo $event->output($instance['format']);
				}
			}
		}else{
			echo '<li>'.__('No events', 'dbem').'</li>';
		}
		echo "</ul>";               
		
	    echo $args['after_widget'];
    }

    /** @see WP_Widget::update */
    function update($new_instance, $old_instance) {
    	//filter the new instance and replace blanks with defaults
    	$defaults = array(
    		'title' => __('Events','dbem'),
    		'scope' => 'future',
    		'order' => 'ASC',
    		'limit' => 5,
    		'format' => '#_LINKEDNAME<ul><li>#j #M #y</li><li>#_TOWN</li></ul>',
    		'nolistwrap' => false,
    		'orderby' => 'start_date,start_time,name'
    	);
    	foreach($defaults as $key => $value){
    		if( !empty($new_instance[$key]) ){
    			$new_instance[$key] = $value;
    		}
    	}
    	return $new_instance;
    }

    /** @see WP_Widget::form */
    function form($instance) {
        ?>
		<p>
			<label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title'); ?>: </label>
			<input type="text" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" value="<?php echo $instance['title']; ?>" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('limit'); ?>"><?php _e('Number of events','dbem'); ?>: </label>
			<input type="text" id="<?php echo $this->get_field_id('limit'); ?>" name="<?php echo $this->get_field_name('limit'); ?>" value="<?php echo $instance['limit']; ?>" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('scope'); ?>"><?php _e('Scope of the events','dbem'); ?>:</label><br/>
			<select id="<?php echo $this->get_field_id('scope'); ?>" name="<?php echo $this->get_field_name('scope'); ?>" >
				<option value="future" <?php echo ($instance['scope'] == 'future') ? 'selected="selected"':''; ?>><?php _e('Future events','dbem'); ?></option>
				<option value="all" <?php echo ($instance['scope'] == 'all') ? 'selected="selected"':''; ?>><?php _e('All events','dbem'); ?></option>
				<option value="past" <?php echo ($instance['scope'] == 'past') ? 'selected="selected"':''; ?>><?php _e('Past events','dbem'); ?></option>
			</select>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('order'); ?>"><?php _e('Order By','dbem'); ?>: </label>
			<select  id="<?php echo $this->get_field_id('orderby'); ?>" name="<?php echo $this->get_field_name('orderby'); ?>">
				<?php 
					$orderby_options = apply_filters('em_widget_orderby_ddm', array(
						'start_date,start_time,name' => __('start date, start time, event name','dbem'),
						'name,start_date,start_time' => __('name, start date, start time','dbem'),
						'name,end_date,end_time' => __('name, end date, end time','dbem'),
						'end_date,end_time,name' => __('end date, end time, event name','dbem'),
					)); 
				?>
				<?php foreach($orderby_options as $key => $value) : ?>   
	 			<option value='<?php echo $key ?>' <?php echo ( !empty($instance['orderby']) && $key == $instance['orderby']) ? "selected='selected'" : ''; ?>>
	 				<?php echo $value; ?>
	 			</option>
				<?php endforeach; ?>
			</select> 
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('order'); ?>"><?php _e('Order','dbem'); ?>: </label>
			<select id="<?php echo $this->get_field_id('order'); ?>" name="<?php echo $this->get_field_name('order'); ?>">
				<?php 
				$order_options = apply_filters('em_widget_order_ddm', array(
					'ASC' => __('Ascending','dbem'),
					'DESC' => __('Descending','dbem')
				)); 
				?>
				<?php foreach( $order_options as $key => $value) : ?>   
	 			<option value='<?php echo $key ?>' <?php echo ($key == $instance['order']) ? "selected='selected'" : ''; ?>>
	 				<?php echo $value; ?>
	 			</option>
				<?php endforeach; ?>
			</select>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('format'); ?>"><?php _e('List item format','dbem'); ?>: </label>
			<textarea rows="5" cols="24" id="<?php echo $this->get_field_id('format'); ?>" name="<?php echo $this->get_field_name('format'); ?>"><?php echo $instance['format']; ?></textarea>
		</p>
        <?php 
    }
}
add_action('widgets_init', create_function('', 'return register_widget("EM_Widget");'));
?>