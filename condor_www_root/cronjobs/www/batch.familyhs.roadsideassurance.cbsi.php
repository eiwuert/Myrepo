<?php

	//============================================================================
	// Cronjob for affinity landing pages
	// runs every Monday at 4am
	// sends the completed orders to CBSI
	// - mel leonard(mel.leonard@thesellingsource.com), 01-06-2005
	//============================================================================

		
	// Includes/Defines
	//============================================================================
	
	include_once('/virtualhosts/lib/mysql.3.php');
	include_once('/virtualhosts/lib/prpc/client.php');
			
	// Initialize
	//============================================================================

	$start = date("Y-m-d h:i:s", strtotime("-1 week"));
	$end = date("Y-m-d h:i:s");	
	
	$sql	=	new MySQL_3();
		
	// Grab Data 
	//============================================================================
	
	$sql->Connect("BOTH", "selsds001", "sellingsource", "%selling\$_db", Debug_1::Trace_Code(__FILE__,__LINE__));

	
	$query= "
		SELECT
			authorize_net_response.x_cust_id as member_id,
			visitors.first_name as first_name,
			visitors.last_name as last_name,
			visitors.address_1 as address,
			visitors.city as city,
			visitors.state as state,
			visitors.zip as zip,
			visitors.home_phone as phone,
			DATE_FORMAT(authorize_net_response.created_date, '%m%d%y') as activation_date,
			visitors.email as email,
			visitors.landing_page_id as landing_page
		FROM
			visitors
		INNER JOIN authorize_net_response ON (visitors.id = authorize_net_response.visitor_id)
		WHERE
			authorize_net_response.created_date BETWEEN '$start' AND '$end'
			AND visitors.auth_net_response = '1'
			AND (visitors.landing_page_id = '6' OR visitors.landing_page_id ='7')
			AND visitors.first_name !='%test'
			AND visitors.first_name !='test%'
			AND visitors.first_name !='test' 
			AND visitors.first_name !=''
			AND visitors.last_name !='%test'
			AND visitors.last_name !='test%'
			AND visitors.last_name !='test' 
			AND visitors.last_name !=''
		ORDER BY activation_date ASC
		";

	$result = $sql->Query("lp", $query, Debug_1::Trace_Code(__FILE__,__LINE__));
	$total_found = $sql->Row_Count($result);
	
	if ($total_found > 0)
	{
		// Create prn file
		//==========================================================================	
		
		$prn = "";
		while ($row = $sql->Fetch_Array_Row ($result))
		{
			
			//familyhs client_id = 9979
			//roadsideassurance client_id = 9980
			$client_id = ($row["landing_page"] == "6") ? "9979" : "9980"; 
			
			$prn .= join ('',
				array(
					str_pad ($client_id, "4", " ", STR_PAD_RIGHT),
					str_pad ($row["member_id"], "12", " ", STR_PAD_RIGHT),
					str_pad ($row["first_name"], "20", " ", STR_PAD_RIGHT),
					str_pad ("", "1", " ", STR_PAD_RIGHT),
					str_pad ($row["last_name"], "20", " ", STR_PAD_RIGHT),
					str_pad ($row["address"], "30", " ", STR_PAD_RIGHT),
					str_pad ($row["city"], "25", " ", STR_PAD_RIGHT),
					str_pad ($row["state"], "2", " ", STR_PAD_RIGHT),
					str_pad ($row["zip"], "10", " ", STR_PAD_RIGHT),
					str_pad ($row["phone"], "10", " ", STR_PAD_RIGHT),
					str_pad ("", "10", " ", STR_PAD_RIGHT),
					str_pad ($row["activation_date"], "6", " ", STR_PAD_RIGHT),
					str_pad ("N", "1", " ", STR_PAD_RIGHT),
					str_pad ($row["email"], "30", " ", STR_PAD_RIGHT),
					str_pad ("", "8", " ", STR_PAD_RIGHT)
				)
			) . "\r\n";
		}
		
		//setup the mail 
		
		$header = new StdClass ();
		$header->subject = 'FamilyHS.com And RoadSideAssurance.com Orders ';
		$header->sender_name = 'Mel Leonard';
		$header->sender_address = 'mel.leonard@thesellingsource.com';
		
		$recipient1 = new StdClass ();
		$recipient1->type = 'to';
		$recipient1->name = '';
		$recipient1->address = 'memberships@consumerbenefit.com';
		//$recipient1->address = 'mel.leonard@thesellingsource.com';
		
		$recipient2 = new StdClass ();
		$recipient2->type = 'cc';
		$recipient2->name = '';
		$recipient2->address = 'mel.leonard@thesellingsource.com';
		
		$recipients = array ($recipient1, $recipient2);
		
		$message = new StdClass ();
		$message->text = "Weekly Submits Between ".$start. "and". $end ."\r\n";
		
		$attach = new stdClass ();
		$attach->name = "orders_".$start."_".$end.".prn";
		$attach->content = base64_encode ($prn);
		$attach->content_type = "text/x-prn";
		$attach->content_length = strlen ($prn);
		$attach->encoded = "TRUE";
		
		
		
		$mail = new prpc_client("prpc://smtp.2.soapdataserver.com/smtp.1.php");
		
		$mailing_id = $mail->CreateMailing ("FamilyHS.com and RoadSideAssurance.com Weekly Orders", $header, NULL, NULL);
		$package_id =$mail->AddPackage ($mailing_id, $recipients, $message, array ($attach));
		$result = $mail->SendMail ($mailing_id);
	}

?>