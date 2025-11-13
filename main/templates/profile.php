<?php $title = __('Edit Profile') ?>
<?php include_once '_helpers.php' ?>
<?php include '_header.php' ?>
<h1 class="page-title"><?=__('Edit Profile')?></h1>

<?php if (!empty($form_errors)) {
	error_message($form_errors);
} ?>

<form action="<?=$_SERVER['REQUEST_URI']?>" class="js-form profile-container" enctype="multipart/form-data" method="post" style="margin-top:2rem;">

<div class="profile-columns">

<?php render_field([
	'field' => sprintf(
		'<select %s>%s</select>',
		html_params([
			'class' => 'form-select',
			'id' => 'lang',
			'name' => 'lang',
		]),
		implode('', array_map(function ($v) use ($form_data) {
			return sprintf('<option value="%s"%s>%s</option>', $v['id'], ($form_data['lang'] === $v['id'] ? ' selected' : ''), $v['title']);
		}, $languages ?? []))
	),
	'group_class' => 'profile-columns__item',
	'id' => "lang",
	'label' => __('Language'),
	'label_class' => 'form-label',
], $form_errors) ?>

<?php render_field([
	'class' => 'form-control',
	'group_class' => 'profile-columns__item',
	'id' => 'name',
	'label' => __('Full name'),
	'label_class' => 'form-label',
	'name' => 'name',
	'type' => 'text',
	'value' => $form_data['name'] ?? '',
], $form_errors) ?>

<?php render_field([
	'class' => 'form-control',
	'group_class' => 'profile-columns__item',
	'id' => 'password',
	'label' => __('To change your password, enter your current and new password. Current password'),
	'label_class' => 'form-label',
	'name' => 'password',
	'type' => 'password',
], $form_errors) ?>

<?php render_field([
	'class' => 'form-control',
	'group_class' => 'profile-columns__item',
	'id' => 'new_password',
	'label' => __('New password'),
	'label_class' => 'form-label',
	'name' => 'new_password',
	'type' => 'password',
], $form_errors) ?>

<?php render_field([
	'class' => 'form-control',
	'group_class' => 'profile-columns__item',
	'id' => 'page_size',
	'label' => __('Number of items per page'),
	'label_class' => 'form-label',
	'name' => 'page_size',
	'type' => 'text',
	'value' => $form_data['page_size'] ?? '',
], $form_errors) ?>

<?php render_checkbox([
	'checked' => (!empty($form_data['file_form_pdf_preview'])),
	'class' => 'form-check-input',
	'group_class' => 'form-check form-check-inline mb-3 profile-columns__item',
	'id' => 'file_form_pdf_preview',
	'label' => __('Show the PDF viewer on the file attribute edit page'),
	'label_class' => 'form-label',
	'name' => 'file_form_pdf_preview',
	'type' => 'checkbox',
	'value' => 1,
], $form_errors) ?>

</div>
<button type="submit" class="btn btn-primary"><?=__('Save')?></button>
</form>

<?php ob_start() ?>
<script>
$(function() {
	$('.js-form').areYouSure();
});
</script>
<?php ! isset($footer_js) && $footer_js = '' ?>
<?php $footer_js .= ob_get_clean() ?>
<?php include '_footer.php' ?>