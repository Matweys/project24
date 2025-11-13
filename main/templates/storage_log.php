<?php $title = __('Storage log'); ?>
<?php $breadcrumbs = [$return_url => $storage['title'], '' => $title]; ?>
<?php include_once '_helpers.php'; ?>
<?php include '_header.php'; ?>
<div class="page-header">
	<div style="-ms-flex-align: center; -ms-flex-flow: row wrap; align-items: center; flex-flow: row wrap; display: -ms-flexbox; display: flex;">
<form action="<?php echo $search_url; ?>" class="search-field">
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
				<th class="column-header"><?php datatable_sort_link($config, 0, __('Timestamp'), __('Order by timestamp'), $sort, $sort_desc, $sort_url); ?></th>
				<th class="column-header"><?php echo __('Message'); ?></th>
			</tr>
		</thead>
		<tbody>
<?php foreach ($data as $row) { ?>
			<tr>
				<td><?php echo e($row['created']); ?></td>
				<td><pre style="white-space:pre-wrap;"><?php echo e($row['message'] ?? ''); ?></pre></td>
			</tr>
<?php	} ?>
		</tbody>
	</table>
</div>
<div class="item-count"><?php printf(_n('%d message', '%d messages', $count), $count); ?></div>
<?php pager($page, $count, $page_size, $pager_url); ?>
<?php ob_start(); ?>
<?php !isset($footer_js) && $footer_js = ''; ?>
<?php $footer_js .= ob_get_clean(); ?>
<?php include '_footer.php'; ?>