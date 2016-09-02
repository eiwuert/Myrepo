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
//	require_once("replace.php");

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

	$send_to = "nathanielm@partnerweekly.com, pamelas@partnerweekly.com, jake.ludens@partnerweekly.com, carolinem@partnerweekly.com, jude.parman@partnerweekly.com, pennied@partnerweekly.com, robynw@partnerweekly.com, celestec@partnerweekly.com, brianr@sellingsource.com";
	
	# get basic variables
	$table_name = get_banner_log_for_date(time());
	$local_time = localtime(time(), 1);
	# the posix functions use 00 for jan and we use 01
	$local_time['tm_mon'] += 1;
	$local_time['tm_year'] += 1900;
	$local_time['tm_mon'] = ($local_time['tm_mon'] < 10) ? '0'.$local_time['tm_mon'] : $local_time['tm_mon']; # 0 pad the result

	$start_of_month = $local_time['tm_year']."-".$local_time['tm_mon']."-01 00:00:00";
	
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
	
	// active affiliates
	$query = "SELECT COUNT(*) FROM agents WHERE suspended > NOW() AND approved = 1";
	$agent_count = get_single_result($query);
			
	// unique impressions
	$query = "SELECT COUNT(*) FROM $table_name WHERE is_unique=1 AND event_type='impr' AND stamp >= '$start_of_month' and stamp <= CURDATE()";
	$month['unique_impressions'] = get_single_result($query);
	$query = "SELECT COUNT(*) FROM $table_name WHERE is_unique=1 AND event_type='impr' AND stamp >= DATE_SUB(CURDATE(), INTERVAL 1 DAY) AND stamp <= CURDATE()";
	$day['unique_impressions'] = get_single_result($query);
	$projected['unique_impressions'] = round($month['unique_impressions'] / $local_time['tm_mday'] * 30);
	
	# non unique impressions
	$query ="SELECT COUNT(*) FROM $table_name WHERE is_unique=0 AND event_type='impr' AND stamp >= '$start_of_month' and stamp <= CURDATE()";
	$month['non_unique_impressions'] = get_single_result($query);
	$query = "SELECT COUNT(*) FROM $table_name WHERE is_unique=0 AND event_type='impr' AND stamp >= DATE_SUB(CURDATE(), INTERVAL 1 DAY) AND stamp <= CURDATE()";
	$day['non_unique_impressions'] = get_single_result($query);
	$projected['non_unique_impressions'] = round($month['non_unique_impressions'] / $local_time['tm_mday'] * 30);
	
	// unique clicks
	$query = "SELECT COUNT(*) FROM $table_name WHERE is_unique=1 AND event_type='click' AND stamp >= '$start_of_month' and stamp <= CURDATE()";
	$month['unique_clicks'] = get_single_result($query);
	$query = "SELECT COUNT(*) FROM $table_name WHERE is_unique=1 AND event_type='click' AND stamp >= DATE_SUB(CURDATE(), INTERVAL 1 DAY) AND stamp <= CURDATE()";
	$day['unique_clicks'] = get_single_result($query);
	$projected['unique_clicks'] = round($month['unique_clicks'] / $local_time['tm_mday'] * 30);
	
	// non unique clicks
	$query = "SELECT COUNT(*) FROM $table_name WHERE is_unique=0 AND event_type='click' AND stamp >= '$start_of_month' and stamp <= CURDATE()";
	$month['non_unique_clicks'] = get_single_result($query);
	$query = "SELECT COUNT(*) FROM $table_name WHERE is_unique=0 AND event_type='click' AND stamp >= DATE_SUB(CURDATE(), INTERVAL 1 DAY) AND stamp <= CURDATE()";
	$day['non_unique_clicks'] = get_single_result($query);
	$projected['non_unique_clicks'] = round($month['non_unique_clicks'] / $local_time['tm_mday'] * 30);
	
	# leads
	$query = "SELECT COUNT(*) FROM lead_log WHERE charge_for_lead > 0 AND status = 'approve' AND stamp >= '$start_of_month' and stamp <= CURDATE()";
	$month['leads'] = get_single_result($query);
	$query = "SELECT COUNT(*) FROM lead_log WHERE charge_for_lead > 0 AND status = 'approve' AND stamp >= DATE_SUB(CURDATE(), INTERVAL 1 DAY) AND stamp <= CURDATE()";
	$day['leads'] = get_single_result($query);
	$projected['leads'] = round($month['leads'] / $local_time['tm_mday'] * 30);
	
	// lead revenue
	$query = "SELECT SUM(charge_for_lead) FROM lead_log WHERE status='approve' AND stamp >= '$start_of_month' AND stamp <= CURDATE()";
	$month['lead_rev'] = get_single_result($query);
	$query = "SELECT SUM(charge_for_lead) FROM lead_log WHERE status='approve' AND stamp >= DATE_SUB(CURDATE(), INTERVAL 1 DAY) AND stamp <= CURDATE()";
	$day['lead_rev'] = get_single_result($query);
	$projected['lead_rev'] = round($month['lead_rev'] / $local_time['tm_mday'] * 30, 2);
	
	
	// sales
	$query = "SELECT COUNT(*) FROM lead_log WHERE charge_for_sale > 0 AND status = 'approve' AND stamp >= '$start_of_month' AND stamp <= CURDATE()";
	$month['sales'] = get_single_result($query);
	$query = "SELECT COUNT(*) FROM lead_log WHERE charge_for_sale > 0 AND status = 'approve' AND stamp >= DATE_SUB(CURDATE(), INTERVAL 1 DAY) AND stamp <= CURDATE()";
	$day['sales'] = get_single_result($query);
	$projected['sales'] = round($month['sales'] / $local_time['tm_mday'] * 30);
	
	# sales revenue
	$query = "SELECT SUM(charge_for_sale) FROM lead_log WHERE status = 'approve' AND stamp >= '$start_of_month' AND stamp <= CURDATE()";
	$month['sales_rev'] = get_single_result($query);
	$query = "SELECT SUM(charge_for_sale) FROM lead_log WHERE status = 'approve' AND stamp >= DATE_SUB(CURDATE(), INTERVAL 1 DAY) AND stamp <= CURDATE()";
	$day['sales_rev'] = get_single_result($query);
	$projected['sales_rev'] = round($month['sales_rev'] / $local_time['tm_mday'] * 30, 2);
	
	// lead cost
	$query = "SELECT SUM(pay_for_lead) FROM lead_log WHERE status = 'approve' AND stamp >= '$start_of_month' AND stamp <= CURDATE()";
	$month['lead_cost'] = get_single_result($query);
	$query = "SELECT SUM(pay_for_lead) FROM lead_log WHERE status = 'approve' AND stamp >= DATE_SUB(CURDATE(), INTERVAL 1 DAY) AND stamp <= CURDATE()";
	$day['lead_cost'] = get_single_result($query);
	$projected['lead_cost'] = round($month['lead_cost'] / $local_time['tm_mday'] * 30, 2);
	
	// sales cost
	$query = "SELECT SUM(pay_for_sale) FROM lead_log WHERE status = 'approve' AND stamp >= '$start_of_month' AND stamp <= CURDATE()";
	$month['sales_cost'] = get_single_result($query);
	$query = "SELECT SUM(pay_for_sale) FROM lead_log WHERE status = 'approve' AND stamp >= DATE_SUB(CURDATE(), INTERVAL 1 DAY) AND stamp <= CURDATE()";
	$day['sales_cost'] = get_single_result($query);
	$projected['sales_cost'] = round($month['sales_cost'] / $local_time['tm_mday'] * 30, 2);
	
	// totals
	$month['total_impressions'] = $month['unique_impressions'] + $month['non_unique_impressions'];
	$month['total_clicks'] = $month['unique_clicks'] + $month['non_unique_clicks'];
	$month['total_rev'] = $month['lead_rev'] + $month['sales_rev'];
	$month['total_cost'] = $month['lead_cost'] - $month['sales_cost'];
	$month['profit'] = $month['total_rev'] - $month['total_cost'];
	$month['margin'] = round($month['profit'] / $month['total_rev'] * 100);
	$month['ecpm'] = round($month['total_rev'] / $month['unique_impressions'], 2);
	$month['epc'] = round($month['total_rev'] / $month['unique_clicks'], 2);

	$day['total_impressions'] = $day['unique_impressions'] + $day['non_unique_impressions'];
	$day['total_clicks'] = $day['unique_clicks'] + $day['non_unique_clicks'];
	$day['total_rev'] = $day['lead_rev'] + $day['sales_rev'];
	$day['total_cost'] = $day['lead_cost'] - $day['sales_cost'];
	$day['profit'] = $day['total_rev'] - $day['total_cost'];
	$day['margin'] = round($day['profit'] / $day['total_rev'] * 100);
	$day['ecpm'] = round($day['total_rev'] / $day['unique_impressions'], 2);
	$day['epc'] = round($day['total_rev'] / $day['unique_clicks'], 2);

	$projected['total_impressions'] = $projected['unique_impressions'] + $projected['non_unique_impressions'];
	$projected['total_clicks'] = $projected['unique_clicks'] + $projected['non_unique_clicks'];
	$projected['total_rev'] = $projected['lead_rev'] + $projected['sales_rev'];
	$projected['total_cost'] = $projected['lead_cost'] - $projected['sales_cost'];
	$projected['profit'] = $projected['total_rev'] - $projected['total_cost'];
	$projected['margin'] = round($projected['profit'] / $projected['total_rev'] * 100);
	$projected['ecpm'] = round($projected['total_rev'] / $projected['unique_impressions'], 2);
	$projected['epc'] = round($projected['total_rev'] / $projected['unique_clicks'], 2);

	$email_text = "<html><body>\n";
	$email_text .= "<b>Total Affiliates</b>: $agent_count<br>\n<br>\n";
	$email_text .= '<table cellspacing="2" cellpadding="2" border="1">'."\n";
	$email_text .= "<tr>\n";
	$email_text .= "	<td></td>\n";
	$email_text .= "	<td><b>Unique Impressions</b></td>\n";
	$email_text .= "	<td><b>Non-unique Impressions</b></td>\n";
	$email_text .= "	<td><b>Total Impressions</b></td>\n";
	$email_text .= "	<td><b>Unique Clicks</b></td>\n";
	$email_text .= "	<td><b>Non-unique Clicks</b></td>\n";
	$email_text .= "	<td><b>Total Clicks</b></td>\n";
	$email_text .= "</tr>\n";
	$email_text .= "<tr>\n";
	$email_text .= "	<td><b>Daily</b></td>\n";
	$email_text .= "	<td>".$day['unique_impressions']."</td>\n";
	$email_text .= "	<td>".$day['non_unique_impressions']."</td>\n";
	$email_text .= "	<td>".$day['total_impressions']."</td>\n";
	$email_text .= "	<td>".$day['unique_clicks']."</td>\n";
	$email_text .= "	<td>".$day['non_unique_clicks']."</td>\n";
	$email_text .= "	<td>".$day['total_clicks']."</td>\n";
	$email_text .= "</tr>\n";
	$email_text .= "<tr>\n";
	$email_text .= "	<td><b>Month to Date</b></td>\n";
	$email_text .= "	<td>".$month['unique_impressions']."</td>\n";
	$email_text .= "	<td>".$month['non_unique_impressions']."</td>\n";
	$email_text .= "	<td>".$month['total_impressions']."</td>\n";
	$email_text .= "	<td>".$month['unique_clicks']."</td>\n";
	$email_text .= "	<td>".$month['non_unique_clicks']."</td>\n";
	$email_text .= "	<td>".$month['total_clicks']."</td>\n";
	$email_text .= "</tr>\n";
	$email_text .= "<tr>\n";
	$email_text .= "	<td><b>Projected</b><br>(Daily Avrg x 30)</td>\n";
	$email_text .= "	<td>".$projected['unique_impressions']."</td>\n";
	$email_text .= "	<td>".$projected['non_unique_impressions']."</td>\n";
	$email_text .= "	<td>".$projected['total_impressions']."</td>\n";
	$email_text .= "	<td>".$projected['unique_clicks']."</td>\n";
	$email_text .= "	<td>".$projected['non_unique_clicks']."</td>\n";
	$email_text .= "	<td>".$projected['total_clicks']."</td>\n";
	$email_text .= "</tr>\n";
	$email_text .= "</table>\n<br>\n<br>\n";
	$email_text .= '<table cellspacing="2" cellpadding="2" border="1">'."\n";
	$email_text .= "<tr>\n";
	$email_text .= "	<td></td>\n";
	$email_text .= "	<td><b>Leads</b></td>\n";
	$email_text .= "	<td><b>Gross Revenue</b></td>\n";
	$email_text .= "	<td><b>PW Profit</b></td>\n";
	$email_text .= "	<td><b>PW Margin (%)</b></td>\n";
	$email_text .= "	<td><b>Network ECPM</b></td>\n";
	$email_text .= "	<td><b>Network EPC</b></td>\n";
	$email_text .= "</tr>\n";
	$email_text .= "<tr>\n";
	$email_text .= "	<td><b>Daily</b></td>\n";
	$email_text .= "	<td>".$day['leads']."</td>\n";
	$email_text .= "	<td>".$day['total_rev']."</td>\n";
	$email_text .= "	<td>".$day['profit']."</td>\n";
	$email_text .= "	<td>".$day['margin']."</td>\n";
	$email_text .= "	<td>".$day['ecpm']."</td>\n";
	$email_text .= "	<td>".$day['epc']."</td>\n";
	$email_text .= "</tr>\n";
	$email_text .= "<tr>\n";
	$email_text .= "	<td><b>Month to Date</b></td>\n";
	$email_text .= "	<td>".$month['leads']."</td>\n";
	$email_text .= "	<td>".$month['total_rev']."</td>\n";
	$email_text .= "	<td>".$month['profit']."</td>\n";
	$email_text .= "	<td>".$month['margin']."</td>\n";
	$email_text .= "	<td>".$month['ecpm']."</td>\n";
	$email_text .= "	<td>".$month['epc']."</td>\n";
	$email_text .= "</tr>\n";
	$email_text .= "<tr>\n";
	$email_text .= "	<td><b>Projected</b><br>(Daily Avrg x 30)</td>\n";
	$email_text .= "	<td>".$projected['leads']."</td>\n";
	$email_text .= "	<td>".$projected['total_rev']."</td>\n";
	$email_text .= "	<td>".$projected['profit']."</td>\n";
	$email_text .= "	<td>".$projected['margin']."</td>\n";
	$email_text .= "	<td>".$projected['ecpm']."</td>\n";
	$email_text .= "	<td>".$projected['epc']."</td>\n";
	$email_text .= "</tr>\n";
	$email_text .= "</table>\n";
	$email_text .= "</body></html>\n";	

	
	mail ($send_to, "Daily stats", $email_text, "From: stats@partnerweekly.com\r\nContent-Type: text/html; charset=\"iso-8859-1\"\r\n");

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

// returns the banner_log table name for the given unix timestamp, date. 
// PORT: from cgi/db.pl
function get_banner_log_for_date($date)
{
	$local_time = localtime($date, 1);

	# the posix functions use 00 for jan and we use 01
	$local_time['tm_mon'] += 1;
	
	# generate the table name
	$disp_year = $local_time['tm_year'] + 1900; # adjust to the correct year
	$disp_month = ($local_time['tm_mon'] < 10) ? '0'.$local_time['tm_mon'] : $local_time['tm_mon']; # 0 pad the result
	
	$result = 'banner_log'.$disp_year.'_'.$disp_month;
	
	return $result;
}

?>