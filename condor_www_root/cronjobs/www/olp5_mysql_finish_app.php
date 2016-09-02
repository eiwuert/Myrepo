<?PHP

	// Initial ini settings
	ini_set ('magic_quotes_runtime', 0);
	ini_set ('session.use_cookies', 0);

	// Make sure we keep running even if user aborts
	ignore_user_abort (TRUE);

	// Let it run forever
	set_time_limit (0);

	// Database connectivity
	require_once ("/virtualhosts/lib/mysql.3.php");
	require_once ("/virtualhosts/lib/lib_mail.1.php");
	require_once ("/virtualhosts/lib/error.2.php");
	
	// Connection information
//	define ("HOST", "localhost");
//	define ("USER", "root");
//	define ("PASS", "");

	define ("HOST", "selsds001");
	define ("USER", "sellingsource");
	define ("PASS", "%selling\$_db");

	// Build the sql object
	$sql = new MySQL_3 ();

	// Try the connection
	$result = $sql->Connect ("BOTH", HOST, USER, PASS, Debug_1::Trace_Code (__FILE__, __LINE__));
	Error_2::Error_Test ($result, TRUE);
	
	// Which dates to I pull
	$range_start = strtotime (date ("Y-m-d", strtotime ("-2 day"))); // Start 48 hours ago
	$range_end = strtotime (date ("Y-m-d 23:59:59", strtotime ("-1 day"))); // End 24 hours ago
	
	// instantiaite ole_mail client
	$ole_mail = new prpc_client("prpc://smtp.2.soapdataserver.com/ole_smtp.1.php");
	
	$companies = array(
		0 => array(
			database => "olp_pcl_visitor",
			property_short => "PCL"
			)
		,1 => array(
			database => "olp_ucl_visitor",
			company_name => "UCL"
			)
		,2 => array(
			database => "olp_ca_visitor",
			company_name => "CA"
			)
//		,3 => array(
//			database => "olp_ca_visitor",
//			company_name => "CA"		
//			)
	);

	// This is kind of a ghetto rotation of companies... need a better solution later. -- like thats actually going to happen  -- probably not
	foreach ($companies as $company_info)
	{
		// Pull the user information
		$query = "
		select
			*
		from
			`session_site`
		where
			modifed_date between FROM_UNIXTIME(".$range_start.") and FROM_UNIXTIME(".$range_end.")";

		$result = $sql->Query ($company_info["database"], $query, Debug_1::Trace_Code (__FILE__, __LINE__));
		
		Error_2::Error_Test ($result, TRUE);

		//Start the session	
		@session_start();
		
		//For each customer found process them
		while($user_info = $sql->Fetch_Array_Row($result))
		{
			
			
			$count++;
			$_SESSION = array();
			
			//Decode the session and put into the array
			session_decode($user_info["session_info"]);


			//If the customer status is not complete process and send the email to them.
			if(!$_SESSION['data']['app_completed'] && $_SESSION['data']['email_primary'] && $_SESSION['data']['name_first'] && $_SESSION['data']['name_last'])
			{
				
				// send email via OLE event 'OLP_INCOMPLETE_APP'			
				$email_data['email_primary'] = $_SESSION['data']['email_primary']; 
				//$email_data['email_primary'] = 'don.adriano@thesellingsource.com'; 
				$email_data['email_primary_name'] = $_SESSION['data']['name_first'].' '.$_SESSION['data']['name_last']; 		
				$email_data['name_view'] = $_SESSION['config']->name_view;
				$email_data['site_name'] = $_SESSION['config']->site_name;
				$email_data['name_first'] = $_SESSION['data']['name_first'];
				$email_data['link'] = 'http://'.$_SESSION['config']->site_name.'/?unique_id='.$_SESSION['data']['unique_id'];
				
				$ole_mailing_id = $ole_mail->Ole_Send_Mail ("OLP_INCOMPLETE_APP", NULL, $email_data);
				

			}
		}
	}
?>