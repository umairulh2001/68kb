<h2><?php echo lang('kb_main_settings'); ?></h2>

<div id="form" class="">
	
	<?php if(validation_errors()) {
		echo '<div class="error">'.validation_errors().'</div>';
	} ?>
	
	<?php echo form_open('admin/settings/main'); ?>
	
	<table width="100%" cellspacing="0">
		<tr>
			<td class="row1"><label for="site_name"><?php echo lang('kb_site_title'); ?>:</label></td>
			<td class="row1"><input type="text" size="50" name="site_name" id="site_name" value="<?php echo (isset($settings['site_name'])) ? form_prep($settings['site_name']) : ''; ?>" /></td>
		</tr>
		<tr>
			<td class="row2"><label for="site_keywords"><?php echo lang('kb_site_keywords'); ?>:</label></td>
			<td class="row2"><input type="text" size="50" name="site_keywords" id="site_keywords" value="<?php echo (isset($settings['site_keywords'])) ? form_prep($settings['site_keywords']) : ''; ?>" /></td>
		</tr>
		<tr>
			<td class="row1"><label for="site_description"><?php echo lang('kb_site_description'); ?>:</label></td>
			<td class="row1"><input type="text" size="50" name="site_description" id="site_description" value="<?php echo (isset($settings['site_description'])) ? form_prep($settings['site_description']) : ''; ?>" /></td>
		</tr>
		<tr>
			<td class="row2"><label for="site_email"><?php echo lang('kb_email'); ?>:</label></td>
			<td class="row2"><input type="text" size="50" name="site_email" id="site_email" value="<?php echo (isset($settings['site_email'])) ? form_prep($settings['site_email']) : ''; ?>" /></td>
		</tr>
		<tr>
			<td class="row1"><label for="max_search"><?php echo lang('kb_max_search'); ?>:</label></td>
			<td class="row1"><input type="text" size="50" name="max_search" id="max_search" value="<?php echo (isset($settings['max_search'])) ? form_prep($settings['max_search']) : ''; ?>" /></td>
		</tr>
		<tr>
			<td class="row2"><label for="max_search"><?php echo lang('kb_allow_comments'); ?>:</label></td>
			<td class="row2">
				<select name="comments">
					<option value="Y"<?php echo (isset($settings['comments']) && $settings['comments'] == 'Y') ? ' selected' : ''; ?>><?php echo lang('kb_yes'); ?></option>
					<option value="N"<?php echo (isset($settings['comments']) && $settings['comments'] == 'N') ? ' selected' : ''; ?>><?php echo lang('kb_no'); ?></option>
				</select>
			</td>
		</tr>
		<tr>
			<td class="row1"><label for="cache_time"><?php echo lang('kb_cache_time'); ?>:</label></td>
			<td class="row1">
				<input type="text" size="4" name="cache_time" id="cache_time" value="<?php echo (isset($settings['cache_time'])) ? form_prep($settings['cache_time']) : ''; ?>" />
				<a href="javascript:void(0);" title="<?php echo lang('kb_cache_desc'); ?>" class="tooltip">
				<img src="<?php echo base_url(); ?>images/help.png" border="0" alt="<?php echo lang('kb_edit'); ?>" />	
				</a>
			</td>
		</tr>
		<tr>
			<td class="row2"><label for="enable_viewer_login"><?php echo lang('kb_enable_viewer_login'); ?>:</label></td>
			<td class="row2">
				<select name="enable_viewer_login" id="enable_viewer_login">
					<option value="Y"<?php echo (isset($settings['enable_viewer_login']) && $settings['enable_viewer_login'] == 'Y') ? ' selected' : ''; ?>><?php echo lang('kb_yes'); ?></option>
					<option value="N"<?php echo ( ! isset($settings['enable_viewer_login']) || $settings['enable_viewer_login'] == 'N') ? ' selected' : ''; ?>><?php echo lang('kb_no'); ?></option>
				</select>
				<span class="small"><?php echo lang('kb_enable_viewer_login_hint'); ?></span>
			</td>
		</tr>
		<tr>
			<td class="row1"><label for="session_timeout"><?php echo lang('kb_session_timeout'); ?>:</label></td>
			<td class="row1">
				<input type="text" size="6" name="session_timeout" id="session_timeout" value="<?php echo (isset($settings['session_timeout'])) ? form_prep($settings['session_timeout']) : '120'; ?>" />
				<span class="small"><?php echo lang('kb_session_timeout_hint'); ?></span>
			</td>
		</tr>
		<tr>
			<td class="row2"><label for="login_max_attempts"><?php echo lang('kb_login_max_attempts'); ?>:</label></td>
			<td class="row2">
				<input type="text" size="6" name="login_max_attempts" id="login_max_attempts" value="<?php echo (isset($settings['login_max_attempts'])) ? form_prep($settings['login_max_attempts']) : '5'; ?>" />
				<span class="small"><?php echo lang('kb_login_max_attempts_hint'); ?></span>
			</td>
		</tr>
		<tr>
			<td class="row1"><label for="login_lockout_minutes"><?php echo lang('kb_login_lockout_minutes'); ?>:</label></td>
			<td class="row1">
				<input type="text" size="6" name="login_lockout_minutes" id="login_lockout_minutes" value="<?php echo (isset($settings['login_lockout_minutes'])) ? form_prep($settings['login_lockout_minutes']) : '15'; ?>" />
				<span class="small"><?php echo lang('kb_login_lockout_minutes_hint'); ?></span>
			</td>
		</tr>
		<tr>
			<td class="row2"><label for="force_https"><?php echo lang('kb_force_https'); ?>:</label></td>
			<td class="row2">
				<select name="force_https" id="force_https">
					<option value="Y"<?php echo (isset($settings['force_https']) && $settings['force_https'] == 'Y') ? ' selected' : ''; ?>><?php echo lang('kb_yes'); ?></option>
					<option value="N"<?php echo ( ! isset($settings['force_https']) || $settings['force_https'] != 'Y') ? ' selected' : ''; ?>><?php echo lang('kb_no'); ?></option>
				</select>
				<span class="small"><?php echo lang('kb_force_https_hint'); ?></span>
			</td>
		</tr>
		<?php $this->core_events->trigger('settings/form');?>
	</table>
	
	
	<p><input type="submit" name="submit" class="save" value="<?php echo lang('kb_save'); ?>" /></p>
	
	<?php echo form_close(); ?>
	
	<div class="clear"></div>
</div>