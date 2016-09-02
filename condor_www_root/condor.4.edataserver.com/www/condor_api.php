<?php
	/**
	* This file contains the remote transport for the Condor API.
	* 
	* @author Brian Feaver
	*/
	
	// attempt to make sure this is a valid request
	// before we aeven try anything else
	if ($_SERVER['REQUEST_METHOD'] === 'POST')
	{
		
		require_once('automode.1.php');
		
		$automode = new Auto_Mode();
		$mode = $automode->Fetch_Mode($_SERVER['HTTP_HOST']);
		define('EXECUTION_MODE',$mode);
		
		$logged_in = FALSE;
		
		$username = ($_SERVER['PHP_AUTH_USER'] ? $_SERVER['PHP_AUTH_USER'] : FALSE);
		$password = ($_SERVER['PHP_AUTH_PW'] ? $_SERVER['PHP_AUTH_PW'] : FALSE);
		
		if ($username && $password)
		{
			require('../lib/config.php');
			require('../lib/condor.class.php');
			require('../lib/security.php');
			
			$security = new Security($mode);
			$logged_in = $security->Login_User('condorapi', $username, $password);
			$user_id = $security->Get_Agent_ID();
			$company_id = $security->Get_Company_ID();
		}
		
		if ($logged_in)
		{
			if (preg_match('/^PRPC/', $_SERVER['HTTP_USER_AGENT']))
			{
				
				require('prpc/server.php');
				require('prpc/proxy.php');

				
				// We need to pass the mode and user_id to condor.
				$condor_args = array($mode, $user_id, $company_id, $username, $password);
				
				// process the PRPC request
				$server = new PRPC_Proxy('Condor', $condor_args, TRUE, TRUE);
				
			}
			else
			{
				// create a SOAP server
				$server = new SoapServer(WSDL_FILE);
				$server->setClass('Condor', $mode, $user_id, $company_id);
				
				// process the SOAP request
				$server->handle();
				
			}
			
		}
		else
		{
			
			// Print authentication headers and exit.
			header('WWW-Authenticate: Basic realm="Condor"');
			header('HTTP/1.0 401 Unauthorized');
			
			include('./unauthorized.html');
			
		}
		
	}
	else
	{
		include('./warning.html');
	}
	
?>
