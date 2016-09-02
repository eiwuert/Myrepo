<?php
	require_once 'SOAP/Client.php';

	class Smtp_Mail_Soap_Client extends SOAP_Client
	{
		function Smtp_Mail_Soap_Client ($smtp_mail_soap_server)
		{
			$this->SOAP_Client ("http://".$smtp_mail_soap_server."/smtp_mail.1.php", 0);
		}

		function Send_Mail ($deliverable, $header)
		{
			// header is a ComplexType header,
			//refer to wsdl for more info
			$header =& new SOAP_Value ('header','{urn:smtp_mail}header',$header);

			// deliverable is a ComplexType header,
			//refer to wsdl for more info
			$deliverable =& new SOAP_Value ('deliverable','{urn:smtp_mail}deliverable',$deliverable);

			return $this->call
			(
				"Send_Mail",
				$v = array("deliverable"=>$deliverable, "header"=>$header),
				array
				(
					'namespace'=>'smtp_mail',
					'soapaction'=>'smtp_mail#soap_smtp#Send_Mail',
					'style'=>'rpc',
					'use'=>'encoded',
					//'trace' => 1,
				)
			);
		}
	}
?>
