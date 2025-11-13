<?php $title = __('Users'); ?>
<?php include_once '_helpers.php'; ?>
<?php include '_header.php'; ?>
<div class="page-header">
	<div>
		<a href="<?php echo $config['base_url'].'/admin/users/new?'.http_build_query(['url' => $return_url]); ?>" title="<?php echo __('New User'); ?>"><img src="<?php echo $config['static_url']; ?>/assets/img/icon_plus.svg" style="margin: .25rem .5rem .25rem 0;"></a>
		<button class="js-action-btn" id="action_delete" style="background-color: transparent; border: none; display: none; padding: .25rem .5rem .25rem 0;" title="<?php echo __('Delete selected users'); ?>"><img src="<?php echo $config['static_url']; ?>/assets/img/icon_trash.svg"></button>
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
				<th class="column-header"><?php datatable_sort_link($config, 0, 'Email', __('Order by email'), $sort, $sort_desc, $sort_url); ?></th>
				<th class="column-header"><?php datatable_sort_link($config, 1, __('Full name'), __('Order by full name'), $sort, $sort_desc, $sort_url); ?></th>
				<th class="column-header"><?php datatable_sort_link($config, 2, __('User permissions'), __('Order by user permissions'), $sort, $sort_desc, $sort_url); ?></th>
				<th class="column-header"><?php echo __('Description'); ?></th>
				<th class="column-header"><?php datatable_sort_link($config, 3, __('Active'), __('Order by active'), $sort, $sort_desc, $sort_url); ?></th>
			</tr>
		</thead>
		<tbody>
<?php foreach ($data as $row) { ?>
			<tr>
				<td><div class="datatable-actions"><input class="datatable-actions-checkbox form-check-input js-action-checkbox" name="id[]" title="<?php echo __('Select User'); ?>" type="checkbox" value="<?php echo e($row['id']); ?>"><a class="btn btn-secondary image-btn" href="<?php echo $config['base_url'].'/admin/users/edit?'.http_build_query(['id' => $row['id'], 'url' => $return_url]); ?>" title="<?php echo __('Edit User'); ?>"><img src="<?php echo $config['static_url']; ?>/assets/img/icon_pencil.svg"></a></div></td>
				<td><?php echo e($row['email']); ?></td>
				<td><?php echo e($row['name']); ?></td>
				<td><?php echo e($row['roles']); ?></td>
				<td><?php echo \Michelf\MarkdownExtra::defaultTransform($row['description'] ?? ''); ?></td>
				<td><?php echo $row['active']; ?></td>
			</tr>
<?php	} ?>
		</tbody>
	</table>
</div>
<div class="item-count"><?php printf(_n('%d user', '%d users', $count), $count); ?></div>
<?php pager($page, $count, $page_size, $pager_url); ?>
<form action="<?php echo $config['base_url'].'/admin/users/action'; ?>" class="d-none js-action-form" method="post">
<input id="js-action-input" name="action" type="hidden">
<input name="url" type="hidden" value="<?php echo e($return_url); ?>">
</form>
<?php ob_start(); ?>
<script>
var listAction = new ListAction({confirmation: {'action_delete': '<?php echo __('Delete selected users?'); ?>'}, message: '<?php echo __('Select users'); ?>'});
(function() {
	$('.js-action-checkbox').change(listAction.change);
	$('.js-select-all').change(function() { $('.js-action-checkbox').prop('checked', this.checked); listAction.change(); });
})();
</script>
<?php !isset($footer_js) && $footer_js = ''; ?>
<?php $footer_js .= ob_get_clean(); ?>
<?php include '_footer.php'; ?>