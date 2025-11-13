<?php $title = empty($data) ? __('New User') : __('Edit User') ?>
<?php $breadcrumbs = [$return_url => __('Users'), '' => $title] ?>
<?php include_once '_helpers.php' ?>
<?php include '_header.php' ?>

<?php if (!empty($form_errors)) {
	error_message($form_errors);
} ?>

<form action="<?=$_SERVER['REQUEST_URI']?>" class="container-sm g-3 row" enctype="multipart/form-data" method="post">

<?php render_field([
	'class' => 'form-control',
	'group_class' => 'col-md-6',
	'id' => 'email',
	'label' => 'Email',
	'label_class' => 'form-label',
	'name' => 'email',
	'required' => true,
	'type' => 'text',
	'value' => $form_data['email'] ?? '',
], $form_errors) ?>

<?php render_field([
	'class' => 'form-control',
	'group_class' => 'col-md-6',
	'id' => 'password',
	'label' => __('Password'),
	'label_class' => 'form-label',
	'name' => 'password',
	'type' => 'password',
], $form_errors) ?>

<?php render_field([
	'class' => 'form-control',
	'id' => 'name',
	'label' => __('Full name'),
	'label_class' => 'form-label',
	'name' => 'name',
	'type' => 'text',
	'value' => $form_data['name'] ?? '',
], $form_errors) ?>

<?php render_field([
	'field' => sprintf(
		'<select %s>%s</select>',
		html_params([
			'data-width' => '100%',
			'id' => 'role',
			'multiple' => true,
			'name' => 'role[]',
		]),
		isset($widget_data['role']) && is_array($widget_data['role']) ? array_reduce($widget_data['role'], function ($a, $v) {
			$a .= sprintf('<option value="%s" selected>%s</option>', e($v[0]), e($v[1]));
			return $a;
		}, '') : ''
	),
	'id' => 'role',
	'label' => __('User permissions'),
	'label_class' => 'form-label',
], $form_errors) ?>

<?php render_field([
	'class' => 'form-control',
	'id' => 'description',
	'label' => __('Description'),
	'label_class' => 'form-label',
	'name' => 'description',
	'textarea' => true,
	'type' => 'text',
	'value' => $form_data['description'] ?? '',
], $form_errors) ?>

<div><?php render_checkbox([
	'class' => 'form-check-input',
	'group_class' => 'form-check form-check-inline2',
	'label_class' => 'form-label',
	'checked' => (!empty($form_data['active'])),
	'id' => 'active',
	'label' => __('Active'),
	'name' => 'active',
	'type' => 'checkbox',
	'value' => 1,
], $form_errors) ?></div>

<div><button type="submit" class="btn btn-primary"><?=__('Save')?></button></div>
</form>

<?php ob_start() ?>
<script>
$(function() {
	$('#role').select2({
		ajax: {
			cache: true,
			data: function(params) {
				return {q: params.term, page: params.page};
			},
			processResults: function(data, params) {
				return {pagination: {more: data.more}, results: data.data};
			},
			url: '<?=$config['base_url'] . '/admin/users/role_lookup'?>',
		},
	});
	$('form').areYouSure();
});
</script>
<?php !isset($footer_js) && $footer_js = '' ?>
<?php $footer_js .= ob_get_clean() ?>
<?php include '_footer.php' ?>