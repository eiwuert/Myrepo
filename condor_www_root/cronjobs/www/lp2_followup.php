<?php

	// ======================================================================
	// Cronjob for Landing Pages
	// runs every half hour, sends email to customers who did not complete
	// the process
	// - myya perez(myya.perez@thesellingsource.com), 06-15-2005
	// ======================================================================

	// Includes/Defines
	include_once('/virtualhosts/lib/mysql.3.php');
	include_once('/virtualhosts/lib/prpc/client.php');
	require_once("/virtualhosts/lib/diag.1.php");
	require_once("/virtualhosts/lib/lib_mode.1.php");
	
	//echo '<pre>';
	
	switch ($mode = Lib_Mode::Get_Mode())
	{
	case MODE_LOCAL://1
		Diag::Enable();
		define('DB_CONFIG',	'rc_lp_config');
		define('DB_PROCESS','rc_lp_process');
		define('DB_VISITOR','rc_lp_visitor');
		require_once("/virtualhosts/lp.2.dataserver.com/functions/cross_sell_promos.php");
		break;
	case MODE_RC:	//2
		default:
		define('DB_CONFIG',	'rc_lp_config');
		define('DB_PROCESS','rc_lp_process');
		define('DB_VISITOR','rc_lp_visitor');
		include_once("/virtualhosts/soapdataserver.com/lp.2/rc/functions/cross_sell_promos.php");
		break;
	case MODE_LIVE:	//3
		define('DB_CONFIG',	'lp_config');
		define('DB_PROCESS','lp_process');
		define('DB_VISITOR','lp_visitor');
		include_once("/virtualhosts/soapdataserver.com/lp.2/live/functions/cross_sell_promos.php");
		break;
	}		

	$sql = new MySQL_3();
	$sql->Connect("BOTH", "selsds001", "sellingsource", "%selling\$_db", Debug_1::Trace_Code(__FILE__,__LINE__));
	
	
	// Create function Followup_Email
	// ======================================================================	
	
	function Followup_Email ($sql, $landing_page_id, $last_page_completed, $link, $site_name, $ole_email_name, $ole_property_code)
	{	
		// Initialize
		$start 	= date("YmdHis", strtotime("-1 hour"));
		$end 	= date("YmdHis", strtotime("-30 minutes"));		

		// For Testing
		//$start = '20050608000000';
		//$end	 = '20050608235959';

		$query = "
			SELECT 
				personal.visitor_id,
				personal.name_first,
				personal.name_last,
				personal.email
			FROM
				".DB_VISITOR.".personal personal,
				".DB_PROCESS.".process process
			WHERE process.created_date BETWEEN '$start' AND '$end'
			AND personal.visitor_id = process.visitor_id
			AND process.landing_page_id = '" . mysql_escape_string($landing_page_id) . "'
			AND process.last_page_completed = '" . mysql_escape_string($last_page_completed) . "'
			AND process.process_completed = 'N'
			AND personal.name_first NOT LIKE '%test'
			AND personal.name_first NOT LIKE 'test%'
			AND personal.name_first NOT LIKE 'test'
			AND personal.name_first NOT LIKE ''
			AND personal.name_last NOT LIKE '%test'
			AND personal.name_last NOT LIKE 'test%'
			AND personal.name_last NOT LIKE 'test'
			AND personal.name_last NOT LIKE ''
			AND personal.email NOT LIKE 'test@test.com'
			AND personal.email NOT LIKE 'aa@aa.com'	
			AND personal.email NOT LIKE ''
		";

		$result = $sql->Query(DB_VISITOR, $query, Debug_1::Trace_Code(__FILE__,__LINE__));

		// Send Email
		$mail = new prpc_client("prpc://smtp.2.soapdataserver.com/ole_smtp.1.php");
		$mail->setPrpcDieToFalse();

		while ($row = $sql->Fetch_Array_Row($result))
		{
			//print_r($row);

			$data = array();

			//required data fields
			$data['email_primary'] 		= $row['email'];
			$data['email_primary_name'] = $row['name_first'].' '.$row['name_last'];
			$data['site_name'] 			= $site_name;

			//$data['email_primary'] 		= "myya.perez@thesellingsource.com";
			//$data['email_primary_name'] = "test test";
			//$data['site_name'] 			= $site_name;
						
			//custom data fields
			$data['link'] = $link.$row['visitor_id'];
			
			$args["sql"] = $sql;
			$args["db_config"] = DB_CONFIG;
			$args["landing_page_id"] = $landing_page_id;
			$data['cross_sell_promo_block'] = "<br><br>".cross_sell_promos($args)."<br><br>";
			
			$mailing_id = $mail->Ole_Send_Mail($ole_email_name, $ole_property_code, $data);
			
			print "\r\nMAILING_ID: $mailing_id  LP_ID: $landing_page_id  EMAIL: $row[email]";
		}
		
	}
	
	// Call function Followup_Email for each Landing Page
	// ======================================================================

		$query_email = " 
			SELECT * 
			FROM email 
			WHERE active = 'Y' 
			AND type = 'FOLLOWUP' 
			AND followup_link != ''
			AND followup_last_page != ''
		";

		$result_email = $sql->Query(DB_CONFIG, $query_email, Debug_1::Trace_Code(__FILE__,__LINE__));
		
		while ($row_email = $sql->Fetch_Array_Row($result_email))
		{
			//print_r($row_email);
			Followup_Email ($sql, $row_email['landing_page_id'], $row_email['followup_last_page'], $row_email['followup_link'], $row_email['site_name'], $row_email['ole_name'], $row_email['ole_property_code']);
		}

?>