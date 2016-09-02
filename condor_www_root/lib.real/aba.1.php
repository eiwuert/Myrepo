<?php

require_once ('SOAP/Client.php');

class ABA_1 extends SOAP_Client
{
    function ABA_1 ($url = 'http://aba.1.soapdataserver.com/aba_1.php', $trace = 0)
	{
		$this->trace = $trace;
        $this->SOAP_Client ($url, 0);
    }

    function Info ($routing_number, $field_list)
	{
        return $this->call (
			'Info',
            $v = array (
				'routing_number' => $routing_number,
				'field_list' => $field_list
			),
			array (
				'namespace' => 'service.aba',
				'soapaction' => 'service.aba#soap_server_aba_1#Info',
				'style' => 'rpc',
				'use' => 'encoded',
				'trace' => $this->trace
			)
		);
    }

    function Verify ($license_key, $application_id, $aba_number)
	{
		return $this->call (
			'Verify',
      $v = array (
				new SOAP_Value('license_key', 'string', $license_key),
				new SOAP_Value('application_id', 'int', $application_id),
				new SOAP_Value('aba_number', 'string', $aba_number),
			),
			array (
				'namespace' => 'service.aba',
				'soapaction' => 'service.aba#soap_server_aba_1#Verify',
				'style' => 'rpc',
				'use' => 'encoded',
				'trace' => $this->trace
			)
		);
    }
}

?>
