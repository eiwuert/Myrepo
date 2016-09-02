<?php if (lum_requirePermission('Users\Accounts\Edit', false)) : ?>
	<li><a href="<?=TOOLS_PATH?>/<?=TOOLS_PAGE?>/Users/list"><img src="<?= BASE_URL_OFFSET?>cms-plugins/Users/admin/images/user.png" class="menu-icon"/>Users</a>
		<ul>
			<li><a href="<?=TOOLS_PATH?>/<?=TOOLS_PAGE?>/Users/list"><img src="<?= BASE_URL_OFFSET?>cms-plugins/Users/admin/images/user_edit.png" class="menu-icon"/>Edit Users</a></li>
			<li><a href="<?=TOOLS_PATH?>/<?=TOOLS_PAGE?>/Users/edit"><img src="<?= BASE_URL_OFFSET?>cms-plugins/Users/admin/images/user_add.png" class="menu-icon"/>Add an Admin User</a></li>
			<?php if (lum_requirePermission('Users\Roles\Edit', false)) : ?>
			<li><a href="<?=TOOLS_PATH?>/<?=TOOLS_PAGE?>/Users/list-roles"><img src="<?= BASE_URL_OFFSET?>cms-plugins/Users/admin/images/role.png" class="menu-icon"/>Roles</a>
				<ul id="id">
					<li><a href="<?=TOOLS_PATH?>/<?=TOOLS_PAGE?>/Users/list-roles"><img src="<?= BASE_URL_OFFSET?>cms-plugins/Users/admin/images/role_edit.png" class="menu-icon"/>Edit Roles</a></li>
					<li><a href="<?=TOOLS_PATH?>/<?=TOOLS_PAGE?>/Users/edit-role"><img src="<?= BASE_URL_OFFSET?>cms-plugins/Users/admin/images/role_add.png" class="menu-icon"/>Add a Role</a></li>
				</ul>
			</li>
			<?php endif; ?>
		</ul>
	</li>
<?php endif ;?>