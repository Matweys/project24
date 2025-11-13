<?php $title = __('File storage permissions') ?>
<?php $breadcrumbs = [$return_url => $storage['title'], '' => $title] ?>
<?php include_once '_helpers.php' ?>
<?php ob_start() ?>
<script>
function formfield_add(name, container) {
	'use strict'

	if (name && container) {
		var last_item = container.querySelector('.js-formfield-item:last-child'),
			prefix = name + '-0'

		if (last_item) {
			let v = last_item.querySelector('input[name^="' + name + '-"]')

			if (v) {
				v = v.getAttribute('name');

				if (v) {
					v = v.split('-');
					v = parseInt(v[v.length - 2], 10) + 1;

					if (v) {
						prefix = name + '-' + v;
					}
				}
			}
		}

		var ff = window['formfield_' + name];

		if (ff) {
			let div = document.createElement('div')
			div.innerHTML = ff
			ff = div.childNodes[0]
			container.appendChild(ff)

			Array.prototype.forEach.call(ff.querySelectorAll('[name]'), (el) => {
				if (el.id) {
					el.setAttribute('id', prefix + '-' + el.id)
				}

				var name = el.getAttribute('name')

				if (name) {
					el.setAttribute('name', prefix + '-' + name)
				}
			})

			let v = ff.querySelector('select,input,textarea')

			if (v) {
				v.focus()
			}
		}
	}
}
</script>
<?php ob_start() ?>
<div class="js-formfield-item row row-cols-lg-auto g-3 align-items-center">
<input type="hidden" name="activate" value="1">

<?php render_field([
	'class' => 'form-control',
	'group_class' => 'col-12',
	'id' => 'email',
	'label' => __('User Email'),
	'label_class' => 'form-label',
	'name' => 'email',
	'required' => true,
	'type' => 'email',
]) ?>

<?php render_field([
	'field' => sprintf(
		'<select %s>%s</select>',
		html_params([
			'class' => 'form-select',
			'id' => 'permission',
			'name' => 'permission',
		]),
		implode('', array_map(function ($v) {
			return sprintf('<option value="%s">%s</option>', $v['id'], $v['title']);
		}, $widget_data['storage_permissions']))
	),
	'group_class' => 'col-12',
	'id' => 'type',
	'label' => __('User permissions'),
	'label_class' => 'form-label',
]) ?>

<div class="col-12"><button class="btn btn-primary" onclick="if(confirm('<?=__('Delete user permissions?')?>')){var v=this.closest('.js-formfield-item');if(v){v.parentNode.removeChild(v)}}return false" style="background-color:transparent;border:none;padding:.125rem;margin:.125rem .25rem;" title="<?=__('Delete user permissions')?>" type="button"><img src="<?=$config['static_url']?>/assets/img/icon_cross_accent.svg" style="height:24px;"></button></div>
</div>
<?='<script>var formfield_user_permissions=' . json_encode(ob_get_clean(), JSON_UNESCAPED_UNICODE) . '</script>'; ?>
</script>
<?php ! isset($footer_js) && $footer_js = '' ?>
<?php $footer_js .= ob_get_clean() ?>
<?php include '_header.php' ?>

<?php if (!empty($form_errors)) {
	error_message($form_errors);
} ?>

<form action="<?=$_SERVER['REQUEST_URI']?>" class="container-md g-3 js-form row" enctype="multipart/form-data" method="post">

<ul class="nav nav-tabs" style="margin-bottom:1rem;">
	<li class="nav-item"><a class="nav-link" href="<?=$config['base_url'] . '/storage/settings/' . $storage['uid'] . '/?' . http_build_query(['url' => $return_url])?>"><?=__('File Storage Settings')?></a></li>
	<li class="nav-item"><a class="nav-link active" href="<?=$config['base_url'] . '/storage/permissions/' . $storage['uid'] . '/?' . http_build_query(['url' => $return_url])?>"><?=__('User permissions')?></a></li>
</ul>

<style>#formfield_user_permissions .row:first-child{--bs-gutter-y:.5rem;}</style>
<h5 style="margin:1rem 0 0;"><?=sprintf(__('Users permissions for %s'), e($storage['title']))?></h3>
<div class="row" id="formfield_user_permissions">
<?php if (!empty($form_data['user_permissions'])) {
	foreach ($form_data['user_permissions'] as $i => $subfield) {
		$subfield_id = sprintf('user_permissions-%d', $i); ?>
	<div class="js-formfield-item row row-cols-lg-auto g-3 align-items-center">
<input type="hidden" name="<?=e($subfield_id)?>-id" value="<?=$subfield['id'] ?? ''?>">

<?php render_field([
	'class' => 'form-control',
	'group_class' => 'col-12',
	'id' => "$subfield_id-email",
	'label' => __('User Email'),
	'label_class' => 'form-label',
	'name' => "$subfield_id-email",
	'required' => true,
	'type' => 'email',
	'value' => $subfield['email'] ?? '',
], $form_errors) ?>

<?php render_field([
	'field' => sprintf(
		'<select %s>%s</select>',
		html_params([
			'class' => 'form-select',
			'id' => "$subfield_id-permission",
			'name' => "$subfield_id-permission",
		]),
		implode('', array_map(function ($v) use ($subfield) {
			return sprintf('<option value="%s"%s>%s</option>', $v['id'], ((int) $subfield['permission'] === $v['id'] ? ' selected' : ''), $v['title']);
		}, $widget_data['storage_permissions']))
	),
	'group_class' => 'col-12',
	'id' => "$subfield_id-type",
	'label' => __('User permissions'),
	'label_class' => 'form-label',
], $form_errors) ?>

<?php if (empty($subfield['isactive'])) { ?><div class="col-12"><div class="form-check"><input class="form-check-input" id="<?=$subfield_id?>-activate" name="<?=$subfield_id?>-activate" type="checkbox" value="1"><label class="form-check-label" for="<?=$subfield_id?>-activate"><?=__('Send the activation email')?></label></div></div><?php } ?>

<?php if (!empty($subfield['id'])) { ?><div class="col-12"><div class="form-check"><input class="ays-ignore form-check-input" id="<?=e($subfield_id)?>-del" name="<?=e($subfield_id)?>-del" type="checkbox"><label class="form-check-label" for="<?=e($subfield_id)?>-del"><?=__('Delete')?></label></div></div><?php } else { ?><div class="col-12"><button class="btn btn-primary" onclick="if(confirm('<?=__('Delete user permissions?')?>')){var v=this.closest('.js-formfield-item');if(v){v.parentNode.removeChild(v)}}return false" style="background-color:transparent;border:none;padding:.125rem;margin:.125rem .25rem;" title="<?=__('Delete user permissions')?>" type="button"><img src="<?=$config['static_url']?>/assets/img/icon_cross_accent.svg" style="height:24px;"></button></div><?php } ?>
	</div>
<?php
	}
} ?>
</div>
<div><button class="btn btn-secondary" onclick="formfield_add('user_permissions', document.getElementById('formfield_user_permissions'));return false" type="button"><?=__('Add User Permissions')?></button></div>
<div><button type="submit" class="btn btn-primary"><?=__('Save')?></button></div>
</form>

<?php ob_start() ?>
<script>$(function(){$('form').areYouSure()})</script>
<?php ! isset($footer_js) && $footer_js = '' ?>
<?php $footer_js .= ob_get_clean() ?>
<?php include '_footer.php' ?>