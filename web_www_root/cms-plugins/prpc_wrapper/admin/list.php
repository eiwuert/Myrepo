<?php if (lum_requirePermission('Options\View')) : ?>
	<div id="plugin-header">
		<h1>Options</h1>
		<form id="list_form" name="list_form" method="post" action="" onsubmit="return false;">
			<table id="plugin_controls" border="0">
				<tr>
					<td>
						<table id="actions">
							<tr>
								<td>										
									<select name="bulk_action" id="bulk_action">
										<option>Bulk Action</option>
										<option value="delete::Deleting options::1">Delete</option>
									</select>
								</td>
								<td>
									<input type="button" value="Go&raquo;" class="do_bulk_action"/>
								</td>
							</tr>
						</table>
					<td>
						<input type="text" id="search" value=""/>
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