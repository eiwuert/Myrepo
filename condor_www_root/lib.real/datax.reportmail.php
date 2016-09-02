<?php


	/***
		dx.reportmail.php
		--
		datax email reporting wrapper
		
		contains a wrapper class that encapsulates
		tss smtp over prpc client interface
	***/
	
	
	require_once('prpc/client.php');
	
	
	class DX_Report_Mail
	{
		function Send($Title,$Recipients,$Body=NULL,$Attachments=NULL)
		{
			$header = new StdClass();
			$header->smtp_server = "";
			$header->port = 25;
			$header->url = "sellingsource.com";
			$header->subject = "DXSERENITY:$Title";
			$header->sender_name = "dx-serenity-noreply@dataxcorp.com";

			$message = new StdClass();
			$message->text = "$Body
			
			Generated e-mail. Do not reply.";


			$mail = new prpc_client("prpc://smtp.2.soapdataserver.com/smtp.1.php");
						
			print_r($header);
			print_r($mail);

			$mailing_id = $mail->CreateMailing("dxserenity_ffdreport", $header, NULL,NULL);

		
			print_r($mailing_id);
			print_r($mail);
			

			$package_id = $mail->AddPackage($mailing_id, $Recipients, $message, $Attachments);
			$result = $mail->SendMail ($mailing_id);
			
		}
	}


?>