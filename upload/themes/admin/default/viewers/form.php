<h2><?php echo lang('kb_manage_viewers'); ?></h2>

<div id="form">
	<?php if(validation_errors()) {
		echo '<div class="error">'.validation_errors().'</div>';
	} ?>
	<form method="post" action="<?php echo $action; ?>" class="searchform">


	<p class="row1">
		<label for="email"><?php echo lang('kb_email'); ?>:</label>
		<input type="text" name="email" id="email" class="inputtext" value="<?php echo (isset($art->email)) ? set_value('email', $art->email) : set_value('email'); ?>" />
	</p>

	<p class="row2">
		<label for="firstname"><?php echo lang('kb_firstname'); ?>:</label>
		<input type="text" name="firstname" id="firstname" class="inputtext" value="<?php echo (isset($art->firstname)) ? set_value('firstname', $art->firstname) : set_value('firstname'); ?>" />
	</p>

	<p class="row1">
		<label for="lastname"><?php echo lang('kb_lastname'); ?>:</label>
		<input type="text" name="lastname" id="lastname" class="inputtext" value="<?php echo (isset($art->lastname)) ? set_value('lastname', $art->lastname) : set_value('lastname'); ?>" />
	</p>

	<p class="row2">
		<label for="active"><?php echo lang('kb_active'); ?>:</label>
		<select name="active" id="active">
			<option value="Y"<?php if ( ! isset($art->active) || $art->active == 1) echo ' selected="selected"'; ?>><?php echo lang('kb_yes'); ?></option>
			<option value="N"<?php if (isset($art->active) && $art->active == 0) echo ' selected="selected"'; ?>><?php echo lang('kb_no'); ?></option>
		</select>
	</p>

		<?php if(isset($art->id)): ?>
		<div class="warning"><p><?php echo lang('kb_password_change'); ?></p></div>
		<?php endif; ?>

	<p class="row1">
		<label for="password"><?php echo lang('kb_password'); ?>:</label>
		<input type="password" name="password" id="password" class="inputtext" value="" />
	</p>

	<p class="row2">
		<label for="passconf"><?php echo lang('kb_confirmpassword'); ?>:</label>
		<input type="password" name="passconf" id="passconf" class="inputtext" value="" />
	</p>


	<p style="text-align: center;">
		<input type="submit" name="submit" class="save" value="<?php echo lang('kb_save'); ?>" />
	</p>

	<input type="hidden" name="id" value="<?php echo (isset($art->id)) ? $art->id : ''; ?>" />
	<input type="hidden" name="action" value="<?php echo $action; ?>" />
	<?php echo form_close(); ?>

	<div class="clear"></div>
</div>
