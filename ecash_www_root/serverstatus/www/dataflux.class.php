<?php

require_once("status_base.class.php");

class Server_Status extends Status_Base
{
	public function __construct()
	{
	}

	public function Run_Tests()
	{
		//if any fail, then FAIL

		//just connect/disconnect
		//if(!$this->MySQL_Test(DB_HOST, DB_USER, DB_PASS)) return FALSE;

		//run a query (it will return the result)
		//must specify schema.table in query
		//if(!$this->MySQL_Test(DB_HOST, DB_USER, DB_PASS, "select user from mysql.user")) return FALSE;

		// test dataflux service
		if(!$this->Dataflux_Test()) return FALSE;

		//open/close a temp file
		if(!$this->HD_Test()) return FALSE;

		//write a temp file, and read back what you wrote
		if(!$this->HD_Test("monkey")) return FALSE;

		//otherwise PASS
		return TRUE;
	}

	public function Dataflux_Test()
	{
        	ini_set("soap.wsdl_cache_enabled","0");
        	$soapClient = new SoapClient('dataflux.wsdl');
        	$svcname = array(
        	"serviceName"=>"phone_type_verify.dmc",
        	"fieldDefinitions"=>array("fieldName"=>"phone_number","fieldType"=>"STRING","fieldLength"=>"15"),
        	"dataRows"=>array("value"=>$phone,"reserved"=>1)
        	);

        	try
        	{
                	$res = $soapClient->ExecuteArchitectService($svcname);
        	}
        	catch(SoapFault $soapFault)
        	{
                	return FALSE;
        	}

        	return TRUE;
	}

}

?>
