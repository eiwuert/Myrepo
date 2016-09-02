<?php if (lum_requirePermission('Gallery\View', false)) : ?>
			<li><a href="<?=TOOLS_PATH?>/<?=TOOLS_PAGE?>/Gallery/list"><img src="<?= BASE_URL_OFFSET?>cms-plugins/Gallery/admin/images/gallery.png" class="menu-icon"/>Galleries</a>
				<ul>
					<li><a href="<?=TOOLS_PATH?>/<?=TOOLS_PAGE?>/Gallery/list"><img src="<?= BASE_URL_OFFSET?>cms-plugins/Gallery/admin/images/gallery_edit.png" class="menu-icon"/>Edit a Gallery</a></li>
					<?php if (lum_requirePermission('Gallery\Edit', false)) : ?>
						<li><a href="<?=TOOLS_PATH?>/<?=TOOLS_PAGE?>/Gallery/edit"><img src="<?= BASE_URL_OFFSET?>cms-plugins/Gallery/admin/images/gallery_add.png" class="menu-icon"/>Add a Gallery</a></li>
					<?php endif; ?>
				</ul>
			</li>
<?php endif; ?>
