<!doctype html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?=sprintf(__('%s - Forgot your password?'), e($config['site_name']))?></title>
<link href="<?=$config['static_url']?>/auth/main.css?<?=$config['assets_ver']['auth'] ?? ''?>" rel="stylesheet" type="text/css">
</head>
<body>
<div class="wrapper">
	<h1 class="h3" style="font-weight:normal;margin:0 0 2rem;text-align:center;"><?=__('Forgot password?')?></h1>
<?php $flash_messages = flash_get_messages() ?>
<?php if (!empty($flash_messages['info']) && is_array($flash_messages['info'])) { ?>
	<div class="alert alert-primary"><p><?=join('</p><p>', $flash_messages['info'])?></p></div>
<?php } ?>
<?php if (!empty($flash_messages['error']) && is_array($flash_messages['error'])) { ?>
	<div class="form-alert"><?=join('</div><div class="form-alert">', $flash_messages['error'])?></div>
<?php } ?>
<?php if (!empty($data['message'])) { ?>
	<div class="form-alert"><?=e($data['message'])?></div>
<?php } ?>
<?=nl2p(__('Enter the email address associated with your account and we will send you a link to reset your password.'))?>
	<form action="<?=$_SERVER['REQUEST_URI']?>" method="post">
<?php form_csrf_field($config, $csrf_id ?? '') ?>
		<div>
			<label class="form-label" for="email">Email</label>
			<input autofocus class="form-control" id="email" name="email" required type="email" value="<?=e($email)?>">
		</div>
		<button class="btn btn-primary" style="margin-top:1rem;width:100%;" type="submit"><?=__('Request Password Reset')?></button>
	</form>
	<ul class="menu"><li><a href="<?=$config['auth']['base_url']?>/login" rel="nofollow"><?=__('← Sign in')?></a></li></ul>
<?php language_switch($lang, $languages) ?>
</div>
<script src="<?=$config['static_url']?>/auth/main.js?<?=$config['assets_ver']['auth'] ?? ''?>"></script>
</body>
</html>
