<?php

require_once 'SOAP/Client.php';

class Fcc_Soap_Client_1 extends SOAP_Client
{
	function Fcc_Soap_Client_1 ($url = "http://fcc.1.soapdataserver.com/fcc_soap_server.1.php")
	{
		$this->SOAP_Client($url, 0);
	}

	function Process_Request ($config_information, $form_data)
	{
		// config_information is a ComplexType attachment_array,
		//refer to wsdl for more info
		$config_information =& new SOAP_Value('config_information','{urn:SoapSmtp_3}config_information',$config_information);

		return $this->call
		(
			"Process_Request",
			$v = array("config_information"=>$config_information, "form_data"=>$form_data),
			array
			(
				'namespace'=>'Fast_Cash_Card_1',
				'soapaction'=>'Fast_Cash_Card_1#fast_cash_card_1#Process_Request',
				'style'=>'rpc',
				'use'=>'encoded',
				'trace'=>1,
			)
		);
	}
}

?>
