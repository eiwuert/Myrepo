<?php
	if (!class_exists ('xmlrpc_client'))
	{
		include_once "/virtualhosts/lib/xmlrpc_client.1.php";
	}
	class epm_collect
	{
		function epm_collect ($email, $name_first, $name_last, $site_id, $list_id, $ip_address)
		{
			$info_array["email"]=$email;
			$info_array["first"]=$name_first;
			$info_array["last"]=$name_last;
			$info_array["list_id"]=$list_id;
			$info_array["site_id"]=$site_id;
			$info_array["IPaddress"]=$ip_address;
			$info_array["licensekey"]= $_SESSION['config']->license;
			$info_array["ole_version"]= "ole_mail.1.php";
			
			$soap_client = new xmlrpc_client ('/',"ole.1.soapdataserver.com", 80);
			//$soap_client->setDebug (1);
			$soap_call = new xmlrpcmsg ("epm_collect", array (xmlrpc_encode ($info_array)));
			$soap_result = $soap_client->send ($soap_call);
			return TRUE;
		}
	}
?>
