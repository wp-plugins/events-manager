<?php
/**
 * @author marcus
 * Standard events calendar widget
 */
class EM_Widget_Calendar extends WP_Widget {
    /** constructor */
    function EM_Widget_Calendar() {
    	$widget_ops = array('description' => __( "Display your events in a calendar widget.", 'dbem') );
        parent::WP_Widget(false, $name = __('Events Calendar','dbem'), $widget_ops);	
    }

    /** @see WP_Widget::widget */
    function widget($args, $instance) {
		echo $args['before_widget'];
	    echo $args['before_title'];
	    echo $instance['title'];
	    echo $args['after_title'];
	    
	    //Our Widget Content  
		$instance['month'] = date("m");
		echo '<div id="em-calendar-'.rand(100,200).'" class="em-calendar-wrapper">';
	    echo EM_Calendar::get($instance);
		echo '</div>';
	    
	    echo $args['after_widget'];
    }

    /** @see WP_Widget::update */
    function update($new_instance, $old_instance) {
    	//filter the new instance and replace blanks with defaults
    	$new_instance['title'] = ($new_instance['title'] == '') ? __('Calendar','dbem'):$new_instance['title'];
    	$new_instance['long_events'] = ($new_instance['long_events'] == '') ? 0:$new_instance['long_events'];
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
			<label for="<?php echo $this->get_field_id('long_events'); ?>"><?php _e('Show Long Events?', 'dbem'); ?>: </label>
			<input type="checkbox" id="<?php echo $this->get_field_id('long_events'); ?>" name="<?php echo $this->get_field_name('long_events'); ?>" value="1" <?php echo ($instance['long_events'] == '1') ? 'checked="checked"':''; ?>/>
		</p>
        <?php 
    }

}
add_action('widgets_init', create_function('', 'return register_widget("EM_Widget_Calendar");'));