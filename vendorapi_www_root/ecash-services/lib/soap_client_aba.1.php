<?php

require_once ('SOAP/Client.php');

class Soap_Client_ABA_1 extends SOAP_Client
{
	var $trace;

    function Soap_Client_ABA_1 ($url = "http://aba.soapdataserver.com/aba_1.php", $trace = 0)
    {
        $this->SOAP_Client($url, 0);
		$this->trace = $trace;
    }

	function Info ($routing_number, $field_list)
	{
        return $this->call (
			"Info",
			$v = array (
				"routing_number" => $routing_number,
				"field_list" => $field_list
			),
			array (
				'namespace' => 'service.aba',
				'soapaction'=>'service.aba#soap_server_aba_1#Info',
				'style'=>'rpc',
				'use'=>'encoded',
				'trace' => $this->trace
			)
		);
	}

    function Verify ($license_key, $application_id, $aba_number)
    {
        return $this->call (
			"Verify",
			$v = array (
				"license_key" => $license_key,
				"application_id" => $application_id,
				"aba_number" => $aba_number
			),
			array (
				'namespace' => 'service.aba',
				'soapaction'=>'service.aba#soap_server_aba_1#Verify',
				'style'=>'rpc',
				'use'=>'encoded',
				'trace' => $this->trace
			)
		);
    }
}

?>
