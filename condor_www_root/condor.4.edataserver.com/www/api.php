<?php
	
	require('../lib/hylafax_api.php');
	require('../lib/hylafax_job.php');
	
	if (preg_match('/^PRPC/', $_SERVER['HTTP_USER_AGENT']))
	{
		
		require('prpc/server.php');
		require('prpc/proxy.php');
		
		// process the PRPC request
		$server = new PRPC_Proxy('HylaFax_API', NULL);
		
	}
	else
	{
		
		// create a SOAP server
		$server = new SoapServer(WSDL_FILE);
		$server->setClass('HylaFax_API');
		
		// process the SOAP request
		$server->handle();
		
	}
	
?>