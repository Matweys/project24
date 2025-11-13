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
<form action="<?=$_SERVER['REQUEST_URI']?>" enctype="multipart/form-data" method="post">
<?php if ($sample_data) {
    echo '<div style="overflow-x:auto;"><table class="import-attributes-datatable">';
    echo '<tr>';
    foreach ($sample_data[0] as $i => $v) {
        echo '<td><select class="import-attributes-select" name="columns_attributes-' . $i .'-attribute"><option value="">' . __('Select a storage attribute match') . '</option>' . array_reduce(
        	array_keys($columns_storage_attributes),
			function ($r, $k) use ($columns_storage_attributes, $form_data, $i) {
                $v = $columns_storage_attributes[$k];
                $r .= sprintf('<option value="%s"%s>%s</option>', $k, (($form_data['columns_attributes'][$i]['attribute'] ?? null) === (string) $k ? ' selected' : ''), $v);
                return $r;
            },
            ''
        ) . '</select></td>';
    }
    echo '</tr>';

    foreach ($sample_data as $i => $row) {
        echo '<tr>';
        foreach ($row as $j => $v) {
            echo '<td>' . e($v) . '</td>';
        }
        echo '</tr>';
    }
    echo '</table></div>';
} ?>
<div class="import-attributes-container">
<?php render_checkbox([
    'checked' => !empty($form_data['basename_match']),
    'class' => 'form-check-input',
    'id' => 'basename_match',
    'label' => __('Search for file matches using a base filename instead of a full filename'),
    'name' => 'basename_match',
    'type' => 'checkbox',
    'value' => 1,
], $form_errors) ?>
<a class="btn btn-secondary" href="<?=$config['base_url'] . '/storage/import_attributes/' . $storage['uid'] . '/?' . http_build_query(['url' => $return_url])?>" title="<?=__('Back')?>"><?=__('Back')?></a>
<button type="submit" class="btn btn-primary"><?=__('Import files attributes')?></button>
</div>
</form>
<?php include '_footer.php' ?>