<?php
if (lum_requirePermission('Languages\Edit')) :

	$obj = null;
	$render_form = true;
	$new = 1;

	if (isset($_GET['lang_id']))
	{
		$new = 0;
		$obj = lum_call('Languages', 'get', $_GET);
		
		if (!$obj)
		{
			echo "<p>The language could not be found in the database.</p>";
			$render_form = false;
		}
	}
	
	if ($render_form) :

?>
<div id="plugin-header">
	<h1><?php echo ($new ? 'Add a Language' : 'Edit Language');?></h1>
	<form id="edit_form" name="edit_form" method="post" action="" class="cmxform" >
		<input type="hidden" name="plugin" value="Languages"/>
		<input type="hidden" name="method" value="update"/>
		<input type="hidden" name="new" value="<?php echo $new; ?>"/>
		<input type="hidden" id="lang_id" name="lang_id" value="<?php echo (!$obj ? '' : $obj['lang_id']); ?>"/>
		<table>
			<tr>
				<td valign="top">
					<fieldset>
						<legend>Details</legend>
						<table>
							<tr>
								<td>
									Language
								</td>
								<td>
									<input id="clanguage" name="language" class="validate[required] text-input" minlength="2" value="<?php echo (!$obj ? '' : $obj['language']); ?>"/>
								</td>
							</tr>
							<tr>
								<td>
									Language Code
								</td>
								<td>
									<input id="clang_code" name="lang_code" class="validate[required] text-input" minlength="2" value="<?php echo (!$obj ? '' : $obj['lang_code']); ?>"/>
								</td>
							</tr>
							<tr>
								<td>
									Status
								</td>
								<td>
									<select id="cstatus" name="status" class="validate[required]">
									<option value="0">Not Used</option>
									<option value="1">Used</option>
									</select>
								</td>
							</tr>
							<tr>
								<td>
									Default Language?
								</td>
								<td>
									<select id="cdef" name="def" class="validate[required]">
									<option value="0">No</option>
									<option value="1">Yes</option>
									</select>
								</td>
							</tr>							
						</table>
					</fieldset>
				</td>
			</tr>
		</table>
		<br/>
		<table>
			<tr>
				<td colspan="2">
					<input class="submit" type="submit" value="Save Language"/>
					<input type="button" value="Cancel" onclick="window.location.href = TOOLS_PATH+'/Languages/list';"/>
				</td>
			</tr>
		</table>
	  </form>
</div>
		<script>
		function updateSelects()
		{
			$("#cstatus option[value='<?php echo (!$obj ? '1' : $obj['status']); ?>']").attr('selected', 'selected');
			$("#cdef option[value='<?php echo (!$obj ? '0' : $obj['def']); ?>']").attr('selected', 'selected');
		}
		</script>
<?php

	endif; // show form
endif; //require perimssion
	
?>
