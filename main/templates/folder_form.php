<?php $title = empty($data) ? __('New folder') : __('Rename folder') ?>
<?php $breadcrumbs = [
    $config['base_url'] . '/storage/' . $storage['uid'] . '/?' . http_build_query(['sort' => $sort_idx, 'desc' => $sort_desc, 'view_mode' => $view_mode]) => $storage['title'],
];

if ($breadcrumbs_data) {
    foreach ($breadcrumbs_data as $v) {
        $breadcrumbs[$config['base_url'] . '/storage/' . $storage['uid'] . '/' . $v['id'] . '/?' . http_build_query(['sort' => $sort_idx, 'desc' => $sort_desc, 'view_mode' => $view_mode])] = $v['name'];
    }
}

$breadcrumbs[''] = $title; ?>
<?php include_once '_helpers.php' ?>
<?php include '_header.php' ?>
<?php if (!empty($form_errors)) {
    error_message($form_errors);
} ?>
<form action="<?=$_SERVER['REQUEST_URI']?>" class="container-sm g-3 row" enctype="multipart/form-data" method="post">

<?php render_field([
    'class' => 'form-control',
    'id' => 'name',
    'label' => __('Folder name'),
    'label_class' => 'form-label',
    'name' => 'name',
    'type' => 'text',
    'value' => $form_data['name'] ?? '',
], $form_errors) ?>

<div><button type="submit" class="btn btn-primary"><?=empty($data) ? __('Create folder') : __('Rename folder')?></button></div>
</form>
<?php ob_start() ?>
<script>
(function() {
	$('form').areYouSure({'silent': true});
}());
</script>
<?php ! isset($footer_js) && $footer_js = '' ?>
<?php $footer_js .= ob_get_clean() ?>
<?php include '_footer.php' ?>