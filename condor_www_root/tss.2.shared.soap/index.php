<?php
	
	define('MODE_LOCAL', 'LOCAL');
	define('MODE_RC', 'RC');
	define('MODE_LIVE', 'LIVE');
	
	// unset our session?
	if (isset($_REQUEST['force_new_session']))
	{
		unset($_COOKIE['unique_id']);
	}
	
	// Site configuration definitions
	require_once('config.php');
	
	// default page
	$page = (isset($_REQUEST['page']) ? $_REQUEST['page'] : PAGE_DEFAULT);
	
	// SOAP website
	require_once(DIR_LIB.'website.php');
	$website = new Website($layout);
	$website->Shared_Directory(DIR_SHARED);
	$website->Skins_Directory(DIR_SKINS);
	$website->Lib_Directory(DIR_LIB);
	
	// set our skin
	$website->Skin(SITE_SKIN);
	
	// import some tokens
	$website->Add_Token('title', SITE_NAME);
	
	if (($_SERVER['REQUEST_METHOD'] == 'POST') || ($page == 'preview_docs'))
	{
		// actually process the request
		$html = $website->Process_Request($_REQUEST);
	}
	else
	{
		
		// need this for hidden fields on the first page
		if (isset($_REQUEST['promo_id'])) $website->Add_Token('promo_id', $_REQUEST['promo_id']);
		if (isset($_REQUEST['promo_sub_code'])) $website->Add_Token('promo_sub_code', $_REQUEST['promo_sub_code']);
		if (isset($_REQUEST['pwadvid'])) $website->Add_Token('pwadvid', $_REQUEST['pwadvid']);
		if (isset($_REQUEST['ssforce'])) $website->Add_Token('ssforce', $_REQUEST['ssforce']);
		if (isset($_REQUEST['no_checks'])) $website->Add_Token('no_checks', $_REQUEST['no_checks']);
			
		// render the page, as defined in the
		// layout array (defined in config.php)
		$html = $website->Render_Page($page);
		
	}
	
	if (defined('DEBUG') && DEBUG)
	{
		// echo some debugging information
		$debug_info = $website->Debug_Info();
	}
	
	if ($html === FALSE)
	{
		// attempt to render the "page not found" page
		$html = $website->Render_Page(PAGE_ERROR);
		if ($html === FALSE) die();
	}
	
	// Display
	echo $html;
	if (isset($debug_info)) echo $debug_info;
	
?>
