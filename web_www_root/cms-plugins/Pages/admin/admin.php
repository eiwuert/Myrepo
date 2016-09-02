<?php if (lum_requirePermission('Pages\View', false)) : ?>
			<li><a href="<?=TOOLS_PATH?>/<?=TOOLS_PAGE?>/Pages/list"><img src="<?= BASE_URL_OFFSET?>cms-plugins/Pages/admin/images/pages.png" class="menu-icon"/>Pages</a>
				<ul>
					<li><a href="<?=TOOLS_PATH?>/<?=TOOLS_PAGE?>/Pages/list"><img src="<?= BASE_URL_OFFSET?>cms-plugins/Pages/admin/images/pages_edit.png" class="menu-icon"/>Edit Pages</a></li>
					<?php if (lum_requirePermission('Pages\Edit', false)) : ?>
						<li><a href="<?=TOOLS_PATH?>/<?=TOOLS_PAGE?>/Pages/edit"><img src="<?= BASE_URL_OFFSET?>cms-plugins/Pages/admin/images/pages_add.png" class="menu-icon"/>Add a Page</a></li>
						<li><a href="<?=TOOLS_PATH?>/<?=TOOLS_PAGE?>/Pages/structure"><img src="<?= BASE_URL_OFFSET?>cms-plugins/Pages/admin/images/pages_structure.png" class="menu-icon"/>Site Structure</a></li>
						<?php endif; ?>
				</ul>
			</li>
<?php endif; ?>
