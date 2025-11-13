<?php
include_once '_helpers.php';
$title = __('Edit file attributes');
$hide_header = true;
$hide_footer = true;
include '_header.php';
?>
<div class="file-form-image-viewer">
	<div class="file-form-image-viewer-image"><img class="lazyload" data-src="<?=rtrim($config['upload']['url'], '/') . '/' . $storage['uid'] . '/' . $data['file']?>"></div>
	<div class="file-form-image-viewer-sidebar">
<div class="page-header" style="margin: 0 0 1rem;">
	<a href="<?=$return_url?>"><?=__('← Back')?></a>
<?php if (!empty($pager[0]) || !empty($pager[1])) { ?>
<div class="pager-compact"><?=(!empty($pager[1]) ? '<a class="pager-compact__item js-pager-item js-pager-item-prev" href="' . (function () {
    $v = $_SERVER['REQUEST_URI'] ?? '';
    if (($p = strpos($v, '?')) !== false) {
        $v = substr($v, 0, $p);
    }
    return $v;
})() . '?' . http_build_query(['desc' => $sort_desc, 'id' => $data['id'], 'prev' => 1, 'sort' => $sort_idx, 'url' => $return_url]) . '" title="' . _x('Previous (Ctrl-Alt-Left arrow)', 'file_form') . '">❮</a>' : '<span class="pager-compact__item pager-compact__item--disabled">❮</span>')?><?=(!empty($pager[0]) ? '<a class="pager-compact__item js-pager-item js-pager-item-next" href="' . (function () {
    $v = $_SERVER['REQUEST_URI'] ?? '';
    if (($p = strpos($v, '?')) !== false) {
        $v = substr($v, 0, $p);
    }
    return $v;
})() . '?' . http_build_query(['desc' => $sort_desc, 'id' => $data['id'], 'next' => 1, 'sort' => $sort_idx, 'url' => $return_url]) . '" title="' . _x('Previous (Ctrl-Alt-Right arrow)', 'file_form') . '">❯</a>' : '<span class="pager-compact__item pager-compact__item--disabled">❯</span>')?></div>
<?php } ?>
</div>
<?php if (!empty($form_errors)) {
	error_message($form_errors);
} ?>
<?php include '_file_form.php' ?>
	</div>
</div>
<?php include '_footer.php' ?>