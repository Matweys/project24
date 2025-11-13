<?php $title = __('File Storages'); ?>
<?php include_once '_helpers.php'; ?>
<?php include '_header.php'; ?>
<div class="page-header">
	<div>
		<a href="<?php echo $config['base_url'].'/admin/storages/new?'.http_build_query(['url' => $return_url]); ?>" title="<?php echo __('New File Storage'); ?>"><img src="<?php echo $config['static_url']; ?>/assets/img/icon_plus.svg" style="margin: .25rem .5rem .25rem 0;"></a>
		<button class="js-action-btn" id="action_delete" style="background-color: transparent; border: none; display: none; padding: .25rem .5rem .25rem 0;" title="<?php echo __('Delete selected File Storages'); ?>"><img src="<?php echo $config['static_url']; ?>/assets/img/icon_trash.svg"></button>
	</div>
	<div style="-ms-flex-align: center; -ms-flex-flow: row wrap; align-items: center; flex-flow: row wrap; display: -ms-flexbox; display: flex;">
<form action="<?php echo $return_url; ?>" class="search-field">
<img src="<?php echo $config['static_url']; ?>/assets/img/icon_search.svg">
<input class="form-control search-field__control" maxlength="200" name="q" placeholder="<?php echo __('Search'); ?>" type="text" value="<?php echo e($search_query); ?>">
<?php if ($search_query) { ?><a href="<?php echo $clear_search_url; ?>" title="<?php echo __('Clear'); ?>"><img src="<?php echo $config['static_url']; ?>/assets/img/icon_cross.svg"></a><?php } ?>
</form>
<?php pager_compact($page, $count, $page_size, $pager_url); ?>
	</div>
</div>
<h1 class="page-title"><?php echo $title; ?></h1>
<?php include '_datatable_sort_link.php'; ?>
<?php include '_datatable_icons.php'; ?>
<div style="overflow-x:auto;">
	<table class="datatable2">
		<thead>
			<tr>
				<th><input class="datatable-actions-checkbox form-check-input js-select-all" title="<?php echo __('Select all'); ?>" type="checkbox"></th>
				<th class="column-header"><?php datatable_sort_link($config, 0, 'ID', __('Order by ID'), $sort, $sort_desc, $sort_url); ?></th>
				<th class="column-header"><?php datatable_sort_link($config, 1, __('Title'), __('Order by Title'), $sort, $sort_desc, $sort_url); ?></th>
				<th class="column-header"><?php echo __('Description'); ?></th>
				<th class="column-header"><?php datatable_sort_link($config, 2, __('Users'), __('Order by User'), $sort, $sort_desc, $sort_url); ?></th>
				<th class="column-header"><?php datatable_sort_link($config, 3, __('Size'), __('Order by Size'), $sort, $sort_desc, $sort_url); ?></th>
			</tr>
		</thead>
		<tbody>
<?php foreach ($data as $row) { ?>
			<tr>
				<td><div class="datatable-actions"><input class="datatable-actions-checkbox form-check-input js-action-checkbox" name="id[]" title="<?php echo __('Select File Storage'); ?>" type="checkbox" value="<?php echo e($row['id']); ?>"><a class="btn btn-secondary image-btn" href="<?php echo $config['base_url'].'/admin/storages/edit?'.http_build_query(['id' => $row['id'], 'url' => $return_url]); ?>" title="<?php echo __('Edit File Storage'); ?>"><img src="<?php echo $config['static_url']; ?>/assets/img/icon_pencil.svg"></a></div></td>
				<td><?php echo e($row['id']); ?></td>
				<td><?php echo e($row['title']); ?></td>
				<td><?php echo \Michelf\MarkdownExtra::defaultTransform($row['description'] ?? ''); ?></td>
				<td><?php echo e($row['users']); ?></td>
				<td><?php echo file_size_format($row['size']); ?></td>
			</tr>
<?php	} ?>
		</tbody>
	</table>
</div>
<div class="item-count"><?php printf(_n('%d storage', '%d storages', $count), $count); ?></div>
<?php pager($page, $count, $page_size, $pager_url); ?>
<form action="<?php echo $config['base_url'].'/admin/storages/action'; ?>" class="d-none js-action-form" method="post">
<input id="js-action-input" name="action" type="hidden">
<input name="url" type="hidden" value="<?php echo e($return_url); ?>">
</form>
<?php ob_start(); ?>
<script>
var listAction = new ListAction({confirmation: {'action_delete': '<?php echo __('Delete selected File Storages?'); ?>'}, message: '<?php echo __('Select File Storages'); ?>'});
(function() {
	$('.js-action-checkbox').change(listAction.change);
	$('.js-select-all').change(function() { $('.js-action-checkbox').prop('checked', this.checked); listAction.change(); });
})();
</script>
<?php !isset($footer_js) && $footer_js = ''; ?>
<?php $footer_js .= ob_get_clean(); ?>
<?php include '_footer.php'; ?>