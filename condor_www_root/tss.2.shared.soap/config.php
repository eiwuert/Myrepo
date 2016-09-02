<?php
	
	// some PHP settings
	ini_set('display_errors', 'off');
	ini_set('soap.wsdl_cache_enabled', '0');
	
	// pull local includes first
	ini_set('include_path', './lib:'.ini_get('include_path'));
	
	/*
		
		Because we're load-balanced over the four process servers,
		we can't use the default PHP session handler, which is file
		based. Instead, we're going to use the NULL session handler
		and share the session ID with the back-end.
		
	*/
	
	// start our session and regenerate a new
	// ID for security purposes (avoid session fixation)
	//session_start();
	//session_regenerate_id();
	
	require_once('null_session.1.php');
	
	// set up a session ID if we don't have one
	session_name('unique_id');
	
	// setup the null session handler
	$session = new Null_Session_1 ();
	session_set_save_handler
	(
		array (&$session, "Open"),
		array (&$session, "Close"),
		array (&$session, "Read"),
		array (&$session, "Write"),
		array (&$session, "Destroy"),
		array (&$session, "Garbage_Collection")
	);
	
	// start our session
	session_start();
	
	$mode = NULL;
	
	if (isset($_GET['mode'])) $mode = $_GET['mode'];
	elseif (isset($_SESSION['mode'])) $mode = $_SESSION['mode'];
	
	if (!in_array($mode, array(MODE_LOCAL, MODE_RC, MODE_LIVE)))
	{
		// determine our mode automatically
		$mode = Get_Mode($_SERVER['HTTP_HOST']);
	}
	
	// debugging mode?
	if (isset($_GET['debug'])) $debug = ($_GET['debug'] == TRUE);
	else $debug = (isset($_SESSION['debug']) && $_SESSION['debug']);
	
	// can't use debug mode on live
	$debug = ($debug && ($mode !== MODE_LIVE));
	
	// set our configuration variables
	define('MODE', $mode);
	define('DEBUG', $debug);
	
	// can't do this anymore -- we don't have a local session!
	//$_SESSION['mode'] = $mode;
	
	// get our configuration
	Get_Config($mode);
	
	// define default values for these
	if (!defined('DIR_CODE')) define('DIR_CODE', dirname(__FILE__));
	if (!defined('DIR_SKINS')) define('DIR_SKINS', DIR_CODE.'/skins/');
	if (!defined('DIR_SHARED')) define('DIR_SHARED', DIR_CODE.'/shared/');
	if (!defined('DIR_LIB')) define('DIR_LIB', DIR_CODE.'/lib/');
	if (!defined('PAGE_ERROR')) define('PAGE_ERROR', 'try_again');
	if (!defined('PAGE_DEFAULT')) define('PAGE_DEFAULT', 'app_allinone');
	
	// determine our mode from our URL
	function Get_Mode($url)
	{
		
		if (preg_match('/\.ds(\d+)\.tss/', $url))
		{
			$mode = MODE_LOCAL;
		}
		elseif (preg_match('/^rc\./', $url))
		{
			$mode = MODE_RC;
		}
		else
		{
			$mode = MODE_LIVE;
		}
		
		return($mode);
		
	}
	
?>
