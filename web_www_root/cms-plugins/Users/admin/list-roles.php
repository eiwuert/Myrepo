<?php if (lum_requirePermission('Users\Roles\Edit')) : ?>

	<div id="plugin-header">
		<h1>Roles</h1>
		<form id="list_form" name="list_form" method="post" action="">
			<table id="plugin_controls" border="0">
				<tr>
					<td>
						<table id="actions">
							<tr>
								<td>										
									<select name="bulk_action" id="bulk_action">
										<option>Bulk Action</option>
										<option value="deleteRole::Deleting roles::1">Delete</option>
									</select>
								</td>
								<td>
									<input type="button" value="Go&raquo;" class="button" onclick="doBulkAction();"/>
								</td>
							</tr>
						</table>
					<td>
						<input type="text" id="search" value="" style="height: 13px;"/>
					</td>
					<td class="query">
						<input type="button" value="Search&raquo;" class="button" onclick="doSearch();"/>
					</td>
				</tr>
			</table>
		</form>	
	</div>
	<div id="grid"></div>
<?php endif; ?>