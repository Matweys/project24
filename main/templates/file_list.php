<?php $title = $folder['name'] ?? $storage['title']; ?>
<?php if ($breadcrumbs_data) {
    $breadcrumbs = [
        $config['base_url'].'/storage/'.$storage['uid'].'/?'.http_build_query(['sort' => $sort_idx, 'desc' => $sort_desc, 'view_mode' => $view_mode]) => $storage['title'],
    ];
    foreach ($breadcrumbs_data as $v) {
        $breadcrumbs[$config['base_url'].'/storage/'.$storage['uid'].'/'.$v['id'].'/?'.http_build_query(['sort' => $sort_idx, 'desc' => $sort_desc, 'view_mode' => $view_mode])] = $v['name'];
    }
} ?>
<?php include_once '_helpers.php'; ?>
<!DOCTYPE html>
<html class="hover">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
<title><?php if (!empty($title)) { ?><?php echo e($title); ?> — <?php } ?><?php echo e($config['site_name']); ?></title>
<link href="<?php echo $config['static_url']; ?>/assets/filelist.css?<?php echo $config['assets_ver']['main'] ?? ''; ?>" rel="stylesheet" type="text/css">
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

    if ($storages) {
        foreach ($storages as $v) {
            $menu[] = ['url' => $config['base_url'] . '/storage/' . $v['uid'] . '/', 'title' => $v['title']];
        }
    }

    if ($storages && count($storages) == 1 && ($storages[0]['folders'] ?? null)) {
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


<?php $edit_permission = in_array($storage['permission_name'] ?? null, ['edit', 'full']); ?>
<?php $full_permission = ($storage['permission_name'] ?? null) === 'full'; ?>
<?php if ($edit_permission) { ?>
<div class="alert alert-danger" id="upload-alert" style="display:none;"></div>
<?php } ?>
<div id="upload-progress"></div>
<div class="page-header">
	<div style="-ms-flex-align:center;-ms-flex-flow:row wrap;align-items:center;flex-flow:row wrap;display:-ms-flexbox;display:flex;">
<?php if ($edit_permission) { ?>
<a class="btn btn-secondary" href="<?php echo $config['base_url'].'/storage/new_folder/'.$storage['uid'].'/'.($folder ? $folder['id'].'/' : '').'?'.http_build_query(['url' => $return_url]); ?>" title="<?php echo __('New folder'); ?>" style="background-color:transparent;border:none;margin:0 .125rem;padding:.125rem;"><img src="<?php echo $config['static_url']; ?>/assets/img/icon_plus.svg"></a>
<button class="btn btn-secondary" id="upload-btn" style="background-color:transparent;border:none;margin:0 .125rem;padding:.125rem .25rem;" title="<?php echo __('Upload'); ?>"><img src="<?php echo $config['static_url']; ?>/assets/img/icon_upload.svg"></button>
<?php if ($folder_file_count) { ?>
<a class="btn btn-secondary" href="<?php echo $config['base_url'].'/storage/download/'.$storage['uid'].'/'.($folder ? $folder['id'].'/' : ''); ?>" id="download-btn" style="background-color:transparent;border:none;margin:0 .125rem;padding:.125rem .25rem;" title="<?php echo __('Download'); ?>"><img src="<?php echo $config['static_url']; ?>/assets/img/icon_download.svg"></a>
<?php if ($full_permission) { ?>
<a class="btn btn-secondary" href="<?php echo $config['base_url'].'/storage/import_attributes/'.$storage['uid'].'/'.($folder ? $folder['id'].'/' : '').'?'.http_build_query(['url' => $return_url]); ?>" style="background-color:transparent;border:none;margin:0 .125rem;padding:.125rem .25rem;" title="<?php echo __('Import file attributes'); ?>"><img src="<?php echo $config['static_url']; ?>/assets/img/icon_import.svg"></a>
<?php } ?>
<a class="btn btn-secondary" href="<?php echo $config['base_url'].'/storage/export/'.$storage['uid'].'/'.($folder ? $folder['id'].'/' : ''); ?>" id="export-btn" style="background-color:transparent;border:none;margin:0 .125rem;padding:.125rem .25rem;" title="<?php echo __('Export file attributes to xlsx'); ?>"><img src="<?php echo $config['static_url']; ?>/assets/img/icon_export.svg"></a>
<?php if ($full_permission) { ?>
<a class="btn btn-secondary" href="<?php echo $config['base_url'].'/storage/reindex/'.$storage['uid'].'/?'.http_build_query(['url' => $return_url]); ?>" style="background-color:transparent;border:none;margin:0 .125rem;padding:.125rem .25rem;" title="<?php echo __('Update search indexes'); ?>"><img src="<?php echo $config['static_url']; ?>/assets/img/icon_search.svg"></a>
<?php } ?>
<?php } ?>
<?php } ?>
<div class="dropdown"><button aria-expanded="false" class="btn btn-secondary" data-bs-toggle="dropdown" style="background-color:transparent;border:none;margin:.125rem;padding:.125rem;" title="<?php echo __('Show items as list or icons'); ?>"><img src="<?php echo $config['static_url']; ?>/assets/img/icon_<?php echo $view_mode ? 'grid' : 'list'; ?>.svg" style="height:24px;"></button><ul class="dropdown-menu toolbar-dropdown-menu"><li><a class="dropdown-item toolbar-dropdown-item<?php if (!$view_mode) { ?> active<?php } ?>" href="<?php echo $config['base_url'].'/storage/'.$storage['uid'].'/'.($folder ? $folder['id'].'/' : '').'?'.http_build_query(['sort' => $sort_idx, 'desc' => $sort_desc]); ?>"><?php echo __('as List'); ?></a></li><li><a class="dropdown-item toolbar-dropdown-item<?php if ($view_mode) { ?> active<?php } ?>" href="<?php echo $config['base_url'].'/storage/'.$storage['uid'].'/'.($folder ? $folder['id'].'/' : '').'?'.http_build_query(['sort' => $sort_idx, 'desc' => $sort_desc, 'view_mode' => 1]); ?>"><?php echo __('as Icons'); ?></a></li></ul></div>

<div class="dropdown"><button aria-expanded="false" class="btn btn-secondary" data-bs-toggle="dropdown" style="background-color:transparent;border:none;margin:.125rem;padding:.125rem;" title="<?php echo __('Change the sort'); ?>"><img src="<?php echo $config['static_url']; ?>/assets/img/icon_sort.svg" style="height:24px;"></button><ul class="dropdown-menu toolbar-dropdown-menu"><li><a class="dropdown-item toolbar-dropdown-item<?php if (0 === $sort_idx) { ?> active<?php } ?>" href="<?php echo call_user_func($sort_url, 0, 0 === $sort_idx); ?>"><?php echo __('Filename'); ?></a></li><?php
if (!empty($storage['attributes'])) {
    foreach ($storage['attributes'] as $i => $v) {
        ?><li><a class="dropdown-item toolbar-dropdown-item<?php if ($sort_idx === $i + 3) { ?> active<?php } ?>" href="<?php echo call_user_func($sort_url, $i + 3, $sort_idx === $i + 3); ?>"><?php echo $v['title']; ?></a></li><?php
    }
} ?></ul></div>
<?php if ($edit_permission && !$folder) { ?>
<a class="btn btn-secondary" href="<?php echo $config['base_url'].'/storage/settings/'.$storage['uid'].'/?'.http_build_query(['url' => $_SERVER['REQUEST_URI']]); ?>" style="background-color:transparent;border:none;margin:.125rem;padding:.125rem;" title="<?php echo __('File Storage Settings'); ?>"><img src="<?php echo $config['static_url']; ?>/assets/img/icon_settings.svg"></a>
<?php } ?>
<?php if ($full_permission && !$folder) { ?>
<a class="btn btn-secondary" href="<?php echo $config['base_url'].'/storage/log/'.$storage['uid'].'/?'.http_build_query(['url' => $_SERVER['REQUEST_URI']]); ?>" style="background-color:transparent;border:none;margin:.125rem;padding:.125rem;" title="<?php echo __('File Storage Log'); ?>"><img src="<?php echo $config['static_url']; ?>/assets/img/icon_log.svg"></a>
<?php } ?>
<?php if ($edit_permission) { ?>
<?php if ($view_mode) { ?>
	<div style="font-size:.9rem;margin:0 0 0 1rem;"><input class="form-check-input js-select-all" id="select-all" type="checkbox">
	<label class="form-check-label" for="select-all"><?php echo __('Select all'); ?></label></div>
<?php } ?>
<button class="action-btn btn btn-secondary" id="action_move" style="background-color:transparent;display:none;border:none;margin:.125rem;padding:.125rem .25rem;" title="<?php echo __('Move selected files'); ?>"><img src="<?php echo $config['static_url']; ?>/assets/img/icon_move_file.svg"></button><button class="action-btn btn btn-secondary" id="action_delete" style="background-color:transparent;display:none;border:none;margin:.125rem;padding:.125rem .125rem;" title="<?php echo __('Delete selected files'); ?>"><img src="<?php echo $config['static_url']; ?>/assets/img/icon_trash.svg"></button><?php } ?>
<a class="d-none btn btn-primary" href="#" id="upload-cancel" style="background-color:transparent;border:none;margin:.125rem;padding:.125rem;" title="<?php echo __('Cancel uploading'); ?>"><img src="<?php echo $config['static_url']; ?>/assets/img/icon_cross_accent.svg" style="height:24px;"></a>
	</div>
	<div style="-ms-flex-align:center;-ms-flex-flow:row wrap;align-items:center;flex-flow:row wrap;display:-ms-flexbox;display:flex;">
<form action="<?php echo $config['base_url'].'/storage/search/'.$storage['uid'].'/'.($folder ? $folder['id'].'/' : '').'?'.http_build_query(['url' => $return_url]); ?>" class="search-field">
<img src="<?php echo $config['static_url']; ?>/assets/img/icon_search.svg">
<input class="form-control search-field__control" maxlength="200" name="q" placeholder="<?php echo __('Search'); ?>" type="text" value="<?php echo e($search_query); ?>">
<?php if ($search_query) { ?><a href="<?php echo $clear_search_url; ?>" title="<?php echo __('Clear'); ?>"><img src="<?php echo $config['static_url']; ?>/assets/img/icon_cross.svg"></a><?php } ?>
</form>
		<div class="page-header__pager" id="compact-pager-container">
<?php pager_compact($page, $count, $page_size, $pager_url); ?>
		</div>
	</div>
</div>

<h1 class="page-title"><?php echo $title; ?></h1>

<?php if ($view_mode) {
    include '_file_grid.php';
} else {
    include '_file_list.php';
} ?>

<div class="item-count" id="list-item-count"><?php printf(_n('%d item', '%d items', $count), $count); ?></div>
<div id="pager-container"><?php pager($page, $count, $page_size, $pager_url); ?></div>

	</div>
</div>
<div class="footer">
	<div class="footer__inner" style="justify-content: center;">
		<div class="footer__col" style="text-align: center; flex: 0 0 auto; max-width: none;">
			<div>Электрозаводская улица 52 стр 8</div>
			<div style="margin: 0.5rem 0;"><a href="tel:+74952606776">+7 (495) 260-67-76</a></div>
			<div><a href="mailto:print@projekt-24.ru">print@projekt-24.ru</a></div>
		</div>
	</div>
</div>
<script src="<?php echo $config['static_url']; ?>/assets/popper.min.js?<?php echo $config['assets_ver']['main'] ?? ''; ?>"></script>
<script src="<?php echo $config['static_url']; ?>/assets/bootstrap.js?<?php echo $config['assets_ver']['main'] ?? ''; ?>"></script>
<script src="<?php echo $config['static_url']; ?>/assets/react.production.min.js?<?php echo $config['assets_ver']['main'] ?? ''; ?>"></script>
<script src="<?php echo $config['static_url']; ?>/assets/react-dom.production.min.js?<?php echo $config['assets_ver']['main'] ?? ''; ?>"></script>
<script src="<?php echo $config['static_url']; ?>/assets/filelist.js?<?php echo $config['assets_ver']['main'] ?? ''; ?>"></script>
<script src="<?php echo $config['static_url']; ?>/assets/help.js?<?php echo $config['assets_ver']['main'] ?? ''; ?>"></script>
<script src="<?php echo $config['static_url']; ?>/assets/language_switch.js?<?php echo $config['assets_ver']['main'] ?? ''; ?>"></script>
<script src="<?php echo $config['static_url']; ?>/assets/lazyload.js?<?php echo $config['assets_ver']['main'] ?? ''; ?>"></script>
<script src="<?php echo $config['static_url']; ?>/assets/menu.js?<?php echo $config['assets_ver']['main'] ?? ''; ?>"></script>
<script src="<?php echo $config['static_url']; ?>/assets/move_files.js?<?php echo $config['assets_ver']['main'] ?? ''; ?>"></script>
<script src="<?php echo $config['static_url']; ?>/assets/tingle.min.js?<?php echo $config['assets_ver']['main'] ?? ''; ?>"></script>
<script>!new Menu()</script>

<?php echo '<script>!new Help('.json_encode([
    'base_url' => $config['base_url'] . '/help/',
    'language' => $lang,
    'return_url' => $return_url,
    'title' => !empty($title) ? sprintf('%s  — %s', e($title), e($config['site_name'])) : e($config['site_name']),
], JSON_UNESCAPED_UNICODE).')</script>'; ?>
<script>var lazyLoadInstance=new LazyLoad({elements_selector: '.lazyload'})</script>
<?php echo '<script>!new Filelist('.json_encode([
    'can_edit' => $edit_permission,
    'clear_filter_url' => $clear_filter_url,
    'delete_url' => $config['base_url'] . '/storage/delete/'.$storage['uid'].'/',
    'language' => $lang,
    'return_url' => $return_url,
    'upload_concurency' => $config['upload']['frontend_concurency'],
    'upload_timeout' => $config['upload']['frontend_upload_timeout'] ?? 0,
    'upload_url' => $config['base_url'] . '/storage/upload/'.$storage['uid'].'/'.($folder ? $folder['id'].'/' : ''),
], JSON_UNESCAPED_UNICODE).')</script>'; ?>
<?php if ($edit_permission) { ?>
<?php echo '<script>!new MoveFiles('.json_encode([
    'folder_id' => $folder['id'] ?? null,
    'folder_select_url' => $config['base_url'].'/storage/folder_select/'.$storage['uid'].'/',
    'language' => $lang,
    'move_url' => $config['base_url'].'/storage/move/'.$storage['uid'].'/',
    'return_url' => $return_url,
], JSON_UNESCAPED_UNICODE).')</script>'; ?>
    <?php }?>
    </body>
</html>