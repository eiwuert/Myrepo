<?php

/*
** Require files
*/
	require_once ('prpc/client.php');


/*
** Instantiate the client
*/
	$server = new Prpc_Client ('prpc://stat.1.soapdataserver.com/prpc_stat_promo_single.php');


/*
** Invoke a method on the server
*/
	$promo_id = 12016;
	$start_date = date ('Y-m-d', strtotime ('-7 days'));
	$end_date = date ('Y-m-d', strtotime ('-1 day'));
	$columns = array ('visitors', 'accepted');
	
	$result = $server->Stat_Promo_Single ($promo_id, $start_date, $end_date, $columns);

	$visitors = $result [$promo_id]['visitors'];

/*
** Prepare the email
*/
	$header = new StdClass ();
	$header->url = "sellingsource.com";
	$header->subject = "ATM application count for ".$start_date." to ".$end_date;
	$header->sender_name = "ATM Batch";
	$header->sender_address = "batch@sellingsource.com";

	$recipient = new StdClass ();
	$recipient->type = "to";
	$recipient->name = "Natalie Dempsey";
	$recipient->address = "ndempsey@41cash.com";
	//$recipient->address = "rodricg@sellingsource.com";

	$message = new StdClass ();
	$message->text =
"

For the week of ".$start_date." to ".$end_date." there are ".$visitors." applications.

";

	Send_Mail ($header, $message, array ($recipient), 0);


/*
** Functions
*/
	function Send_Mail ($header, $message, $recipient_array, $try_count)
	{
		$mail = new Prpc_Client ("prpc://smtp.2.soapdataserver.com/smtp.1.php");
	
		$mailing_id = $mail->CreateMailing ("Batch - ATM", $header, NULL, NULL);
	
		if ($mailing_id > 0)
		{
			$package_id = $mail->AddPackage ($mailing_id, $recipient_array, $message, array ());
			$result = $mail->SendMail ($mailing_id);
			
			//echo "Result: $result\n";
		}
		else if ($try_count < 3)
		{
			sleep (3);
			Send_Mail ($header, $message, $recipient_array, ++$try_count);
		}
	
		return TRUE;
	}
?>
