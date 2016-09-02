<?
#########################################################################
## coreg.nightly.email.batch.php                                       ##
## Matt Piper (matt.piper@thesellingsource.com                         ##
## Date: May 18, 2005                                                  ##
## Batches up and emails the daily co-reg post results back to the     ##
##  vendor who posted it to us.                                        ## 
#########################################################################

include_once('/virtualhosts/lib/mysql.3.php');
$sql    =       new MySQL_3();
$sql->Connect("BOTH", "selsds001", "sellingsource", "%selling\$_db", Debug_1::Trace_Code(__FILE__,__LINE__));

include_once("/virtualhosts/lib/prpc/client.php");


#########################################################################
## Pull out promo_ids and email addresses we need to send a batch to.  ##
#########################################################################
$query = "
	SELECT
		*
	FROM
		coreg_config
";
$result = $sql->Query("lead_generation", $query, Debug_1::Trace_Code(__FILE__,__LINE__));

$index = 0;
while($row = $sql->Fetch_Array_Row($result)) {
	$coreg_site[$index][promo_id] = $row[promo_id];
	$coreg_site[$index][tw_promo_id] = $row[tw_promo_id];
	$coreg_site[$index][name] = $row[name];
	$coreg_site[$index][email] = $row[email];
	$index++;
}



#########################################################################
## Get posts for that day, batch up and fire off.                      ##
#########################################################################

$day = strtotime("-1 day");
$start = date("Ymd000000",$day);
$end = date("Ymd235959",$day);

foreach($coreg_site as $key => $val) {
	$query = "
		SELECT
			*
		FROM
			coreg_post
		WHERE
			created_date between '" . $start . "' and '" . $end . "' and
			promo_id='" . $val[promo_id] . "' 
		ORDER BY
			coreg_post_id
	";
	//echo $query;
	$result = $sql->Query("lead_generation", $query, Debug_1::Trace_Code(__FILE__,__LINE__));

	$csv="";
	$count=0;
	while($row = $sql->Fetch_Array_Row($result)) {
		$csv .= $row[coreg_post_id] . ",";
		$csv .= $row[result_code] . ",";
		$csv .= $row[result_description] . ",";
		$csv .= $row[first_name] . ",";
		$csv .= $row[last_name] . ",";
		$csv .= $row[email];
		$csv .= "\r\n";
		$count++;
	}
	//echo $csv;
	
	
	#######################################################################
	## Build and send the email.                                         ##
	#######################################################################
	$header = new StdClass ();
	$header->subject = 'Co-Reg Site Post for ' . date("m-d-Y", $day);
	$header->sender_name = 'Co-Reg Site';
	$header->sender_address = 'noreply@thesellingsource.com';
	
	$recipient1 = new StdClass ();
	$recipient1->type = 'to';
	$recipient1->name = 'Matt Piper';
	$recipient1->address = 'matt.piper@thesellingsource.com';
	
	if($val[email] != "") {
		$recipient2 = new StdClass ();
		$recipient2->type = 'to';
		$recipient2->name = '';
		$recipient2->address = $val[email];
	}
		
	$recipients = array ($recipient1, $recipient2);
	
	$message = new StdClass ();
	$message->text = $val[name] . " Daily Co-Reg Site Posts For ".date("m-d-Y", $day).".\r\n\r\nPost Count: " . $count . "\r\n";
	
	if($count > 0) {
		$attach = new stdClass ();
		$attach->name = "coreg_post_".date("m-d-Y", $day).".csv";
		$attach->content = base64_encode ($csv);
		$attach->content_type = "text/x-csv";
		$attach->content_length = strlen ($csv);
		$attach->encoded = "TRUE";
	}
	
	$mail = new prpc_client("prpc://smtp.2.soapdataserver.com/smtp.1.php");
	
	$mailing_id = $mail->CreateMailing ("Co-Reg Posts", $header, NULL, NULL);
	$package_id =$mail->AddPackage ($mailing_id, $recipients, $message, array ($attach));
	$result = $mail->SendMail ($mailing_id);
	
	unset($attach);
	
}

?>