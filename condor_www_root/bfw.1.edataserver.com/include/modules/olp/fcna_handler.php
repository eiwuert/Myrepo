<?php
/**
	@version:
		1.0.0 2005-04-15 - FCNA processing
	@author:
		Jason Duffy - version 1.0.0
	@Updates:
	@Todo:
*/

class FCNA_Handler
{
	private $collected_data;
	private $config;
	
	public function __construct(&$olp, $collected_data, &$applog = NULL)
	{
		$this->config = $olp->Get_Config();
		$this->collected_data = $collected_data;
		
		if($applog != NULL)
		{
			$applog->Write('FCNA_Handler was instantiated');
		}
	}

	public function Execute()
	{
		// create email
		$recipient = new StdClass ();
		$recipient->type = "to";
		$recipient->name = $this->collected_data['email_primary'];
		$recipient->address = $this->collected_data['email_primary'];

		$header = new StdClass ();
		$header->port = 25;
		$header->url = $this->collected_data['client_url_root'];
		$header->subject = "New Account at ".$this->config->site_name;
		$header->sender_name = $this->config->site_name." - Approval Department";
		$header->sender_address = "no-reply@".$this->config->site_name;

		$message = new StdClass ();
		$message->html .= "Dear ".$this->collected_data["name_first"]." ".$this->collected_data["name_last"].",<BR>";
		$message->html .= "Thank you for your recent visit to ".$this->config->site_name.".<BR><br>";
		$message->html .= "You are pre-qualified to receive a cash advance of up to $500 on your next pay check. You can have your money as soon as tomorrow!  <BR><BR>";
		$message->html .= "* No Credit Check <br>* Get up to $500 by tomorrow  <br><br>";
		$message->html .= "YOUR CASH IS JUST MINUTES AWAY! <br><br>PLEASE READ - You must do the following to get your Cash!<br><br>";
		$message->html .= "PLEASE READ - In order to VALIDATE your cash advance application YOU MUST CLICK the link below! <br><br>";
		$message->html .= "<a href=\"http://".$this->config->site_name."/?page=fcna_return&unique_id=".session_id()."\">Click Here To Validate</a><br><br>";
		$message->html .= "Thank you, <br>";
		$message->html .= $this->config->site_name;

		// Send the email
		include_once("prpc/client.php");
		$mail = new prpc_client("prpc://smtp.2.soapdataserver.com/smtp.1.php");
		$mailing_id = $mail->CreateMailing ("New Account At ".$this->config->site_name, $header, NULL, NULL);
		$mail->AddPackage ($mailing_id, array ($recipient), $message, array ());
		$mail->SendMail ($mailing_id);
	}

}

?>