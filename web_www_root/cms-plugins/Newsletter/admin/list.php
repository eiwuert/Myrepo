<?php if (lum_requirePermission('Newsletter\View')) : ?>
	<div id="plugin-header">
		<h1>Newsletter Requests</h1>
		<form id="list_form" name="list_form" method="post" action="" onsubmit="return false;">
			<table id="plugin_controls" border="0">
				<tr>
					<td>
						<table id="actions">
							<tr>
								<td>										
									Status <select name="status" id="status">
										<option value="1">New</option>
										<option value="0">Archived</option>
										<option value="">All</option>
									</select>
								</td>

							</tr>
						</table>
					</td>
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