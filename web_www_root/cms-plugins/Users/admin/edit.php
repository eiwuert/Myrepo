<?php
if (lum_requirePermission('Users\Accounts\Edit')) :

	$obj = null;
	$render_form = true;
	$new = 1;
	$roles = array();

	if (isset($_GET['user_id']))
	{
		$new = 0;
		$obj = lum_call('Users', 'get', $_GET);
		$permissions = unserialize(base64_decode($obj['permissions']));
		
		if (lum_call('Users', 'hasPermission', array('permission'=>'Users\Super User', 'permissions'=>$permissions)))
		{
			if (!lum_isSuperUser(true))
				$render_form = false;
		}
		
		if (!$obj)
		{
			echo "<p>The admin user could not be found in the database.</p>";
			$render_form = false;
		}
	}
	
	if ($render_form)
	{
		list($roles, $ret) = lum_call('Users', 'getRoleList', array("user_data"=>$user, 'sort'=>'name'));
		if (!$roles)
		{
			echo "<p>There was a problem with the database. Roles could not be loaded for the form.</p>";
			$render_form = false;
		} 	
	}
	
	if ($render_form) :

?>
<div id="plugin-header">
	<h1><?php echo ($new ? 'Add an Admin User' : 'Edit Admin User');?></h1>
	<form id="edit_form" name="edit_form" method="post" action="" class="cmxform" >
		<input type="hidden" name="plugin" value="Users"/>
		<input type="hidden" name="method" value="update"/>
		<input type="hidden" name="signature" value=""/>
		<input type="hidden" name="region" value=""/>
		<input type="hidden" name="new" value="<?php echo $new; ?>"/>
		<input type="hidden" id="user_id" name="user_id" value="<?php echo (!$obj ? '' : $obj['user_id']); ?>"/>
		<table>
			<tr>
				<td valign="top">
					<fieldset>
						<legend>Account Information</legend>
						<table>
							<tr>
								<td>
									Username
								</td>
								<td>
									<input id="cusername" name="username" class="validate[required] text-input" minlength="5" value="<?php echo (!$obj ? '' : $obj['username']); ?>"/>
								</td>
							</tr>
							<tr>
								<td>
									Email Address
								</td>
								<td>
									<input id="cuser_email" name="user_email" class="validate[required,custom[email]] text-input" minlength="8" value="<?php echo (!$obj ? '' : $obj['user_email']); ?>"/>
								</td>
							</tr>
							<tr>
								<td>
									Role
								</td>
								<td>
									<select id="crole_id" name="role_id" class="validate[required]">
									<?php
									$html = "";
									for ($i=0;$i<count($roles);$i++)
									{
										if (is_object($roles[$i]))
										{
											$roles[$i] = get_object_vars($roles[$i]);
										}
										
										if ($roles[$i]['site_role'])
											continue;
										
										$permissions = unserialize(base64_decode($roles[$i]['permissions']));
										if (lum_call('Users', 'hasPermission', array('permission'=>'Users\Super User', 'permissions'=>$permissions)) && !lum_isSuperUser())
											continue;
										
										$selected = "";
										if (($obj['role_id'] == $roles[$i]['role_id']))
										{
											$selected = "selected";
										}
										$html .= '<option value="'.urlencode($roles[$i]['role_id']).'" '.$selected.'>'.$roles[$i]['name'].'</option>
										';
									}
									echo $html;										
									?>
									</select>
								</td>
							</tr>
							<tr>
								<td>
									Status
								</td>
								<td>
									<select id="cstatus" name="status" class="validate[required]">
									<option value="0">Cannot Login</option>
									<option value="1">Can Login</option>
									</select>
								</td>
							</tr>				
							<tr>
								<td>
									Multi-User Login?
								</td>
								<td>
									<select id="cis_group" name="is_group" class="validate[required]">
									<option value="0">No</option>
									<option value="1">Yes</option>
									</select>
								</td>
							</tr>					
						</table>
						<fieldset>
							<legend>
								Password
							</legend>
							<table>
								<tr>
									<td>
										<?php echo ($new ? 'Set' : 'Change'); ?> Password
									</td>
									<td>
										<input id="cuser_password" name="user_password"  class="validate[<?php echo ($new ? 'required' : 'optional');?>] text-input" minlength="6" value="<?php
										if (!_USE_HASHED_PASSWORDS)
											echo (!$obj ? '' : $obj['user_password']);
										?>"/>
									</td>
								</tr>				
								<tr>
									<td>
										User Must Change Password?
									</td>
									<td>
										<select id="cmust_change_password" name="must_change_password" class="validate[required]">
										<option value="0">No</option>
										<option value="1">Yes</option>
										</select>
									</td>
								</tr>	
				
							</table>
						</fieldset>
					</fieldset>
						
				</td>
				<td valign="top">
					<fieldset>
						<legend>Personal Information</legend>
						<table>
							<tr>
								<td>
									First Name
								</td>
								<td>
									<input id="cfirst_name" name="first_name" class="validate[required] text-input" minlength="3" value="<?php echo (!$obj ? '' : $obj['first_name']); ?>"/>
								</td>
							</tr>				
							<tr>
								<td>
									Last Name
								</td>
								<td>
									<input id="clast_name" name="last_name" class="validate[required] text-input" minlength="3" value="<?php echo (!$obj ? '' : $obj['last_name']); ?>"/>
								</td>
							</tr>
							<tr>
								<td>
									Phone
								</td>
								<td>
									<input id="cphone" name="phone" value="<?php echo (!$obj ? '' : $obj['phone']); ?>" class="validate[optional] text-input"/>
								</td>
							</tr>
							<tr>
								<td>
									Comments
								</td>
								<td>
									<textarea id="ccomments" name="comments"><?php echo (!$obj ? '' : $obj['comments']); ?></textarea>
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
					<input class="submit" type="submit" value="Save User"/>
					<input type="button" value="Cancel" onclick="window.location.href = TOOLS_PATH+'/Users/list';"/>
				</td>
			</tr>
		</table>
	  </form>
</div>
		<script>
		function updateSelects()
		{
			$("#cregion option[value='<?php echo (!$obj ? '' : $obj['region']); ?>']").attr('selected', 'selected');
			$("#cis_group option[value='<?php echo (!$obj ? '0' : $obj['is_group']); ?>']").attr('selected', 'selected');
			$("#crole_id option[value='<?php echo (!$obj ? '2' : $obj['role_id']); ?>']").attr('selected', 'selected');
			$("#cstatus option[value='<?php echo (!$obj ? '1' : $obj['status']); ?>']").attr('selected', 'selected');
			$("#cmust_change_password option[value='<?php echo (!$obj ? '0' : $obj['must_change_password']); ?>']").attr('selected', 'selected');
		}
		</script>
<?php

	endif; // show form
endif; //require perimssion
	
?>
