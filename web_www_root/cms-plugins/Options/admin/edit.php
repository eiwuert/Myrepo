<?php
if (lum_requirePermission('Options\Edit')) :

	$obj = null;
	$render_form = true;
	$new = 1;

	if (isset($_GET['option_id']))
	{
		$new = 0;
		$obj = lum_call('Options', 'get', array('option_id'=>$_GET['option_id']));
		
		if (!$obj)
		{
			echo "<p>The option could not be found in the database.</p>";
			$render_form = false;
		}
	}

	if ($render_form) :
?>
<div id="plugin-header">
	<h1><?php echo ($new ? 'Add an Option' : 'Edit Option');?></h1>
	<form id="edit_form" name="edit_form" method="post" action="">
		<input type="hidden" name="plugin" value="Options"/>
		<input type="hidden" name="method" value="update"/>
		<input type="hidden" name="new" value="<?php echo $new; ?>"/>
		<input type="hidden" id="option_id" name="option_id" value="<?php echo (!$obj ? '' : $obj['option_id']); ?>"/>
		<fieldset>
			<legend>Option Details</legend>
			<table>
				<tr>
					<td>
						Name
					</td>
					<td>
						<input id="cname" name="name" class="validate[required] text-input" minlength="3" value="<?php echo (!$obj ? '' : $obj['name']); ?>" style="width: 300px;"/>
					</td>
				</tr>
				<tr>
					<td>
						Value
					</td>
					<td>
						<?php if ($new) : ?>
							<input id="cvalue" name="value" class="validate[required] text-input" value="<?php echo (!$obj ? '' : $obj['value']); ?>" style="width: 300px;"/>
						<?php else:?>
							<?php if ($obj['type'] == 'int' || $obj['type'] == 'string') : ?>
							<input id="cvalue" name="value" class="validate[required] text-input" value="<?php echo (!$obj ? '' : $obj['value']); ?>" style="width: 300px;"/>
							<?php endif; ?>
							<?php if ($obj['type'] == 'bool') : ?>
							<select id="cvalue" name="value" class="validate[required]">
								<option value="1" <?php echo ($obj && $obj['value'] == '1'? 'selected="selected"' : ''); ?>>Yes</option>
								<option value="0" <?php echo ($obj && $obj['value'] == '0'? 'selected="selected"' : ''); ?>>No</option>
							</select>
							<?php endif; ?>
						<?php endif; ?>	
					</td>
				</tr>
				<tr>
					<td>
						Type
					</td>
					<td>
						<select id="ctype" name="type" class="validate[required]">
							<option value="int" <?php echo ($obj && $obj['type'] == 'int'? 'selected="selected"' : ''); ?>>int</option>
							<option value="bool" <?php echo ($obj && $obj['type'] == 'bool'? 'selected="selected"' : ''); ?>>bool</option>
							<option value="string" <?php echo ($obj && $obj['type'] == 'string'? 'selected="selected"' : ''); ?>>string</option>
						</select>
					</td>
				</tr>
			</table>
		</fieldset>
		<input class="submit" type="submit" value="Save Option"/>
		<input type="button" value="Cancel" onclick="window.location.href = TOOLS_PATH+'/Options/list';"/>

	  </form>
</div>
<?php
	endif;
endif;
?>
