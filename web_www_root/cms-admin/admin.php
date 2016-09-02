<?php

global $lumRegistry;

//init_plugins_admin_menu();

$pb = new LuminancePageBuilder($lumRegistry);
$pb->parseUrl($_SERVER['REQUEST_URI']);
$bits = $pb->getBits();

// admin urls are formatted like this
// /tools/<plugin>/<action>
// let's figure out which plugin is in the url, if any
// and which action has been chosen

$plugin = '';
$action = '';
$offset = count(explode("/", substr(BASE_URL_OFFSET,1))) -1;
if (isset($bits[$offset+1]))
	$plugin = $bits[$offset+1];
if (isset($bits[$offset+2]))
	$action = $bits[$offset+2];

if (!$plugin)
	$plugin = "Dashboard";

if (!$action)
	$action = "list";

$user = lum_call('Users', 'isSignedIn');


include_once('admin_header.php');

if ($user)
{
	if($_SERVER['QUERY_STRING'] == 'sign-out')
	{
		lum_call('Users', 'signOut');
		lum_redirect();
	}
	include_once('admin_content.php');
}
else
{
	if($_SERVER['QUERY_STRING'] == 'sign-out')
	{
		lum_redirect();
	}
	
	if ($_SERVER['QUERY_STRING'] == 'forgot-password')
	{
		include_once('admin_forgot.php');
	}
	else
	{
		$_REQUEST['redirect'] = $_SERVER['REQUEST_URI'];
		include_once('admin_sign_in.php');
	}
}

include_once('admin_footer.php');

?>
