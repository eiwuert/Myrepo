<?php if (lum_requirePermission('Users\Accounts\Edit')) : ?>

	<div id="plugin-header">
		<h1>Admin Users</h1>
		<form id="list_form" name="list_form" method="post" action="" onsubmit="return false;">
			<table id="plugin_controls" border="0">
				<tr>
					<td>
						<table id="actions">
							<tr>
								<td>										
									<select name="bulk_action" id="bulk_action">
										<option>Bulk Action</option>
										<option value="delete::Deleting users::1">Delete</option>
										<option value="activate::Activating users::0">Activate</option>
										<option value="deactivate::Deactivating users::0">Deactivate</option>
									</select>
								</td>
								<td>
									<input type="button" value="Go&raquo;" class="do_bulk_action"/>
								</td>
								<td>										
									Region
									<select id="cregion" name="region" class="validate[required]">
									<option value="">All</option>
									<option value="Jackson Hole">Jackson Hole</option>
									<option value="Mont Tremblant">Mont Tremblant</option>
									<option value="Park City">Park City</option>
									<option value="Remote">Remote</option>
									<option value="Whistler">Whistler</option>
									</select>
								</td>								
							</tr>
						</table>
					<td>
						<input type="text" id="search" value="" style="height: 13px;"/>
					</td>
					<td class="query">
						<input type="button" value="Search&raquo;" class="do_search"/> <input type="button" value="Reset" class="reset_search"/>
					</td>
				</tr>
			</table>
		</form>	
	</div>
	<div id="grid"></div>
	
<?php endif; ?>	