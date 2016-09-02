<?php
	
	/**
	* This file contains the remote transport for the Site Optimization API.
	* 
	* @author Brian Feaver
	*/
	
	require_once 'maintenance_mode.php';
	require_once 'mode_test.php';
	require_once 'libolution/AutoLoad.1.php';
	
	// attempt to make sure this is a valid request
	// before we even try anything else
	if ($_SERVER['REQUEST_METHOD'] === 'POST')
	{
		
		require('../include/modules/siteopt/site_optimization.php');
			
		if (preg_match('/^PRPC/', $_SERVER['HTTP_USER_AGENT']))
		{
			//Check for maintenance_mode
	        $maintenance_mode = new Maintenance_Mode();
			if(!$maintenance_mode->Is_Online()) 
    		{
				$server = new PRPC_Proxy('Default_Site_Opt', array($mode), TRUE, TRUE);
    		}
    		else
    		{
				require('prpc/server.php');
				require('prpc/proxy.php');
							
				//Get Mode
				$mode = strtoupper(Mode_Test::Get_Mode_As_String());
				//Force to live if not set
				if($mode == "UNKNOWN") $mode = "LIVE";
				if($mode == "NW") $mode = "RC";
				DEFINE('BFW_MODE',$mode);
	
				// process the PRPC request
				$server = new PRPC_Proxy('Site_Optimization', array($mode), TRUE, TRUE);
    		}
		}
		else
		{
			include('./warning.html');
		}
		
	}
	else
	{
		include('./warning.html');
	}
	
	/**
	 * Default Site Opt
	 * Fake class
	 */
	class Default_Site_Opt
	{
		public function __construct() {}
		
		public function Landing_Page($a,$b,$c="")
		{
			return "site";
		}
	}
	
?>