<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="content-type" content="text/html; charset=utf-8" />
<title><?php echo (isset($settings['site_name'])) ? $settings['site_name'] : ''; ?> &raquo; <?php echo lang('kb_viewer_login'); ?></title>
<link href="<?php echo base_url();?>themes/front/emporium/default.css" rel="stylesheet" type="text/css" />
</head>
<body>
<div id="wrapper">
	<div id="logo">
		<h1><a href="<?php echo site_url(); ?>"><?php echo (isset($settings['site_name'])) ? $settings['site_name'] : ''; ?></a></h1>
	</div>
	<div id="page">
		<div id="content">
			<div class="post">
				<h2 class="title"><?php echo lang('kb_viewer_login'); ?></h2>
				<div class="entry">

					<?php if ( ! empty($error)): ?>
					<p class="error"><?php echo $error; ?></p>
					<?php endif; ?>
					<?php echo validation_errors(); ?>

					<?php echo form_open('kb/login'); ?>

						<p>
							<label for="email"><?php echo lang('kb_email'); ?>:</label><br />
							<?php echo form_input(array('type' => 'email', 'name' => 'email', 'id' => 'email', 'value' => set_value('email'), 'class' => 'input_box')); ?>
						</p>

						<p>
							<label for="password"><?php echo lang('kb_password'); ?>:</label><br />
							<?php echo form_password(array('name' => 'password', 'id' => 'password', 'class' => 'input_box')); ?>
						</p>

						<p>
							<?php echo form_submit(array('name' => 'submit', 'value' => lang('kb_login'), 'class' => 'submit')); ?>
						</p>

					<?php echo form_close(); ?>

				</div>
			</div>
		</div>
		<div style="clear: both;">&nbsp;</div>
	</div>
	<div id="footer">
		<p id="legal">Powered by <a href="http://68kb.com/">68kb</a>.</p>
	</div>
</div>
</body>
</html>
