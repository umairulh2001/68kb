<h2><?php echo lang('kb_envvars'); ?></h2>
<div id="form" class="wrap">

	<?php if(validation_errors()) {
		echo '<div class="error">'.validation_errors().'</div>';
	} ?>

	<p><?php echo lang('kb_envvars_intro'); ?></p>

	<form method="post" action="<?php echo $action; ?>" class="searchform">

	<label for="var_name"><?php echo lang('kb_var_name'); ?>:</label>
	<input type="text" name="var_name" id="cName" class="inputtext" value="<?php echo (isset($art->var_name)) ? set_value('var_name', $art->var_name) : set_value('var_name'); ?>" />

	<label for="var_value"><?php echo lang('kb_var_value'); ?>:</label>
	<textarea name="var_value" id="var_value" cols="15" rows="8" class="inputtext"><?php echo (isset($art->var_value)) ? set_value('var_value', $art->var_value) : set_value('var_value'); ?></textarea>

	<label for="var_description"><?php echo lang('kb_var_description'); ?>:</label>
	<input type="text" name="var_description" id="var_description" class="inputtext" value="<?php echo (isset($art->var_description)) ? set_value('var_description', $art->var_description) : set_value('var_description'); ?>" />

	<p><input type="submit" name="submit" class="save" value="<?php echo lang('kb_save'); ?>" /></p>

	<input type="hidden" name="id" value="<?php echo (isset($art->id)) ? set_value('id', $art->id) : set_value('id'); ?>" />
	<?php echo form_close(); ?>

	<div class="clear"></div>
</div>
