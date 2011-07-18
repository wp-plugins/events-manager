<?php
	global $bp, $EM_Notices;
	echo $EM_Notices;
	$events = EM_Events::get(array('group'=>'my'));
	if( count($events) > 0 ){
		?>
		<?php echo $EM_Notices; ?>
				
		<table class="widefat events-table">
			<thead>
				<tr>
					<th><?php _e ( 'Name', 'dbem' ); ?></th>
					<th><?php _e ( 'Location', 'dbem' ); ?></th>
					<th><?php _e ( 'Date and time', 'dbem' ); ?></th>
					<th>&nbsp;</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach( $events as $EM_Event ): ?>
				<tr>
					<td><b><?php echo $EM_Event->output('#_EVENTLINK'); ?></b></td>
					<td><?php echo "<b>" . $EM_Event->location->name . "</b><br/>" . $EM_Event->location->address . " - " . $EM_Event->location->town;  ?></td>
					<th>
						<?php 
							echo date_i18n('D d M Y', $EM_Event->start);
							echo ($EM_Event->start != $EM_Event->end) ? " - ".date_i18n('D d M Y', $EM_Event->end):''; 
						?>
						<br />
						<?php echo substr ( $EM_Event->start_time, 0, 5 ) . " - " . substr ( $EM_Event->end_time, 0, 5 );	?>
						<?php if($EM_Event->can_manage('edit_events','edit_others_events')): ?>
						<a href="<?php echo $EM_Event->output('#_EDITEVENTURL'); ?>"><?php _e ( 'edit', 'dbem' ); ?></a>
						<?php endif; ?>
					</th>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php
	}else{
		?>
		<p><?php _e('No Events', 'dbem'); ?></p>
		<?php
	}
?>