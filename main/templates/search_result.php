<?php $title = $folder['name'] ?? $storage['title'] ?>
<?php include_once '_helpers.php' ?>
<?php include '_header.php' ?>
<?php $edit_permission = in_array($storage['permission_name'] ?? null, ['edit', 'full']) ?>
<?php $full_permission = ($storage['permission_name'] ?? null) === 'full' ?>
<div class="page-header">
	<form action="<?=$config['base_url'] . '/storage/search/' . $storage['uid'] . '/' . ($folder ? $folder['id'] . '/' : '') . '?' . http_build_query(['url' => $return_url])?>" class="search-field">
	<img src="<?=$config['static_url']?>/assets/img/icon_search.svg">
	<input class="form-control search-field__control" maxlength="200" name="q" placeholder="<?=__('Search')?>" type="text" value="<?=e($search_query)?>">
	</form>
	<div class="pager-compact-container" id="compact-pager-container"><?php pager_compact($page, $count, $page_size, $pager_url) ?></div>
</div>
<div style="-ms-flex-align:center;-ms-flex-flow:row wrap;align-items:center;flex-flow:row wrap;display:-ms-flexbox;display:flex;margin:.5rem 0 0;"><?php if ($folder) { ?><div style="margin:0 .5rem 0 0;">Поиск:</div><div class="btn-group"><a href="<?=$config['base_url'] . '/storage/search/' . $storage['uid'] . '/' . ($folder ? $folder['id'] . '/' : '') . '?' . http_build_query(['q' => $search_query])?>" class="btn<?php if (! $search_mode) { ?> btn-secondary active<?php } else { ?> btn-light<?php } ?>"<?php if (! $search_mode) { ?> aria-current="page"<?php } ?>><?=$folder['name']?></a><a href="<?=$config['base_url'] . '/storage/search/' . $storage['uid'] . '/' . ($folder ? $folder['id'] . '/' : '') . '?' . http_build_query(['mode' => 1, 'q' => $search_query])?>" class="btn<?php if ($search_mode) { ?> btn-secondary active<?php } else { ?> btn-light<?php } ?>"<?php if ($search_mode) { ?> aria-current="page"<?php } ?>><?=$storage['title']?></a></div><?php } ?><div style="margin:0 0 0 .5rem;"><?=sprintf(_n('About %d result.', 'About %d results.', $meta['total_found']), $meta['total_found'])?></div></div>
<?php foreach ($data as $row) { ?>
<div class="search-result-item row gx-2 gy-3" style="align-items:center;">
<?php if ($edit_permission) { ?>
	<div class="col-auto"><a class="btn btn-secondary image-btn" href="<?=$config['base_url'] . '/storage/edit/' . $storage['uid'] . '?' . http_build_query(['id' => $row['id'], 'q' => $search_query, 'url' => $return_url])?>" title="<?=__('Edit file attributes')?>"><img src="<?=$config['static_url']?>/assets/img/icon_pencil.svg"></a></div>
<?php } ?>		
	<div class="col-auto"><a href="<?=(rtrim($upload_config['url'], '/') . '/' . $row['file'])?>" target="_blank"><img src="<?=$config['static_url']?>/assets/img/icon_file.svg"></a></div>
	<div class="col"><h3 class="search-result-item-title"><a href="<?=(rtrim($upload_config['url'], '/') . '/' . $row['file'])?>" target="_blank"><?=e($row['name'])?></a></h3></div>
	<div><?=nl2p($row['snippet'])?></div>
</div>
<?php } ?>
<?php pager($page, $count, $page_size, $pager_url) ?>
<form action="<?=$config['base_url'] . '/storage/action/' . $storage['uid'] . '/'?>" class="js-action-form d-none" method="post">
<input id="js-action-input" name="action" type="hidden">
<input name="url" type="hidden" value="<?=e($return_url)?>">
</form>
<?php include '_footer.php' ?>