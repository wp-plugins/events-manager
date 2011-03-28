<?php
$attributes = em_get_attributes();
$has_depreciated = false;
?>
<?php if( count( $attributes ) > 0 ) : ?>
	<div class="inside">
		<?php foreach( $attributes as $name) : ?>
		<div>
			<label for="em_attributes[<?php echo $name ?>]"><?php echo $name ?></label>
			<input type="text" name="em_attributes[<?php echo $name ?>]" value="<?php echo ( is_array($EM_Event->attributes) && array_key_exists($name, $EM_Event->attributes) ) ? htmlspecialchars($EM_Event->attributes[$name], ENT_QUOTES):''; ?>" />
		</div>
		<?php endforeach; ?>
	</div>
<?php endif; ?>