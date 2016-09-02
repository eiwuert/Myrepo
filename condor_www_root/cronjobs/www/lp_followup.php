<?php

	/*****************************************************/
	// Cronjob for Landing Pages
	// runs every half hour, sends email to short_form (A) subscribers
	// who did not complete the long_form (B)
	// - myya perez(myya.perez@thesellingsource.com), 02-02-2005
	/*****************************************************/

	// Includes/Defines
	include_once('/virtualhosts/lib/mysql.3.php');
	include_once('/virtualhosts/lib/prpc/client.php');
	require_once("/virtualhosts/lib/diag.1.php");
	require_once("/virtualhosts/lib/lib_mode.1.php");
	
	
	switch (Lib_Mode::Get_Mode())
	{
	case MODE_LOCAL:
		Diag::Enable();
		define('DB_NAME', 'rc_lp');
		break;
	case MODE_RC:
		default:
		define('DB_NAME', 'rc_lp');
		break;
	case MODE_LIVE:
		define('DB_NAME', 'lp');
		break;
	}		

	$sql = new MySQL_3();
	$sql->Connect("BOTH", "selsds001", "sellingsource", "%selling\$_db", Debug_1::Trace_Code(__FILE__,__LINE__));
	
	
	// Create function Followup_Email
	/*****************************************************/

	function Followup_Email ($sql, $landing_page, $lp_email, $landing_page_id, $ole_property_code)
	{
		// Initialize
		$start 	= date("Y-m-d H:i:s", strtotime("-4 hour -30 min"));
		$end 	= date("Y-m-d H:i:s", strtotime("-30 minutes"));
		
		//$start = '2005-02-03 11:09:20';
		//$end   = '2005-02-03 12:33:25';

		$query = "
			SELECT
				session_id
				,first_name
				,last_name
				,email
			FROM
				visitors
			WHERE
				created_date BETWEEN '$start' AND '$end'
				AND last_page_completed = 'A'
				AND notice_sent = 'N'
				AND landing_page_id = ".$landing_page_id."
				AND first_name NOT LIKE '%test'
				AND first_name NOT LIKE 'test%'
				AND first_name !='test'
				AND first_name !=''
				AND last_name NOT LIKE '%test'
				AND last_name NOT LIKE 'test%'
				AND last_name !='test'
				AND last_name !=''
			";

		$query_update = "
			UPDATE
				visitors
			SET
				notice_sent = 'Y'
			WHERE
				created_date BETWEEN '$start' AND '$end'
				AND last_page_completed = 'A'
				AND notice_sent = 'N'
				AND landing_page_id = ".$landing_page_id."
				AND first_name NOT LIKE '%test'
				AND first_name NOT LIKE 'test%'
				AND first_name !='test'
				AND first_name !=''
				AND last_name NOT LIKE '%test'
				AND last_name NOT LIKE 'test%'
				AND last_name !='test'
				AND last_name !=''
			";
		
		$rs = $sql->Query(DB_NAME, $query, Debug_1::Trace_Code(__FILE__,__LINE__));

		// Send Email
		$mail = new prpc_client("prpc://smtp.2.soapdataserver.com/ole_smtp.1.php");
		$mail->setPrpcDieToFalse();

		while ($row = $sql->Fetch_Array_Row($rs))
		{

			$sid = $row['session_id'];
			$first_name = $row['first_name'];
			$last_name = $row['last_name'];
			$email = $row['email'];
			//$email = "myya.perez@thesellingsource.com";

			$data = array();

			//required data fields
			$data['email_primary'] = $email;
			$data['email_primary_name'] = $first_name . ' ' . $last_name;
			$data['site_name'] = 'Healthy Aspect';

			//custom data fields
			$data['link'] = 'http://greatweboffers.com/'.$landing_page.'/index.php?reentry='.$sid;

			$mailing_id = $mail->Ole_Send_Mail($lp_email, $ole_property_code, $data);
			
			print "\r\nMAILING_ID: $mailing_id  LP_ID: $landing_page_id  EMAIL: $email";
		}

		$result = $sql->Query(DB_NAME, $query_update, Debug_1::Trace_Code(__FILE__,__LINE__));

	}


	// Call function Followup_Email for each Landing Page
	/*****************************************************/

		$query_email = "
			SELECT 
				site_name
				,followup_ole_name
				,landing_page_id
				,ole_property_code
			FROM
				email
			WHERE
				followup_active = 'Y'
			";

		$res = $sql->Query(DB_NAME, $query_email, Debug_1::Trace_Code(__FILE__,__LINE__));

		while ($row = $sql->Fetch_Array_Row($res))
		{
			Followup_Email ($sql, $row['site_name'], $row['followup_ole_name'], $row['landing_page_id'], $row['ole_property_code']);
		}

?>