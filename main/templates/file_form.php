<?php $title = __('Edit file attributes');

if ($search_query) {
    $breadcrumbs = [$return_url => __('Search results'), '' => $title];
} else {
    $breadcrumbs = [
        $config['base_url'] . '/storage/' . $storage['uid'] . '/?' . http_build_query(['sort' => $sort_idx, 'desc' => $sort_desc, 'view_mode' => $view_mode]) => $storage['title'],
    ];

    if ($breadcrumbs_data) {
        foreach ($breadcrumbs_data as $v) {
            $breadcrumbs[$config['base_url'] . '/storage/' . $storage['uid'] . '/' . $v['id'] . '/?' . http_build_query(['sort' => $sort_idx, 'desc' => $sort_desc, 'view_mode' => $view_mode])] = $v['name'];
        }
    }

    $breadcrumbs[''] = $title;
} ?>
<?php include_once '_helpers.php' ?>
<?php if (!empty($pager[0]) || !empty($pager[1])) {
    ob_start() ?>
<div class="pager-compact" style="float: right;"><?=(!empty($pager[1]) ? '<a class="pager-compact__item js-pager-item js-pager-item-prev" href="' . (function () {
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
<?php	$breadcrumbs_after = ob_get_clean();
} ?>
<?php include '_header.php' ?>
<?php if (!empty($form_errors)) {
    error_message($form_errors);
} ?>
<div class="container-sm">
<?php include '_file_form.php' ?>
</div>
<?php include '_footer.php' ?>