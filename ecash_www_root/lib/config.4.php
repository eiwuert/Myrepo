<?php
	// Version 4.0.0
	// Obtain site config

	if ( isset($_SERVER["SERVER_NAME"]) AND preg_match("/\.(ds\d{2}|dev\d{2})\.tss$/i", $_SERVER["SERVER_NAME"], $matched) )
	{
		//define ("CONFIG_4_HOST", "tss.config.soapdataserver.com.{$matched[1]}.tss");
		define ("CONFIG_4_HOST", "config.1.soapdataserver.com");
	}
	else
	{
		define ("CONFIG_4_HOST", "config.1.soapdataserver.com");
	}
	define ("CONFIG_4_PATH", "/init_4/");

	require_once ("error.2.php");
	require_once ("debug.1.php");
	require_once ("prpc/client.php");

	class Config_4
	{
		function Config_4 ()
		{
			return TRUE;
		}
	
		function Get_Site_Config ($license,
											$promo_id = NULL,
											$promo_sub_code = NULL,
											$site_type = NULL)
		{
			$promo_id = is_null ($promo_id) ? 10000 : $promo_id;
			$promo_sub_code = trim ($promo_sub_code);
													
			// Set up the parameters (notice the format for the server)
			$server = "prpc://". CONFIG_4_HOST . CONFIG_4_PATH;

			// Build the object
			$init_obj = new Prpc_Client ($server);

			$response = $init_obj->Get_Init($license, $promo_id, $promo_sub_code, $site_type);

			return $response;
		}
	}
?>
