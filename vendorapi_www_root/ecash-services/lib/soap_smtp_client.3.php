<?php
	require_once ('SOAP/Client.php');

	class SoapSmtpClient_3 extends SOAP_Client
	{
		var $trace;

		function SoapSmtpClient_3 ($server_url = 'soap.maildataserver.com', $trace = 0)
		{
			$this->SOAP_Client("http://".$server_url."/soap_smtp.3.php", 0);
			$this->trace = $trace;
		}

		function CreateMailing($name, $header, $schedule_date, $schedule_time)
		{
			return $this->call
			(
				"CreateMailing",
				$v = array("name"=>$name, "header"=>$header, "schedule_date"=>$schedule_date, "schedule_time"=>$schedule_time),
				array
				(
					'namespace'=>'SoapSmtp_3',
					'soapaction'=>'SoapSmtp_3#soapsmtp_3#CreateMailing',
					'style'=>'rpc',
					'use'=>'encoded',
					'trace' => $this->trace
				)
			);
		}

		function AddPackage($mailing_id, $recipient, $message, $attachment)
		{
			// attachment is a ComplexType attachment_array,
			//refer to wsdl for more info
			$attachment =& new SOAP_Value('attachment','{urn:SoapSmtp_3}attachment_array',$attachment);

			// recipient is a ComplexType recipient_array,
			//refer to wsdl for more info
			$recipient =& new SOAP_Value('recipient','{urn:SoapSmtp_3}recipient_array',$recipient);
			return $this->call
			(
				"AddPackage",
				$v = array("mailing_id"=>$mailing_id, "recipient"=>$recipient, "message"=>$message, "attachment"=>$attachment),
				array
				(
					'namespace'=>'SoapSmtp_3',
					'soapaction'=>'SoapSmtp_3#soapsmtp_3#AddPackage',
					'style'=>'rpc',
					'use'=>'encoded',
					'trace' => $this->trace
				)
			);
		}

		function SendMail($mailing_id)
		{
			return $this->call
			(
				"SendMail",
				$v = array ("mailing_id"=>$mailing_id),
				array
				(
					'namespace'=>'SoapSmtp_3',
					'soapaction'=>'SoapSmtp_3#soapsmtp_3#SendMail',
					'style'=>'rpc',
					'use'=>'encoded',
					'trace' => $this->trace
				)
			);
		}
	}
?>
