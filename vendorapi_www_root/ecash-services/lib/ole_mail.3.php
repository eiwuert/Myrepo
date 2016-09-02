<?php
	function epm_collect ($email, $name_first, $name_last, $site_id, $list_id, $ip_address, $debug = 0)
	{
		if (!class_exists ('xmlrpc_client'))
		{
			include_once "/virtualhosts/lib/xmlrpc_client.2.php";
		}
		
		$info_array["email"]=$email;
		$info_array["first"]=$name_first;
		$info_array["last"]=$name_last;
		$info_array["list_id"]=$list_id;
		$info_array["site_id"]=$site_id;
		$info_array["IPaddress"]=$ip_address;
		$info_array["licensekey"]= $_SESSION['config']->license;
		$info_array["ole_version"]= "ole_mail.3.php";
		
		$soap_client = new xmlrpc_client ('/',"ole.1.soapdataserver.com", 80);
		$soap_client->setDebug ($debug);
		$soap_call = new xmlrpcmsg ("epm_collect", array (php_xmlrpc_encode ($info_array)));
		$soap_result = $soap_client->send ($soap_call);
		return TRUE;
	}
?>
