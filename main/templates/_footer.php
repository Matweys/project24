<?php if (empty($hide_footer)) { ?>
	</div>
</div>
<div class="footer">
	<div class="footer__inner" style="justify-content: center;">
		<div class="footer__col" style="text-align: center; flex: 0 0 auto; max-width: none;">
			<div>Электрозаводская улица 52 стр 8</div>
			<div style="margin: 0.5rem 0;"><a href="tel:+74952606776">+7 (495) 260-67-76</a></div>
			<div><a href="mailto:print@projekt-24.ru">print@projekt-24.ru</a></div>
		</div>
	</div>
</div>
<?php } ?>
<script src="<?php echo $config['static_url']; ?>/assets/main.js?<?php echo $config['assets_ver']['main'] ?? ''; ?>" type="text/javascript"></script>
<script>(new Menu());</script>
<?php echo '<script>!new Help('.json_encode([
    'base_url' => $config['base_url'] . '/help/',
    'language' => $lang,
    'return_url' => $return_url ?? null,
    'title' => !empty($title) ? sprintf('%s  — %s', e($title), e($config['site_name'])) : e($config['site_name']),
], JSON_UNESCAPED_UNICODE).')</script>'; ?>
<?php echo $footer_js ?? ''; ?>
</body>
</html>
