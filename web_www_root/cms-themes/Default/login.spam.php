<?php
/*
Template: Spam Login
Description: Page that a comes from clicking the spam link and directs a user to the customer privacy page
*/
?>
<?php
	include(TEMPLATES_PATH.'customer.func.php');

	// Check and make sure that the application id and hash key match, and that the time hasn't expired or redirect to bad login
	$confirm_string = lum_getString("[SPAM_HASH_KEY]");
	$app_id =  base64_decode(urldecode($_REQUEST['link']));
	if (!(md5($app_id.$confirm_string)==$_REQUEST['key'])) {
		// set login lock for bad access
		setLoginLock($app_id);
		lum_redirect("/".getCurrentLanguage()."/validation_failed");
	}

	// make sure the application isn't locked out
	checkLoginLock($app_id);
	
	$application = getApplication($app_id);

	setSession($application);
	
	lum_redirect("/".getCurrentLanguage()."/customer_privacy");
?>
