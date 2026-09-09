<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="content-type" content="text/html; charset=utf-8" />
<title><?php echo (isset($settings['site_name'])) ? $settings['site_name'] : ''; ?> &raquo; <?php echo lang('kb_viewer_login'); ?></title>
<style type="text/css">
	body { font-family: Arial, Helvetica, sans-serif; background: #f2f2f2; color: #333; }
	#login_box { width: 320px; margin: 80px auto; padding: 25px; background: #fff; border: 1px solid #ddd; }
	#login_box h1 { font-size: 18px; margin: 0 0 5px; }
	#login_box h2 { font-size: 14px; font-weight: normal; color: #666; margin: 0 0 20px; }
	#login_box label { display: block; margin-bottom: 4px; font-weight: bold; }
	#login_box input[type=text], #login_box input[type=email], #login_box input[type=password] { width: 100%; padding: 6px; margin-bottom: 15px; box-sizing: border-box; }
	#login_box .submit { padding: 8px 18px; cursor: pointer; }
	#login_box .error { color: #b00; margin-bottom: 12px; }
	#login_box a { color: #333; text-decoration: none; }
</style>
</head>
<body>
	<div id="login_box">
		<h1><a href="<?php echo site_url(); ?>"><?php echo (isset($settings['site_name'])) ? $settings['site_name'] : ''; ?></a></h1>
		<h2><?php echo lang('kb_viewer_login'); ?></h2>

		<?php if ( ! empty($error)): ?>
		<p class="error"><?php echo $error; ?></p>
		<?php endif; ?>
		<?php echo validation_errors(); ?>

		<?php echo form_open('kb/login'); ?>

			<label for="email"><?php echo lang('kb_email'); ?>:</label>
			<?php echo form_input(array('type' => 'email', 'name' => 'email', 'id' => 'email', 'value' => set_value('email'))); ?>

			<label for="password"><?php echo lang('kb_password'); ?>:</label>
			<?php echo form_password(array('name' => 'password', 'id' => 'password')); ?>

			<?php echo form_submit(array('name' => 'submit', 'value' => lang('kb_login'), 'class' => 'submit')); ?>

		<?php echo form_close(); ?>
	</div>
</body>
</html>
