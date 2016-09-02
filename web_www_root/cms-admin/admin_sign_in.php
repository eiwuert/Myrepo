<?php
	$change_password = false;
	$error = '';
	// doing a password change
	if (isset($_POST['username']) && isset($_POST['password_change']))
	{
		if ($_POST['password_key'] == $_SESSION['password_key'])
		{
			$auth = lum_call('Users', 'changePassword', $_POST);

			if (!$auth['changed'])
			{
				$error = 'The password could not be changed due to a databse error.';
				$change_password = true;
			}			
			else
			{
				$_POST['user_password'] = $_POST['new_password'];
			}
			
			if ($auth['mismatch'])
			{
				$error = 'The new password and verify password do not match';
				$change_password = true;
			}
			
			if ($auth['notfound'])
			{
				$error = 'The user could not be found in the database';
			}			
		}
	}
	
	if (isset($_POST['username']) && !$change_password)
	{
		$auth = lum_call('Users', 'authenticate', $_POST);

		if ($auth['authenticated'])
		{
			lum_redirect();
		}
		else
		{
			if ($auth['status'])
			{
				$error = 'Sorry but your admin account has been deactivated';
			}
			
			if (!$auth['status'] && !$auth['change_password'])
			{
				$error = "Sorry but either your username or password is incorrect";
			}
			
			if ($auth['change_password'])
			{
				$_SESSION['password_key'] = md5($_POST['username'].time().'SALT!');
				$change_password = true;
			}
		}
	}

	$redirect = '/'.TOOLS_PAGE;
	if (isset($_REQUEST['redirect']))
	{
		$redirect = $_REQUEST['redirect'];
	}

	if ($error)
		echo '<p class="red_text">'.$error.'</p>';

?>
<?php if (!$change_password) : ?>
<?php
 /**
  * Edit below here for the sign in page
  **/
?>
	<form action="<?php echo $_SERVER['REQUEST_URI']; ?>" method="POST">
		<input type="hidden" name="redirect" value="<?=$redirect?>"/>
		<p>
			Username<br/>
			<input type="text" name="username" value="" maxlength="32"/>
			<br/>
			<br/>
			Password<br/>
			<input type="password" name="user_password" value="" maxlength="32"/>
			<br/>
			<br/>
			<a href="/<?=TOOLS_PAGE?>?forgot-password" class="blue">Forgot Password</a>&nbsp;&nbsp;<input type="submit" value="Sign In"/>
		</p>
	</form>
<?php else: ?>
<?php
 /**
  * Edit below here for the change password page
  **/
?>
	You are required to change your password at this time
	<form action="<?php echo $_SERVER['REQUEST_URI']; ?>" method="POST">
		<input type="hidden" name="redirect" value="<?=$redirect?>"/>
		<p>
			<input type="hidden" name="password_key" value="<?php echo $_SESSION['password_key'];?>"/>
			<input type="hidden" name="password_change" value="1"/>
			<input type="hidden" name="username" value="<?php echo $_POST['username'];?>"/>
			Current Password<br/>
			<input type="password" name="user_password" value="" maxlength="32"/>
			<br/>
			<br/>
			New Password<br/>
			<input type="password" name="new_password" value="" maxlength="32"/>
			<br/>
			<br/>
			Verify Password<br/>
			<input type="password" name="verify_password" value="" maxlength="32"/>
			<br/>
			<br/>
			<a href="/<?=TOOLS_PAGE?>?forgot-password" class="blue">Forgot Password</a>&nbsp;&nbsp;<input type="submit" value="Change Password"/>
		</p>
	</form>
<?php endif; ?>

