<?php

	$msg = '';
	if (isset($_POST['user_email']))
	{
		if (lum_call('Users', 'forgotPassword', $_POST))
		{
			$msg = 'An email has been sent with your new password. <a href="'.TOOLS_PAGE.'">Sign In</a>';
		}
		else
		{
			$msg = 'Sorry, but an error occurred and we could not send your new password.';
		}
	}

	if ($msg)
		echo '<p class="red_text">'.$msg.'</p>';

?>

	<form action="<?php echo $_SERVER['REQUEST_URI']; ?>" method="POST">
		<p>
			Email<br/>
			<input type="text" name="user_email" value="" maxlength="255"/>
			<input type="submit" value="Submit"/>
		</p>
	</form>
