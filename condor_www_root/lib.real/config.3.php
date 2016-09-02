<?php
	// Version 3.0.0
	// Obtain site config

	define ("CONFIG_3_SOAP_HOST", "config.soapdataserver.com");
	define ("CONFIG_3_SOAP_PATH", "/init_3/");
	define ("CONFIG_3_SOAP_PORT", "80");
	
	require_once ("error.2.php");
	require_once ("debug.1.php");
	require_once ("xmlrpc.1.php");

	class Config_3
	{
		function Config_3 ()
		{
			return TRUE;
		}
	
		function Get_Site_Config ($license, $promo_id = NULL, $promo_sub_code = NULL)
		{
			$promo_id = is_null ($promo_id) ? 10000 : $promo_id;
			$promo_sub_code = trim ($promo_sub_code);
		
			$args = array ( 'license' => $license, 'promo_id' => $promo_id, 'promo_sub_code' => $promo_sub_code, 'server' => $_SERVER );
			$response = xmlrpc_request (CONFIG_3_SOAP_HOST, CONFIG_3_SOAP_PORT, CONFIG_3_SOAP_PATH, "Soap_Default", $args, $debug);

			if (@$response ['XMLRPC_RESULT'] === FALSE)
			{
				print_r ($response);
				exit;
			}
			
			$result = unserialize (base64_decode($response[0]));
			
			if (Error_2::Check ($result))
			{
				return $result;
				// the below won't happen while this line is above
				print_r ($result);
				exit;
			}
			
			if (is_array ($result))
			{
				$obj = new stdClass ();
				foreach ($result as $key => $val)
				{
					$obj->$key = $val;
				}
				
				return $obj;
			}
			
			return $result;
		}
	}

?>
