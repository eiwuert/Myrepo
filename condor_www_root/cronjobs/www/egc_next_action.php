<?php

	// set to TRUE to enable testing
	// MAKE SURE TO SET BACK!
	$TESTING = FALSE;
	$TEST_EMAIL = "nickw@sellingsource.com";

	//Set the doc root
	$outside_web_space = realpath ("../")."/";
	$inside_web_space = realpath ("./")."/";
	define ("OUTSIDE_WEB_SPACE", $outside_web_space);
	define ("DATABASE", "expressgoldcard");

	require_once ("/virtualhosts/lib/debug.1.php");
	require_once ("/virtualhosts/lib/error.2.php");
	require_once ("/virtualhosts/lib/mysql.3.php");
	require_once ("/virtualhosts/lib/crypt.3.php");

	if ($TESTING){
		//TEST
		$server = new stdClass ();
		$server->host = "ds05.tss";
		$server->user = "root";
		$server->pass = "";
	}
	else
	{
		//LIVE, REAL
		$server = new stdClass ();
		$server->host = "selsds001";
		$server->user = "sellingsource";
		$server->pass = "%selling\$_db";
	}

	// Create sql connection(s)
	$sql = new MySQL_3();
	$result = $sql->Connect(NULL, $server->host, $server->user, $server->pass, Debug_1::Trace_Code (__FILE__, __LINE__));

	$query = "
	SELECT
		comments.*,
		customer.*,
		account.account_status AS account_status
	FROM
		comments,
		customer,
		account
	WHERE
		comments.follow_up_date ='".date('Y-m-d')."'
	AND
		comments.cc_number = customer.cc_number
	AND
		account.cc_number = comments.cc_number";

	$result = $sql->Query(DATABASE, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
	
	$loop = FALSE;
	while (FALSE !== ($row_data = $sql->Fetch_Object_Row ($result)))
	{
		$acct = $row_data->cc_number;
		$comment->{$acct}=$row_data;
		$loop = TRUE;
	}
	
	if ($loop)
	{
		$today = date("m-d-Y");
		$xls_file = "
		<html>
		<body>
		
		<table border=2>
		<tr>
		<td colspan=6 align=center><b>CUSTOMER FOLLOW UP REPORT</b><br><i>Date: $today</i></td>
		</tr>
		</table>
			
		<table>
		<tr>
		<td colspan=6 align=center>&nbsp;</td>
		</tr>
		</table>
			
		<table>
		<tr>
		<td align=center><b>CC NUMBER</b></td>
		<td align=center><b>NAME</b></td>
		<td align=center><b>STATUS</b></td>
		<td align=center><b>HOME PHONE</b></td>
		<td align=center><b>COMMENT</b></td>
		<td align=center><b>DATE</b></td>
		</tr>
		</table>
		
		<table>";
		
		foreach ($comment AS $record)
		{
			$xls_data .= "
			<tr>
			<td>".chunk_split($record->cc_number, 4, ' ')."</td>
			<td>".$record->first_name."".$record->last_name."</td>
			<td>".$record->account_status."</td>
			<td>".$record->home_phone."</td>
			<td>".$record->comment."</td>
			<td>".$record->follow_up_date."</td>
			</tr>";
		}
		
		$xls_file .= $xls_data."
		</body>
		</html>";
		
		$outer_boundry = md5("Outer Boundry");
		$inner_boundry = md5("Inner Boundry");	
		
		$batch_headers =
		"MIME-Version: 1.0\r\n".
		"Content-Type: Multipart/Mixed;\r\n boundary=\"" . $outer_boundry . "\"\r\n".
		"From: EGC AGENT TOOL <noreply@expressgoldcard.com>\r\n"; 

		
		$batch_body =
		"--".$outer_boundry."\r\n".
		"Content-Type: text/plain;\r\n".
		" charset=\"us-ascii\"\r\n".
		"Content-Transfer-Encoding: 7bit\r\n".
		"Content-Disposition: inline\r\n\r\n".
		"Follow Up Report - ".date ("Y-m-d")."\r\n".
		"--".$outer_boundry."\r\n".
		"Content-Type: application/xls;\r\n".
		" name=\"ExpressGoldCard - ".date ("md")."\"\r\n".
		"Content-Transfer-Encoding: 7bit\r\n".
		"Content-Disposition: attachment; filename=\"ExpressGoldCard - ".date ("Y-m-d").".xls\"\r\n\r\n".
		$xls_file."\r\n".
		"--".$outer_boundry."--\r\n\r\n";
	
		if ($TESTING){
			mail ($TEST_EMAIL, "TEST Daily Follow Up List: ".date ("Y-m-d \a\\t H:i:s"), $batch_body, $batch_headers);
		}
		else
		{
		mail ("nickw@sellingsource.com", "Daily Follow Up List: ".date ("Y-m-d \a\\t H:i:s"), $batch_body, $batch_headers);
		mail ("approval-department@expressgoldcard.com", "Daily Follow Up List: ".date ("Y-m-d \a\\t H:i:s"), $batch_body, $batch_headers);
		}
	}
	else
	{
		$message = "No Follow Ups For Today.";
		if ($TESTING){
			mail ($TEST_EMAIL, "Daily Follow Up List", $message, "From: EGC AGENT TOOL <noreply@expressgoldcard.com>");
		}
		else
		{
			mail ("nickw@sellingsource.com", "Daily Follow Up List", $message, "From: EGC AGENT TOOL <noreply@expressgoldcard.com>");
			mail ("approval-department@expressgoldcard.com", "Follow Up List", $message, "From: no_reply <noreply@expressgoldcard.com>");
		}
	}

?>
