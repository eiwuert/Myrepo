<?php
if (lum_requirePermission('Users\Roles\Edit')) :

	$obj = null;
	$render_form = true;
	$new = 1;
	$permissions = array();
	
	if (isset($_GET['role_id']))
	{
		$new = 0;
		$obj = lum_call('Users', 'getRole', $_GET);
		
		$permissions = unserialize(base64_decode($obj['permissions']));

		
		// does this role have super user privleges? If so, only another super user can edit it
		if (lum_call('Users', 'hasPermission', array('permission'=>'Users\Super User', 'permissions'=>$permissions)))
		{
			// does the current user have Super User privleges?
			if (!lum_isSuperUser())
			{
				echo "<p>Sorry but you do not have access to edit this role</p>";
				$render_form = false;
			}
		}

		if (!$obj)
		{
			echo "<p>The role could not be found in the database.</p>";
			$render_form = false;
		}
	}

	if ($render_form) :
	
?>
<div id="plugin-header">
	<h1><?php echo ($new ? 'Add a Role' : 'Edit Role');?></h1>
	<form id="edit_form" name="edit_form" method="post" action="" class="cmxform" >
		<input type="hidden" name="plugin" value="Users"/>
		<input type="hidden" name="method" value="updateRole"/>
		<input type="hidden" name="new" value="<?php echo $new; ?>"/>
		<input type="hidden" name="require_superuser" value="0"/>
		<input type="hidden" id="role_id" name="role_id" value="<?php echo (!$obj ? '0' : $obj['role_id']); ?>"/>
		Role <input id="cname" name="name" class="validate[required] text-input" minlength="2" value="<?php echo (!$obj ? '' : $obj['name']); ?>"/><br/>
		<?php
		if (lum_isMasterSite()) : ?>
		Site Role Only? <input type="checkbox" value="1" name="site_role" id="csite_role" <?php echo ($obj && $obj['site_role'] ? 'checked' : ''); ?>/>
		<?php else: ?>
		<input type="hidden" value="0" name="site_role" id="csite_role"/>
		<?php endif; ?>
		<br/><br/>
		<fieldset>
			<legend>Permissions</legend>
			<p>Check all that apply. If a permission has an 'All' option checking it is all you need for the entire group of permissions.</p>
			<?php
				$classes = lum_getInstalledPlugins();
				$groups = array();
				foreach ($classes as $plugin)
				{
					$potential_plugin = 'LuminancePlugin'.$plugin;
		
					if (!class_exists($potential_plugin))
					{
						lum_loadPlugin($plugin);
					}
					if (method_exists($potential_plugin, 'getPermissionTypes'))
					{
						$obj = new $potential_plugin($this->lumRegistry);
						
						$arr = $obj->getPermissionTypes();
						if ($arr)
						{
							foreach ($arr as $perm)
							{
								$temp = explode('\\', $perm);
								$key = null;
								
								if (isset($temp[0]))
								{
									$key = $temp[0];
									if (!isset($groups[$key]))
										$groups[$key] = array();
								}

								if ($key)
								{
									array_shift($temp);
									$groups[$key][] = implode('\\', $temp);
								}
								
							}
						}
					}
				}
				ksort($groups);
				echo '<table width="100%"><tr>';
				$count = 0;
				$need_tr = false;
				foreach ($groups as $group=>$arr)
				{
					if ($need_tr)
					{
						$need_tr = false;
						echo '<tr>';
					}
					
					$last = '';
					if ($count == 3) // if we're the last group in the row dont show a right border
						$last = ' last_group';
						
					echo '<td class="permission_group'.$last.'"><b>'.$group.'</b><br/>';
					foreach ($arr as $perms)
					{
						$perm_value = $group.'\\'.$perms;
						$checked = '';
						if (in_array($perm_value, $permissions))
							$checked = ' checked="checked"';
							
						$ok = true;
						// special case scenario!
						if ($perms == 'Super User')
						{
							if (!lum_isSuperUser())
								$ok = false;
						}
						
						if ($ok)
							echo '<input type="checkbox" name="permissions[]" value="'.$perm_value.'"'.$checked.' class="permission">'.$perms.'<br/>';	
					}
					echo '</td>';
					$count++;
					if ($count / 4 == intval($count / 4)) // 4 groups per row
					{
						echo '</tr>';
						$need_tr = true;
						$count = 0;
					}
					
				}
				echo '</table>';
				
			?>
		</fieldset>
		<input class="submit" type="submit" value="Save Role"/>
		<input type="button" value="Cancel" onclick="window.location.href = TOOLS_PATH+'/Users/list-roles';"/>

	  </form>
</div>
<?php

	endif; // show form
endif; // require perimssion

?>
