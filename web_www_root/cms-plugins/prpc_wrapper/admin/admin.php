<?php if (lum_requirePermission('Options\View', false)) : ?>
			<li><a href="<?=TOOLS_PATH?>/<?=TOOLS_PAGE?>/Options/list"><img src="<?= BASE_URL_OFFSET?>cms-plugins/Options/admin/images/options.png" class="menu-icon"/>Options</a>
				<ul>
					<li><a href="<?=TOOLS_PATH?>/<?=TOOLS_PAGE?>/Options/list"><img src="<?= BASE_URL_OFFSET?>cms-plugins/Options/admin/images/options_edit.png" class="menu-icon"/>Edit Options</a></li>
					<?php if (lum_requirePermission('Options\Edit', false)) : ?><li><a href="<?=TOOLS_PATH?>/<?=TOOLS_PAGE?>Options/edit"><img src="<?= BASE_URL_OFFSET?>cms-plugins/Options/admin/images/options_add.png" class="menu-icon"/>Add an Option</a></li><?php endif; ?>
				</ul>
			</li>
<?php endif; ?>