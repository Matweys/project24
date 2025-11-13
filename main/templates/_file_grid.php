<?php $edit_permission = in_array($storage['permission_name'] ?? null, ['edit', 'full']) ?>
<div class="file-grid">
	<div class="file-grid__inner js-file-grid">
<?php foreach ($data ?: [] as $item) { ?>
		<div class="file-grid-item"><?php
	if ($item['folder']) {
		?><a href="<?=$config['base_url'] . '/storage/' . $storage['uid'] . '/' . $item['id'] . '/?' . http_build_query(['sort' => $sort_idx, 'desc' => $sort_desc, 'url' => $return_url, 'view_mode' => $view_mode])?>"><img class="file-grid-icon" src="<?=$config['static_url']?>/assets/img/file_folder.svg"></a><?php
	} else {
		?><a href="<?=rtrim($upload_config['url'], '/') . '/' . $item['file']?>"><?php
		if ($item['image']) {
			?><img class="lazyload" data-src="<?=rtrim($upload_config['url'], '/') . '/' . $item['image']?>"><?php
		} elseif ($item['type'] === 2 && $item['mime_type'] === 'image/svg+xml') {
			?><img class="lazyload" data-src="<?=rtrim($upload_config['url'], '/') . '/' . $item['file']?>"><?php
		} elseif ($item['type'] === 1) {
			?><img class="file-grid-icon" src="<?=$config['static_url']?>/assets/img/file_pdf.svg"><?php
		} elseif ($item['type'] === 2) {
			?><img class="file-grid-icon" src="<?=$config['static_url']?>/assets/img/image.webp"><?php
		} else {
			?><img class="file-grid-icon" src="<?=$config['static_url']?>/assets/img/file2.svg"><?php
		} ?></a><?php
	}
	?><div class="file-grid-actions"><input class="file-grid-actions-checkbox form-check-input" name="id[]" title="<?=__('Select file')?>" type="checkbox" value="<?=e($item['id'])?>"><a class="btn btn-secondary image-btn" href="<?=$config['base_url'] . ($item['folder'] ? '/storage/edit_folder/' : '/storage/edit/') . $storage['uid'] . '?' . http_build_query(['id' => $item['id'], 'sort' => $sort_idx, 'desc' => $sort_desc, 'url' => $return_url, 'view_mode' => $view_mode])?>" title="<?=$item['folder'] ? __('Rename folder') : __('Edit file attributes')?>"><img src="<?=$config['static_url']?>/assets/img/icon_pencil.svg"></a></div>
<div class="file-grid-item-text"><?php
if ($item['folder']) { ?>
<a href="<?=$config['base_url'] . '/storage/' . $storage['uid'] . '/' . $item['id'] . '/?' . http_build_query(['sort' => $sort_idx, 'desc' => $sort_desc, 'url' => $return_url, 'view_mode' => $view_mode])?>"><?=e($item['name'])?></a>
<?php } else { ?>
<a href="<?=rtrim($upload_config['url'], '/') . '/' . $item['file']?>"><?=e($item['name'])?></a>
<?php }
?></div>
		</div>
<?php } ?>
	</div>
</div>
