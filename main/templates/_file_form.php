<? ob_start() ?>
<script>
(function() {
	$('form').areYouSure({'silent': true});
	$('.js-pager-item').click(function (e) {
		var a, f, d, u;
		f = $('form');
		a = f.attr('action');
		u = $(this).attr('href');
		if (a && f.hasClass('dirty') && confirm('<?=__('Save changes?')?>')) {
			e.preventDefault();
			d = new FormData();
			$.each(f.serializeArray(), function (k, v) {
				d.append(v.name, v.value);
			});
			$.ajax({
				contentType: false,
				data: d,
				method: 'post',
				processData: false,
				url: a,
			}).always(function() {
				if (u) {
					document.location.href = u;
				}
			});
		}
	});

	document.addEventListener('keydown', function (e) {
		if (e.keyCode === 83 && (e.ctrlKey || e.metaKey) && e.shiftKey) {
			a = document.querySelector('button[name="save_and_next"]');
			if (a) {
				e.preventDefault();
				a.click();
			}
		}

		if (e.keyCode === 37 && e.ctrlKey && e.altKey) {
			a = document.querySelector('.js-pager-item-prev');
			if (a) {
				e.preventDefault();
				a.click();
			}
		}

		if (e.keyCode === 39 && e.ctrlKey && e.altKey) {
			a = document.querySelector('.js-pager-item-next');
			if (a) {
				e.preventDefault();
				a.click();
			}
		}
	});
}());
</script>
<? !isset($footer_js) && $footer_js = '' ?>
<? $footer_js .= ob_get_clean() ?>
<form action="<?=$_SERVER['REQUEST_URI']?>" class="g-3 row" enctype="multipart/form-data" method="post">
<? render_field([
	'autofocus' => true,
	'class' => 'form-control',
	'id' => 'name',
	'label' => __('Filename'),
	'label_class' => 'form-label',
	'name' => 'name',
	'type' => 'text',
	'value' => $form_data['name'] ?? '',
], $form_errors) ?>

<? if (!empty($storage['attributes'])) {
	foreach ($storage['attributes'] as $i => $v) {
		$field_name = 'a' . $v['id'];
		$value = $form_data[$field_name] ?? '';

		if ($v['type_name'] === 'date' && !$_POST) {
			$value = $data[$field_name] ? (date_create($data[$field_name]))->format('d.m.Y') : '';
		}

		if ($v['type_name'] === 'datetime' && !$_POST) {
			$value = $data[$field_name] ? (date_create($data[$field_name]))->format('d.m.Y H:i:s') : '';
		}

		$field_args = [
			'class' => 'form-control',
			'id' => $field_name,
			'label' => $v['title'],
			'label_class' => 'form-label',
			'name' => $field_name,
			'type' => 'text',
			'value' => $value,
		];

		if ($v['type_name'] == 'text') {
			$field_args['textarea'] = true;
			$field_args['rows'] = 4;
		}

		render_field($field_args, $form_errors);
	}
} ?>

<div class="row gx-3 gy-2"><div class="col-auto gy-3"><button type="submit" class="btn btn-primary"><?=__('Save')?></button></div><?
if (empty($search_query)) {
	?><div class="col-auto gy-3"><button class="btn btn-secondary" name="save_and_next" title="<?=__('Save and go to next file (Ctrl-Shift-S)')?>"><?=__('Save and go to next file')?></button></div><div class="col-auto gy-3"><button class="btn btn-secondary" name="delete" onclick="return confirm('<?=__('Delete file?')?>')" title="<?=__('Delete file')?>"><?=__('Delete')?></button></div><?
} ?></div>
</form>