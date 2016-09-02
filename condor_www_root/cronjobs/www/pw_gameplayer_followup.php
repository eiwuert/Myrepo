<?php

	/*****************************************************/
	// Cronjob for TheGamePlayer.com	01-26-2004
	// runs every 2 hours, sends email to short_form 
	// subscribers who did not complete the Activation Step
	// - myya perez(myya.perez@thesellingsource.com) 
	/*****************************************************/

		
	// Create function Followup_Email
	/*****************************************************/

	function Followup_Email ($ole_email, $start, $end)
	{
		// Includes/Defines
		include_once('/virtualhosts/lib/mysql.3.php');
		include_once('/virtualhosts/lib/prpc/client.php');

		$sql	=	new MySQL_3();
		
		//Grab Data
		$sql->Connect("BOTH", "selsds001", "sellingsource", "%selling\$_db", Debug_1::Trace_Code(__FILE__,__LINE__));

		$query = "
			SELECT
				player_id,
				first_name,
				last_name,
				email
			FROM
				thegameplayer
			WHERE
				created_date BETWEEN '$start' AND '$end'
				AND activated = 'N'
				AND first_name NOT LIKE '%test'
				AND first_name NOT LIKE 'test%'
				AND first_name !='test' 
				AND first_name !=''
				AND last_name NOT LIKE '%test'
				AND last_name NOT LIKE 'test%'
				AND last_name !='test' 
				AND last_name !=''
			";

		$rs = $sql->Query("pw_visitor", $query, Debug_1::Trace_Code(__FILE__,__LINE__));
		//$rs = $sql->Query("rc_pw_visitor", $query, Debug_1::Trace_Code(__FILE__,__LINE__));
	
		// Send Email
		$mail = new prpc_client("prpc://smtp.2.soapdataserver.com/ole_smtp.1.php");
		$mail->setPrpcDieToFalse();
		
		while ($row = $sql->Fetch_Array_Row($rs))
		{
			$pid 		= $row['player_id'];
			$first_name = $row['first_name'];
			$last_name 	= $row['last_name'];
			$email 		= $row['email'];
			//$email 	= "myya.perez@thesellingsource.com";
		
			$data = array();
	
			//required data fields
			$data['email_primary'] = $email; 
			$data['email_primary_name'] = $first_name . ' ' . $last_name; 
			$data['site_name'] = 'TheGamePlayer.com'; 
		
			//custom data fields
			$data["active_link"] = "http://www.thegameplayer.com?page=main&player_id=".$pid;
			//$data["active_link"] = "http://rc.thegameplayer.com?page=main&player_id=".$pid;

			$mailing_id = $mail->Ole_Send_Mail($ole_email, 34676, $data);	
			
			//print "\r\nMAILING_ID: $mailing_id  OLE_EMAIL: $ole_email  EMAIL: $email";
		}
	}

	
	// Call function Followup_Email
	/*****************************************************/	

	// Initialize 2hr Notification Variables
	$start_2hr 	= date("Y-m-d h:i:s", strtotime("-2 hour -30 min"));
	$end_2hr 	= date("Y-m-d h:i:s", strtotime("-2 hour"));	
	
	// Initialize 24hr Notification Variables
	$start_24hr = date("Y-m-d h:i:s", strtotime("-24 hour -30 min"));
	$end_24hr 	= date("Y-m-d h:i:s", strtotime("-24 hour"));
	
	// Initialize Test Notification Variables
	$start_test = "2005-01-26 00:00:00";
	$end_test 	= "2005-01-26 00:00:02";	
	
		
	Followup_Email ("tgp_2_hours", $start_2hr, $end_2hr);
	
	Followup_Email ("tgp_24hours", $start_24hr, $end_24hr);
	
	//Followup_Email ("tgp_2_hours", $start_test, $end_test);

?>