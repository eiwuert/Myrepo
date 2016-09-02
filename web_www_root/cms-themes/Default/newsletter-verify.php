<?php
/*
Template: Newsletter Verify Page
Description: The contact us template
*/

	$verify_key = $_SERVER['QUERY_STRING'];
	if (strlen($verify_key) != 32)
	{
		lum_redirect('/');
	}
	
	list($msg, $ret) = lum_call('Newsletter', 'verify', array("verify_key"=>$verify_key));
	$error = null;
	if ($ret == WEB_SERVICE_ERROR)
	{	
		lum_redirect('/'.lum_getCurrentLanguage().'/email-not-verified');
	}
	else
	{
		lum_redirect('/'.lum_getCurrentLanguage().'/email-verified');
	}
