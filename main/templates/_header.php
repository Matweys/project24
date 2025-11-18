<!DOCTYPE html>
<html class="hover">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
<title><?php if (!empty($title)) { ?><?php echo e($title); ?> — <?php } ?><?php echo e($config['site_name']); ?></title>
<link href="<?php echo $config['static_url']; ?>/assets/main.css?<?php echo $config['assets_ver']['main'] ?? ''; ?>" rel="stylesheet" type="text/css">
<link href="https://fonts.googleapis.com/css?family=Roboto+Slab:400,700&amp;subset=cyrillic-ext" rel="stylesheet">
<?php echo $head ?? ''; ?>
</head>
<body>
<?php include_once '_navbar.php'; ?>
<?php if (empty($hide_header)) { ?>
<?php $username = (!empty($current_user['name']) ? $current_user['name'] : (!empty($current_user['username']) ? $current_user['username'] : (!empty($current_user['email']) ? $current_user['email'] : ''))); ?>
<div class="page-wrapper">
	<div class="header">
		<table>
			<tr>
				<td class="header__shim"></td>
				<td class="header__logo">
					<button class="hamburger hamburger--vortex js-menu-toggler menu-toggler" type="button"><span class="hamburger-box"><span class="hamburger-inner"></span></span></button>
					<a href="<?php echo $config['base_url']; ?>/"><img alt="<?php echo $view['header_logo_text'] ?? $config['short_site_name'] ?? ''; ?>" class="header__logo_img" src="<?php echo $view['header_logo_url'] ?? ''; ?>" style="width: 150px !important; max-width: 150px !important; height: auto !important;"></a>
					<div class="header__logo_text"><a href="<?php echo $config['base_url']; ?>/"><?php echo $view['header_logo_description'] ?? ''; ?><?php if (!empty($config['debug'])) { ?><span class="debug">DEBUG</span><?php } ?></a></div>
				</td>
				<td class="header-menu" role="menubar">
<?php
    $menu = [];

    if ($storages ?? null) {
        foreach ($storages as $v) {
            $menu[] = ['url' => $config['base_url'] . '/storage/' . $v['uid'] . '/', 'title' => $v['title']];
        }
    }

    if (($storages ?? null) && count($storages) == 1 && isset($storages[0]['folders']) && is_array($storages[0]['folders'])) {
        foreach ($storages[0]['folders'] as $v) {
            $menu[] = ['url' => $config['base_url'] . '/storage/' . $storages[0]['uid'] . '/' . $v['id'] . '/', 'title' => $v['name']];
        }
    }

    if (is_array($current_user['role'] ?? null) && array_intersect(['admin', 'storage_management'], $current_user['role'])) {
        $menu[] = ['url' => $config['base_url'] . '/admin/storages/', 'title' => __('File Storages')];
    }

    if (is_array($current_user['role'] ?? null) && array_intersect(['admin', 'user_management'], $current_user['role'])) {
        $menu[] = ['url' => $config['base_url'] . '/admin/users/', 'title' => __('Users')];
    }

    if (is_array($current_user['role'] ?? null) && in_array('admin', $current_user['role'])) {
        $menu[] = ['url' => $config['base_url'] . '/admin/log/', 'title' => __('Log')];
    }

    ?>
<?php foreach ($menu as $item) { ?>
<?php if (!empty($item['menu'])) { ?>
<div class="<?php if (!empty($item['name']) && !empty($active_dropdown) && $item['name'] === $active_dropdown) { ?>active <?php } ?>dropdown">
	<a href="#"><?php echo e($item['title'] ?? ''); ?> <span class="menu_caret"><span></span></span></a>
	<ul role="menu">
<?php	foreach ($item['menu'] as $menu_item) { ?>
		<li<?php if (navbar_item_active($config, $menu_item['url'] ?? '', $active_item ?? '')) { ?> class="active"<?php } ?>><a href="<?php echo $menu_item['url'] ?? ''; ?>"><?php echo e($menu_item['title'] ?? ''); ?></a></li>
<?php	} ?>
	</ul>
</div>
<?php } else { ?>
          <a<?php if (navbar_item_active($config, $item['url'] ?? null, $active_item ?? '')) { ?> class="active"<?php } ?> href="<?php echo $item['url'] ?? ''; ?>"<?php if (!empty($item['id'])) {?> id="<?php echo $item['id']; ?>"<?php } ?>><?php echo e($item['title'] ?? ''); ?></a>
<?php } ?>
<?php } ?>
				</td>
				<td class="header-menu header-profile">
<div class="dropdown-right">
	<a href="<?php echo $config['base_url'] . '/profile/?' . http_build_query(['next' => $_SERVER['REQUEST_URI']]); ?>"><?php echo e($username); ?> <span class="menu_caret"><span></span></span></a>
	<ul role="menu">
		<li><a href="<?php echo $config['base_url'] . '/profile/?' . http_build_query(['next' => $_SERVER['REQUEST_URI']]); ?>"><?php echo __('Edit Profile'); ?></a></li>
		<li><a href="<?php echo $config['base_url']; ?>/logout"><?php echo __('Sign out'); ?></a></li>
	</ul>
</div>
				</td>
			</tr>
		</table>
		<div class="mobile-menu js-mobile-menu" role="menu">
			<div class="mobile-menu-inner">
<ul>
<?php foreach ($menu as $item) { ?>
<?php if (!empty($item['menu'])) { ?>
<?php	foreach ($item['menu'] as $menu_item) { ?>
<li><a class="mobile-menu__link<?php if (navbar_item_active($config, $menu_item['url'] ?? '', $active_item ?? '')) { ?> active<?php } ?>" href="<?php echo $menu_item['url'] ?? ''; ?>"><?php echo e($menu_item['title'] ?? ''); ?></a></li>
<?php	} ?>
<?php } else { ?>
<li><a class="mobile-menu__link<?php if (navbar_item_active($config, $item['url'] ?? null, $active_item ?? '')) { ?> active<?php } ?>" href="<?php echo $item['url'] ?? ''; ?>"><?php echo e($item['title'] ?? ''); ?></a></li>
<?php } ?>
<?php } ?>
<li><a class="mobile-menu__link" href="<?php echo $config['base_url']; ?>/profile/?<?php echo http_build_query(['next' => $_SERVER['REQUEST_URI']]); ?>"><?php echo __('Edit Profile'); ?></a></li>
<li><a class="mobile-menu__link" href="<?php echo $config['base_url']; ?>/logout"><?php echo __('Sign out'); ?></a></li>
</ul>
			</div>
		</div>
	</div>
	<div class="container-fluid" style="padding-top: 1rem; padding-bottom: 2rem;">
<?php include_once '_flash_messages.php'; ?>
<?php flash_messages(); ?>
<?php if (isset($breadcrumbs) && is_array($breadcrumbs) && count($breadcrumbs) > 1) { ?>
<div class="breadcrumbs"><ul><?php foreach (array_slice($breadcrumbs, 0, -1) as $k => $v) { ?><li class="breadcrumbs__item"><a class="breadcrumbs__link" href="<?php echo $k; ?>"><?php echo e($v); ?></a></li><?php } ?><li class="breadcrumbs__item--active"><?php echo array_values(array_slice($breadcrumbs, -1))[0]; ?></li></ul>
<?php echo $breadcrumbs_after ?? ''; ?></div>
<?php } ?>
<?php } ?>
