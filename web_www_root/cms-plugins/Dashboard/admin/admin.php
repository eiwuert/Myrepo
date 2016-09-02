<?php if (lum_requirePermission('Strings\View', false)) : ?>
			<li><a href="/<?=TOOLS_PAGE?>/Strings/list"><img src="<?= BASE_URL_OFFSET?>cms-plugins/Strings/admin/images/strings.png" class="menu-icon"/>Strings</a>
				<ul>
					<li><a href="/<?=TOOLS_PAGE?>/Strings/list"><img src="<?= BASE_URL_OFFSET?>cms-plugins/Strings/admin/images/strings_edit.png" class="menu-icon"/>Edit Strings</a></li>
					<?php if (lum_requirePermission('Strings\Edit', false)) : ?><li><a href="/<?=TOOLS_PAGE?>/Strings/edit"><img src="<?= BASE_URL_OFFSET?>cms-plugins/Strings/admin/images/strings_add.png" class="menu-icon"/>Add a String</a></li><?php endif; ?>
				</ul>
			</li>
<?php endif; ?>