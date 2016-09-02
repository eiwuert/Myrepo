<?php if (lum_requirePermission('Languages\Edit', false)) : ?>
	<li><a href="<?=TOOLS_PATH?>/<?=TOOLS_PAGE?>/Languages/list"><img src="<?= BASE_URL_OFFSET?>cms-plugins/Languages/admin/images/languages.png" class="menu-icon"/>Languages</a>
		<ul>
			<li><a href="<?=TOOLS_PATH?>/<?=TOOLS_PAGE?>/Languages/list"><img src="<?= BASE_URL_OFFSET?>cms-plugins/Languages/admin/images/language_edit.png" class="menu-icon"/>Edit Languages</a></li>
			<li><a href="<?=TOOLS_PATH?>/<?=TOOLS_PAGE?>/Languages/edit"><img src="<?= BASE_URL_OFFSET?>cms-plugins/Languages/admin/images/language_add.png" class="menu-icon"/>Add a Language</a></li>
		</ul>
	</li>
<?php endif ;?>