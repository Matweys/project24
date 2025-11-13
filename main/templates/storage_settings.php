<?php $title = __('File Storage Settings') ?>
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
<?php render_field([
	'class' => 'form-control',
	'group_class' => 'col-12',
	'id' => 'title',
	'label' => __('Attribute name'),
	'label_class' => 'form-label',
	'name' => 'title',
	'required' => true,
	'textarea' => true,
	'type' => 'text',
]) ?>

<?php render_field([
	'field' => sprintf(
		'<select %s>%s</select>',
		html_params([
			'class' => 'form-select',
			'id' => 'type',
			'name' => 'type',
		]),
		implode('', array_map(function ($v) {
			return sprintf('<option value="%s">%s</option>', $v['id'], $v['title']);
		}, $widget_data['attribute_types']))
	),
	'group_class' => 'col-12',
	'id' => 'type',
	'label' => __('Attribute type'),
	'label_class' => 'form-label',
]) ?>

<?php render_field([
	'class' => 'form-control',
	'group_class' => 'col-12',
	'id' => 'sort',
	'label' => __('Sort'),
	'label_class' => 'form-label',
	'name' => 'sort',
	'type' => 'text',
]) ?>

<div class="col-12"><?php render_checkbox([
	'class' => 'form-check-input',
	'group_class' => 'form-check',
	'id' => 'filter',
	'label' => __('Filter'),
	'label_class' => 'form-check-label',
	'name' => 'filter',
	'type' => 'checkbox',
	'value' => 1,
]) ?></div>

<div class="col-12"><button class="btn btn-primary" onclick="if(confirm('<?=__('Delete an attribute?')?>')){var v=this.closest('.js-formfield-item');if(v){v.parentNode.removeChild(v)}}return false" style="background-color:transparent;border:none;padding:.125rem;margin:.125rem .25rem;" title="<?=__('Delete an attribute')?>" type="button"><img src="<?=$config['static_url']?>/assets/img/icon_cross_accent.svg" style="height:24px;"></button></div>
</div>
<?='<script>var formfield_attributes=' . json_encode(ob_get_clean(), JSON_UNESCAPED_UNICODE) . '</script>'; ?>
</script>
<?php ! isset($footer_js) && $footer_js = '' ?>
<?php $footer_js .= ob_get_clean() ?>
<?php include '_header.php' ?>
<?php $edit_permission = in_array($storage['permission_name'] ?? null, ['edit', 'full']) ?>
<?php $full_permission = ($storage['permission_name'] ?? null) === 'full' ?>

<?php if (!empty($form_errors)) {
	error_message($form_errors);
} ?>

<form action="<?=$_SERVER['REQUEST_URI']?>" class="container-md g-3 js-form row" enctype="multipart/form-data" method="post">

<?php if ($full_permission) { ?>
<ul class="nav nav-tabs">
	<li class="nav-item"><a class="nav-link active" href="<?=$config['base_url'] . '/storage/settings/' . $storage['uid'] . '/?' . http_build_query(['url' => $return_url])?>"><?=__('File Storage Settings')?></a></li>
	<li class="nav-item"><a class="nav-link" href="<?=$config['base_url'] . '/storage/permissions/' . $storage['uid'] . '/?'. http_build_query(['url' => $return_url])?>"><?=__('User permissions')?></a></li>
</ul>
<?php } ?>

<?php render_field([
	'class' => 'form-control',
	'id' => 'title',
	'label' => __('File Storage Name'),
	'label_class' => 'form-label',
	'name' => 'title',
	'type' => 'text',
	'value' => $form_data['title'] ?? '',
], $form_errors) ?>

<style>#formfield_attributes .row:first-child{--bs-gutter-y:.5rem;}</style>
<h5 style="margin:1rem 0 0;"><?=__('Attributes')?></h3>
<div class="row" id="formfield_attributes">
<?php if (!empty($form_data['attributes'])) {
	foreach ($form_data['attributes'] as $i => $subfield) {
		$subfield_id = sprintf('attributes-%d', $i); ?>
	<div class="js-formfield-item row row-cols-lg-auto g-3 align-items-center">
<input type="hidden" name="<?=e($subfield_id)?>-id" value="<?=$subfield['id'] ?? ''?>">

<?php render_field([
	'class' => 'form-control',
	'group_class' => 'col-12',
	'id' => "$subfield_id-title",
	'label' => __('Attribute name'),
	'label_class' => 'form-label',
	'name' => "$subfield_id-title",
	'textarea' => true,
	'type' => 'text',
	'value' => $subfield['title'] ?? '',
	'required' => true,
], $form_errors) ?>

<?php render_field([
	'field' => sprintf(
		'<select %s>%s</select>',
		html_params([
			'class' => 'form-select',
			'id' => "$subfield_id-type",
			'name' => "$subfield_id-type",
		]),
		implode('', array_map(function ($v) use ($subfield) {
			return sprintf('<option value="%s"%s>%s</option>', $v['id'], ((int) $subfield['type'] === $v['id'] ? ' selected' : ''), $v['title']);
		}, $widget_data['attribute_types']))
	),
	'group_class' => 'col-12',
	'id' => "$subfield_id-type",
	'label' => __('Attribute type'),
	'label_class' => 'form-label',
], $form_errors) ?>

<?php render_field([
	'class' => 'form-control',
	'group_class' => 'col-12',
	'id' => "$subfield_id-sort",
	'label' => __('Sort'),
	'label_class' => 'form-label',
	'name' => "$subfield_id-sort",
	'type' => 'text',
	'value' => $subfield['sort'] ?? '',
], $form_errors) ?>

<div class="col-12"><?php render_checkbox([
	'checked' => (!empty($subfield['filter'])),
	'class' => 'form-check-input',
	'group_class' => 'form-check',
	'id' => "$subfield_id-filter",
	'label' => __('Filter'),
	'label_class' => 'form-check-label',
	'name' => "$subfield_id-filter",
	'type' => 'checkbox',
	'value' => 1,
], $form_errors) ?></div>

<?php if (!empty($subfield['id'])) { ?><div class="col12"><div class="form-check"><input class="ays-ignore form-check-input" id="<?=e($subfield_id)?>-del" name="<?=e($subfield_id)?>-del" type="checkbox"><label class="form-check-label" for="<?=e($subfield_id)?>-del"><?=__('Delete')?></label></div></div><?php } else { ?><div class="col-12"><button class="btn btn-primary" onclick="if(confirm('<?=__('Delete an attribute?')?>')){var v=this.closest('.js-formfield-item');if(v){v.parentNode.removeChild(v)}}return false" style="background-color:transparent;border:none;padding:.125rem;margin:.125rem .25rem;" title="<?=__('Delete an attribute')?>" type="button"><img src="<?=$config['static_url']?>/assets/img/icon_cross_accent.svg" style="height:24px;"></button></div><?php } ?>
	</div>
<?php
	}
} ?>
</div>
<div><button class="btn btn-secondary" onclick="formfield_add('attributes', document.getElementById('formfield_attributes'));return false" type="button"><?=__('Add an attribute')?></button></div>
<div><button type="submit" class="btn btn-primary"><?=__('Save')?></button></div>
</form>

<?php ob_start() ?>
<script>$(function(){$('form').areYouSure()})</script>
<?php ! isset($footer_js) && $footer_js = '' ?>
<?php $footer_js .= ob_get_clean() ?>
<?php include '_footer.php' ?>