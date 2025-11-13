<?php $edit_permission = in_array($storage['permission_name'] ?? null, ['edit', 'full']) ?>
<?php include '_datatable_sort_link.php' ?>
<?php include '_datatable_icons.php' ?>
<div id="datatable-container" style="overflow-x:auto;">
	<table class="datatable">
		<thead>
			<tr>
<?php if ($edit_permission) { ?>
				<th><input class="datatable-actions-checkbox form-check-input" id="select-all" title="<?=__('Select all')?>" type="checkbox"></th>
<?php } ?>
				<th><?php datatable_sort_link($config, 0, __('Filename'), __('Order by filename'), $sort_idx, (bool) $sort_desc, $sort_url) ?></th>
<?php if (!empty($storage['attributes'])) {
	foreach ($storage['attributes'] as $i => $v) { ?>
				<th><?php datatable_sort_link($config, $i + 3, $v['title'], sprintf(__('Order by %s'), $v['title']), $sort_idx, (bool) $sort_desc, $sort_url) ?></th>
<?php	}
	} ?>
			</tr>
<?php include '_datatable_filter.php' ?>
		</thead>
		<tbody class="js-datatable-file-list">
<?php foreach ($data ?: [] as $row) { ?>
			<tr>
<?php if ($edit_permission) { ?>
				<td><div class="datatable-actions"><input class="datatable-actions-checkbox form-check-input" name="id[]" title="<?=__('Select file')?>" type="checkbox" value="<?=e($row['id'])?>"><a class="btn btn-secondary image-btn" href="<?=$config['base_url'] . ($row['folder'] ? '/storage/edit_folder/' : '/storage/edit/') . $storage['uid'] . '?' . http_build_query(['id' => $row['id'], 'sort' => $sort_idx, 'desc' => $sort_desc, 'view_mode' => $view_mode, 'url' => $return_url])?>" title="<?=$row['folder'] ? __('Rename folder') : __('Edit file attributes')?>"><img src="<?=$config['static_url']?>/assets/img/icon_pencil.svg"></a></div></td>
<?php } ?>
<?php if ($row['folder']) { ?>
				<td><a href="<?=$config['base_url'] . '/storage/' . $storage['uid'] . '/' . $row['id'] . '/?' . http_build_query(['sort' => $sort_idx, 'desc' => $sort_desc, 'url' => $return_url, 'view_mode' => $view_mode])?>"><img class="datatable-file-icon" src="<?=$config['static_url']?>/assets/img/icon_folder.svg"><?=e($row['name'])?></a></td>
<?php } else { ?>
				<td><a href="<?=rtrim($upload_config['url'], '/') . '/' . $row['file']?>" target="_blank"><img class="datatable-file-icon" src="<?=$config['static_url']?>/assets/img/icon_file.svg"><?=e($row['name'])?></a></td>
<?php } ?>
<?php if (!empty($storage['attributes'])) {
	foreach ($storage['attributes'] as $i => $v) {
		$field_name = 'a' . $v['id'];

		switch ($v['type_name'] ?? '') {
			case 'date':
				$value = datetime_format($row[$field_name] ?? null, 'dd.MM.yyyy');
				break;

			case 'datetime':
				$value = datetime_format($row[$field_name] ?? null, 'dd.MM.yyyy H:mm');
				break;

			default:
				$value = $row[$field_name] ?? null;
				break;
		} ?>
				<td><?=$value?></td>
<?php
	}
} ?>
			</tr>
<?php } ?>
		</tbody>
	</table>
</div>
