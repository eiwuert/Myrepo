<?php
	// Version 2.1.0
	// Obtain site config

	// 2.0.1 - Check for faults
	// 2.1.0 - Use a config cache

	define ("CONFIG_2_SOAP_SERVER", "config.soapdataserver.com");
	define ("CONFIG_2_SOAP_PATH", "/init/");
	define ("CONFIG_2_SOAP_PORT", "80");
	
	require_once ("error.2.php");
	require_once ("debug.1.php");
	require_once ("xmlrpc_client.2.php");

	class Config_2
	{
		function Config_2 ()
		{
			return TRUE;
		}
	
		function Get_Site_Config ($property_name, $site_name, $page_name, $promo_id, $debug = FALSE)
		{
			global $_Config_2_Cache;
			
			$config_guid = md5 ($property_name.$site_name.$page_name.$promo_id);
			
			if (! $debug && isset ($_Config_2_Cache [$config_guid]))
			{
				return $_Config_2_Cache [$config_guid];
			}
			
			$soap_client = new xmlrpc_client (CONFIG_2_SOAP_PATH, CONFIG_2_SOAP_SERVER, CONFIG_2_SOAP_PORT);
			//$soap_client->setDebug ($debug, __FILE__, __LINE__);
			//$soap_client->setDebug (TRUE, __FILE__, __LINE__);
			
			$args = array (
				"property_name" => $property_name,
				"site_name" => $site_name,
				"page_name" => $page_name,
				"promo_id" => $promo_id
			);
			$soap_call = new xmlrpcmsg ("Default", array (php_xmlrpc_encode ($args)));
			$soap_result = $soap_client->send ($soap_call);
			
			if ($debug)
			{
				echo "\n\nConfig_2::Get_Site_Config Debug\n\n";
				exit;
			}
			
			if ($soap_result->faultCode ())
			{
				echo "A configuration error has occured. The site admin has been notified. Please try your request again in 30 minutes.";
				echo "\n\n<!--SF:".$soap_result->faultCode ().":".$soap_result->faultString ()."-->\n\n";
				exit;
			}
			
			$_Config_2_Cache [$config_guid] = php_xmlrpc_decode_obj ($soap_result->value ());
			
			return $_Config_2_Cache [$config_guid];
		}
	}

?>
