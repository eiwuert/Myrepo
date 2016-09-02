<?php
/**
 * Copyright 2009 - 2011 Color Shift, Inc.
 * 
 * @package Luminance v4.0
 *
 * Does all of our start up
 * 
 **/

	// error handler
	set_error_handler("lum_errorHandler");

	// load in all of our supporing classes	
	require_once(INCLUDES_PATH . 'pear.mysql.php');
	
	if (is_file(INCLUDES_PATH . 'hash_hmac.php'))
		require_once(INCLUDES_PATH . 'hash_hmac.php');
	
	require_once(INCLUDES_PATH . 'class.phpmailer.php');	
	require_once(INCLUDES_PATH . 'class.smtp.php');
	
	// only load this in if we plan on doing any mobile or tablet support
	if (lum_needDeviceDetection() && lum_useLocalDeviceDetection())
		require_once(INCLUDES_PATH . 'wurfl/TeraWurfl.php'); // for mobile detection!

	// now our application classes
	require_once(APPLICATION_PATH . 'luminance.model.class.php');
	require_once(APPLICATION_PATH . 'luminance.view.class.php');	
	require_once(APPLICATION_PATH . 'luminance.cache.class.php');
	require_once(APPLICATION_PATH . 'luminance.registry.class.php');
	require_once(APPLICATION_PATH . 'luminance.pagebuilder.class.php');

	// ok, now we can start our session
	if (!session_id())
		session_start();
	
	// start up the registry
	$lumRegistry = new LuminanceRegistry;
	$lumPageBuilder = '';
	
	// script will die if a core plugin is missing - look in cms-includes/functions.inc.php
	lum_checkCorePlugins();

	// now load in our core plugins
	require_once(PLUGINS_PATH . 'Languages/luminance.plugin.class.php');
	require_once(PLUGINS_PATH . 'Strings/luminance.plugin.class.php');	
	require_once(PLUGINS_PATH . 'Pages/luminance.plugin.class.php');

	// start the database connection
	$lumRegistry->db = new PearWrapper(array(
		'phptype'  => "mysql",
		'hostspec' => DB_HOST,
		'database' => DB_NAME,
		'username' => DB_USER,
		'password' => DB_PASSWORD
	));


	// having the database is a must!
	// if we can't connect. Let's say
	// the page is unavailable!
	if (!$lumRegistry->db->connect())
	{
		$lumPageBuilder = new LuminancePageBuilder($lumRegistry);
		$lumPageBuilder->showNotAvailable();
		exit;
	}

	function lum_start()
	{
		global $lumRegistry, $lumPageBuilder;
		
		$lumRegistry->theme = lum_getTheme();
		define('TEMPLATES_PATH', ROOT_PATH.'cms-themes/'.$lumRegistry->theme.'/');
		define('THEME_URL', '/cms-themes/'.$lumRegistry->theme.'/');
		
		// start up our page builder. It will create the final output and display it
		$lumPageBuilder = new LuminancePageBuilder($lumRegistry);
		
		if (isset($_SERVER['LUM_FORCE_PAGE']) && isset($_SERVER['LUM_VERIFY_FORCE_PAGE']))
			$_SERVER['REQUEST_URI'] = '/'.$_SERVER['LUM_FORCE_PAGE'];
		
		// parse out the URL to get our URL 'bits' - the pieces of the URL that correspond to various plugins
		$lumPageBuilder->parseUrl($_SERVER['REQUEST_URI']);
		
		// before we build the page we need to know which language we're using
		$lumLanguages = new LuminancePluginLanguages($lumRegistry);
		
		// pass in the url bits. We're looking at the first bit to see if it matches a language code
		// we then return a modified array of bits in case the first one was a language code
		$lumPageBuilder->setBits($lumLanguages->setLanguage($lumPageBuilder->getBits()));
		
		//load the string table 
		$lumPageBuilder->loadStringTable();
		
		// display the page
		$lumPageBuilder->servePage();
	}

	// this is for the admin service
	function lum_start_admin_rpc()
	{
		global $lumRegistry, $lumPageBuilder;
		
		$lumRegistry->theme = lum_getTheme();
		
		define('TEMPLATES_PATH', ROOT_PATH.'cms-themes/'.$lumRegistry->theme.'/');		
		define('THEME_URL', '/cms-themes/'.$lumRegistry->theme.'/');
		
		$lumLanguages = new LuminancePluginLanguages($lumRegistry);
		$lumLanguages->setDefaultLanguage();
		
		$lumPageBuilder = new LuminancePageBuilder($lumRegistry);
		$lumPageBuilder->loadStringTable();		
	}

?>
