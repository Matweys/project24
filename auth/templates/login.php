<!doctype html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?=sprintf(__('%s - Sign in'), e($config['site_name']))?></title>
<link href="<?=$config['static_url']?>/auth/main.css?<?=$config['assets_ver']['auth'] ?? ''?>" rel="stylesheet" type="text/css">
</head>
<body>
<div class="wrapper">
	<h1 class="h3" style="font-weight:normal;margin:0 0 2rem;text-align:center;"><?=__('Sing in')?></h1>
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
	<form action="<?=$_SERVER['REQUEST_URI']?>" method="post">
<?php form_csrf_field($config, $csrf_id ?? '') ?>
<?php if ($next_url) { ?>
<input id="next" name="next" type="hidden" value="<?=e($next_url)?>">
<?php } ?>
		<div>
			<label class="form-label" for="username">Email</label>
			<input autofocus class="form-control" id="username" name="username" required type="email" value="<?=e($username)?>">
		</div>
		<div style="margin-top:1rem;">
			<label class="form-label" for="password"><?=__('Password')?></label>
			<input autocomplete="off" class="form-control" id="password" name="password" required type="password">
		</div>
		<div style="margin-top:1rem;">
			<div class="form-check">
				<input class="form-check-input"<?php if ($remember) { ?> checked<?php } ?> id="remember" name="remember" type="checkbox" value="1">
				<label class="form-check-label" for="remember"><?=__('Remember me')?></label>
			</div>
		</div>
		<button class="btn btn-primary" style="margin-top:1rem;width:100%;" type="submit"><?=__('Sing in')?></button>
	</form>
	<ul class="menu"><li><a href="<?=$config['auth']['base_url']?>/forgot_password" rel="nofollow"><?=__('Forgot password?')?></a></li></ul>
<?php language_switch($lang, $languages) ?>
</div>
<script src="<?=$config['static_url']?>/auth/main.js?<?=$config['assets_ver']['auth'] ?? ''?>"></script>
</body>
</html>
