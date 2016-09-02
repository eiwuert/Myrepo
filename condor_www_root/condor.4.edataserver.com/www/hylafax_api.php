<?php
	
	/**
	* This file contains the remote transport for the HylaFax API.
	*
	* @author Andrew Minerd
	*/
	
	define('CONDOR_ROOT', '/virtualhosts/condor.4.edataserver.com/www/');
	
	define('MODE_LIVE', 'LIVE');
	define('MODE_RC', 'RC');
	define('MODE_DEV', 'LOCAL');
	
	require_once('automode.1.php');
	
	$automode = new Auto_Mode();
	$mode = $automode->Fetch_Mode($_SERVER['HTTP_HOST']);
	define('EXECUTION_MODE',$mode);
	
	// get authentication information
	$username = isset($_SERVER['PHP_AUTH_USER']) ? $_SERVER['PHP_AUTH_USER'] : NULL;
	$password = isset($_SERVER['PHP_AUTH_PW']) ? $_SERVER['PHP_AUTH_PW'] : NULL;
	$logged_in = false;
	if ($username && $password)
	{
		require('../lib/config.php');
		require('../lib/security.php');
	
		$security = new Security($mode);
		$logged_in = $security->Login_User('condorapi', $username, $password);
		
	}
		
	if ($logged_in)
	{
		if (preg_match('/^PRPC/', $_SERVER['HTTP_USER_AGENT']))
		{
			
			require('prpc/server.php');
			require('../lib/prpc_proxy.php');
			require('../lib/hylafax_api.php');
			$args = array(
				$mode,
				$security
			);
			// process the PRPC request
			
			$server = new PRPC_Proxy('HylaFax_API', $args, TRUE, TRUE);
		}
		else 
		{
			include('./warning.html');
		}
	}
	else
	{
		// Print authentication headers and exit.
		header('WWW-Authenticate: Basic realm="Condor"');
		header('HTTP/1.0 401 Unauthorized');
		include('./unauthorized.html');
	}
	
	
?>