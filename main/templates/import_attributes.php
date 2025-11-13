<?php $title = __('Import files attributes') ?>
<?php $breadcrumbs = [
    $config['base_url'] . '/storage/' . $storage['uid'] . '/' => $storage['title'],
];

if ($breadcrumbs_data) {
    foreach ($breadcrumbs_data as $v) {
        $breadcrumbs[$config['base_url'] . '/storage/' . $storage['uid'] . '/' . $v['id'] . '/'] = $v['name'];
    }
}

$breadcrumbs[''] = $title; ?>
<?php include_once '_helpers.php' ?>
<?php include '_header.php' ?>
<?php if (!empty($form_errors)) {
    error_message($form_errors);
} ?>
<form action="<?=$_SERVER['REQUEST_URI']?>" class="g-3 row" enctype="multipart/form-data" method="post" style="margin:0 auto;max-width:768px;">
<div class="col-sm"><label class="form-label" for="file"><?=__('Select CSV or XLSX file to import attributes')?></label><input class="form-control<?=!empty($form_errors['file']) ? ' is-invalid' : ''?>" type="file" name="file"><? if (!empty($form_errors['file'])) { ?><div class="invalid-feedback"><?=$form_errors['file']?></div><? } ?></div>
<?php render_field([
    'class' => 'form-control',
    'label_class' => 'form-label',
    'group_class' => 'col-sm-auto',
    'id' => 'csv_delimiter',
    'label' => __('CSV field delimiter'),
    'name' => 'csv_delimiter',
    'type' => 'text',
    'size' => 3,
    'value' => $form_data['csv_delimiter'] ?? '',
], $form_errors) ?>
<div><button type="submit" class="btn btn-primary"><?=__('Upload file to import attributes')?></button></div>
</form>
<?php include '_footer.php' ?>