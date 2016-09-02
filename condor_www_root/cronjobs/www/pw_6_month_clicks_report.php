<?php
	/**
		@publicsection
		@public
		@brief


		@version

		@todo
	*/
	
	// Support files
	require_once ("mysql.3.php");
	require_once ("debug.1.php");
	require_once ("error.2.php");
	require_once ("Net/SMTP.php");
	require_once ("Mail/mime.php");
	require_once("/virtualhosts/partnerweekly.com/live/www/common.php");
	//require_once("/virtualhosts/perl.partnerweekly.com/www/common.php");
//	require_once("replace.php");

	db_connect();

	$use_debug = false;
	
	// Be a constructor
	$sql_info->type = "BOTH";
	$sql_info->host = "selsds001";
	$sql_info->login = "sellingsource";
	$sql_info->password = 'password';
	$sql_info->database = "partnerweekly_com";
	//$sql_info->host = "localhost";
	//$sql_info->login = "root";
	//$sql_info->password = '';
	//$sql_info->database = "perl_partnerweekly_com";

	$send_to = "nathanielm@partnerweekly.com, pamelas@partnerweekly.com, carolinem@partnerweekly.com, jude.parman@partnerweekly.com, brianr@sellingsource.com";
	//$send_to = "nathanielm@partnerweekly.com";

	// Connect to the db server
	$sql = new MySQL_3 ();

	$result = $sql->Connect ($sql_info->type, $sql_info->host, $sql_info->login, $sql_info->password, Debug_1::Trace_Code (__FILE__, __LINE__));
	if (Error_2::Error_Test ($result))
	{
		// It went bad punt
		if ($use_debug)
		{
			Debug_1::Raw_Dump ($result);
		}
		exit;
	}

	// get basic variables
	$local_time = localtime(time(), true);
	// build the end date
	$end_date = mktime(0, 0, 0, $local_time['tm_mon'] + 1, 1, $local_time['tm_year'] + 1900);
	// build the start date
	$start_date = mktime(0, 0, 0, $local_time['tm_mon'] - 5, 1, $local_time['tm_year'] + 1900);
	// grab local time values for the start date
	$start_localtime = localtime($start_date, true);
	$start_localtime['tm_mon'] += 1;
	$start_localtime['tm_year'] += 1900;
	$end_localtime = localtime($end_date, true);
	$end_localtime['tm_mon'] += 1;
	$end_localtime['tm_year'] += 1900;
	
	$table_name = get_banner_log_merge($start_date, $end_date);
	// get the agents we will use
	$query = "SELECT agent_id, count(*) AS click_totals FROM ".$table_name." WHERE event_type='click' AND is_unique=1 AND stamp >= '".$start_localtime['tm_year']."-".$start_localtime['tm_mon']."-".$start_localtime['tm_mday']." 00:00:00' AND stamp < '".$end_localtime['tm_year']."-".$end_localtime['tm_mon']."-".$end_localtime['tm_mday']." 00:00:00' GROUP BY agent_id ORDER BY click_totals DESC LIMIT 50";
	$result = $sql->Query ($sql_info->database, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
	$row = $sql->Fetch_Row($result);
	$agents = array();
	while ($row)
	{
		$agents[$row[0]]['total'] = $row[1];
		$row = $sql->Fetch_Row($result);
	}
	// get the matrix
	foreach (array_keys($agents) as $agent_id)
	{
		$query = "SELECT first_name, last_name, company_name FROM agents WHERE id=".$agent_id;
		$result = $sql->Query ($sql_info->database, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
		$row = $sql->Fetch_Row($result);
		
		$agents[$agent_id]['first_name'] = $row[0];
		$agents[$agent_id]['last_name'] = $row[1];
		$agents[$agent_id]['company_name'] = $row[2];
		
		for ($month = 0; $month < 6; $month++)
		{
			$query_start_localtime = localtime(mktime(0, 0, 0, $start_localtime['tm_mon'] + $month, 1, $start_localtime['tm_year']), 1);
			$query_start_localtime['tm_mon'] += 1;
			$query_start_localtime['tm_year'] += 1900;
			$query_end_localtime = localtime(mktime(0, 0, 0, $start_localtime['tm_mon'] + $month + 1, 1, $start_localtime['tm_year']), 1);
			$query_end_localtime['tm_mon'] += 1;
			$query_end_localtime['tm_year'] += 1900;
		
			$query = "SELECT count(*) FROM ".$table_name." WHERE event_type='click' AND is_unique=1 AND stamp >= '".$query_start_localtime['tm_year']."-".$query_start_localtime['tm_mon']."-".$query_start_localtime['tm_mday']." 00:00:00' AND stamp < '".$query_end_localtime['tm_year']."-".$query_end_localtime['tm_mon']."-".$query_end_localtime['tm_mday']." 00:00:00' AND agent_id=".$agent_id;
			$agents[$agent_id][$month] = get_single_result($query);
		}
	}
	
	release_banner_log_merge($table_name);
	
	// Build table headers
	$month_headers = array();
	for ($month = 0; $month < 6; $month++)
	{
		$header_time = mktime(0, 0, 0, $start_localtime['tm_mon'] + $month, 1, $start_localtime['tm_year']);
		
		$month_headers[$month] = strftime("%b", $header_time);
	}
		
	$email_text = "<html><body>\n";
	$email_text .= "Top Performers 6 Month Unique Clicks\n";
	$email_text .= '<table cellspacing="2" cellpadding="2" border="1">'."\n";
	$email_text .= "<tr><td>Company Name</td><td>First Name</td><td>Last Name</td>";
	for ($i = 0; $i < 6; $i++)
	{
		$email_text .= "<td>$month_headers[$i]</td>";
	}
	$email_text .= "<td>Total</td></tr>\n";
	foreach (array_keys($agents) as $agent)
	{
		$email_text .= "<tr><td>".$agents[$agent]['company_name']."</td><td>".$agents[$agent]['first_name']."</td><td>".$agents[$agent]['last_name']."</td>";
		for ($i = 0; $i < 6; $i++)
		{
			$email_text .= "<td>".$agents[$agent][$i]."</td>";
		}
		$email_text .= "<td>".$agents[$agent]['total']."</td></tr>\n";
	}
	$email_text .= "</table>\n";
	$email_text .= "</body></html>\n";	
	
	mail ($send_to, "Top Performers 6 Month Unique Clicks", $email_text, "From: stats@partnerweekly.com\r\nContent-Type: text/html; charset=\"iso-8859-1\"\r\n");
exit;

// returns the first field of the first row of a query
function get_single_result($query)
{
	global $sql, $sql_info;
	#print $query."\n";
	$result = $sql->Query ($sql_info->database, $query, Debug_1::Trace_Code (__FILE__, __LINE__));

     if (Error_2::Error_Test ($result))
     {
      	mail ("nathanielm@partnerweekly.com", "Error from ".__FILE__." at ".__LINE__, var_export ($result, 1));
        return FALSE;
      }
	$row = $sql->Fetch_Row ($result);
		
	return $row[0];	
}

?>