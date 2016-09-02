<?php

	/*****************************************************/
	// Cronjob for Trimpatch
	// runs every half hour, sends email to short_form subscribers
	// who did not complete the Authorize.net long_form
	// - myya perez(myya.perez@thesellingsource.com), 12-21-2004
	/*****************************************************/

		
	// Create function Followup_Email
	/*****************************************************/

	function Followup_Email ($landing_page, $lp_email)
	{
		// Includes/Defines
		include_once('/virtualhosts/lib/mysql.3.php');
		include_once('/virtualhosts/lib/prpc/client.php');

			
		// Initialize
		$start = date("Y-m-d h:i:s", strtotime("-1 hour"));
		$end = date("Y-m-d h:i:s", strtotime("-30 minutes"));	
	
		$sql	=	new MySQL_3();
		
		//Grab Data
		$sql->Connect("BOTH", "selsds001", "sellingsource", "%selling\$_db", Debug_1::Trace_Code(__FILE__,__LINE__));

		$query= "
			SELECT
				customer_id,
				first_name,
				last_name,
				phone,
				email
			FROM
				$landing_page
			WHERE
				created_date BETWEEN '$start' AND '$end'
				AND !authorize_net_response
				AND first_name NOT LIKE '%test'
				AND first_name NOT LIKE 'test%'
				AND first_name !='test' 
				AND first_name !=''
				AND last_name NOT LIKE '%test'
				AND last_name NOT LIKE 'test%'
				AND last_name !='test' 
				AND last_name !=''
			";
	
		$rs = $sql->Query("tss_visitor", $query, Debug_1::Trace_Code(__FILE__,__LINE__));
	
		// Send Email
		$mail = new prpc_client("prpc://smtp.2.soapdataserver.com/ole_smtp.1.php");
		$mail->setPrpcDieToFalse();
		
		while ($row = $sql->Fetch_Array_Row($rs))
		{
			$customer_id = $row['customer_id'];
			$first_name = $row['first_name'];
			$last_name = $row['last_name'];
			$phone = $row['phone'];
			$email = $row['email'];
			//$email = "myya.perez@thesellingsource.com";
		
			$data = array();
	
			//required data fields
			$data['email_primary'] = $email; 
			$data['email_primary_name'] = $first_name . ' ' . $last_name; 
			$data['site_name'] = 'Healthy Aspect'; 
		
			//custom data fields
			$data['link'] = 'http://greatweboffers.com/'.$landing_page.'/index.php?page=cc_redirect&x_first_name='.$first_name.'&x_last_name='.$last_name.'&x_email='.$email.'&x_phone='.$phone.'&sid='.$customer_id;	
	
			$mailing_id = $mail->Ole_Send_Mail($lp_email, 34676, $data);	
		}
		
	}

	
	// Call function Followup_Email for each Landing Page
	/*****************************************************/	

	Followup_Email ("trimpatch", "30 min trim patch");
	
	Followup_Email ("trimpatch_newyear", "30 min trim patch");
	
	Followup_Email ("sleepeze", "30 min sleepeze");
	
	Followup_Email ("sleepeze2", "30 min sleepeze");


?>