<?php
	// Version 1.0.0
	// Obtain site config

	define ("CONFIG_SOAP_SERVER", "config.soapdataserver.com");
	define ("CONFIG_SOAP_PATH", "/init/");
	define ("CONFIG_SOAP_PORT", "80");
	
	require_once ("error.2.php");
	require_once ("debug.1.php");
	require_once ("xmlrpc_client.1.php");

	class Config_1
	{
		function Config_1 ()
		{
			return TRUE;
		}
	
		function Get_Site_Config ($property_name, $site_name, $page_name, $promo_id)
		{
			$soap_client = new xmlrpc_client (CONFIG_SOAP_PATH, CONFIG_SOAP_SERVER, CONFIG_SOAP_PORT);
			//$soap_client->setDebug (1);
			
			$args = array (
				"property_name" => $property_name,
				"site_name" => $site_name,
				"page_name" => $page_name,
				"promo_id" => $promo_id
			);
			
			$soap_call = new xmlrpcmsg ("Default", array (xmlrpc_encode ($args)));
			
			$soap_result = $soap_client->send ($soap_call);
			
			return xmlrpc_decode_obj ($soap_result->value ());
		}
	}

?>