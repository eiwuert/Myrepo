<?PHP
	// ======================================================================
	// VP Nightly 1 => batch.nightly.VP.1.php
	// VP Nightly 2 => batch.nightly.VP.2.php
	//
	// This used to be hawkins.session_harves.php...it was changed for BB2
	// the LICENSE_KEY is for "Harvest_User_Info" under the Partnerweekly
	// Webadmin Stat column - VP_NIGHTLY
	//
	// myya.perez@thesellingsource.com 05-27-2005
	// ======================================================================
	
	
	// INCLUDES / DEFINES / INITIALIZE VARIABLES
	// ======================================================================

	require_once('lib_mode.1.php');
	require_once('diag.1.php');
	require_once('error.2.php');
	require_once('debug.1.php');
	require_once('mysql.3.php');
	require_once('hit_stats.1.php');
	require_once('csv.1.php');
	require_once('olp_valid_accounts.1.php');
	
	Diag::Enable();

	// stat column in webadmin is VP_NIGHTLY
	define('STAT_COLOUMN', 'h8');
	define('LICENSE_KEY','3301577eb098835e4d771d4cceb6542b');	
	
	$date_early = date("YmdHis", strtotime("yesterday 0:00:00"));
	$date_late = date("YmdHis", strtotime("yesterday 23:59:59"));
	$yesterday = date("Y-m-d", strtotime("yesterday"));

	
	$olp_accounts = new OLP_Valid_Accounts($date_early,$date_late,"OLP_VP1",null,"LIVE");
	//$data_array = $olp_accounts->Get_Bad_Standing_Accounts();
	$data_array = $olp_accounts->Get_All_Accounts();	
	$result_count = count($data_array);
	print "\r\nResult Count - ".$result_count."\r\n";	
	echo '<pre>';	
	
	// CREATE_CSV
	//============================================================	

	$fields = array
	(
		 "FNAME"
		,"LNAME"
		,"STREET"
		,"CITY"
		,"STATE"
		,"ZIP"
		,"PHONE"
		,"EMAIL"
		,"IP"
		,"REFERRER"
		,"CREATED"
	);
	
	$path = "/tmp/";
	$filename = "[".$yesterday."]-[".$result_count."]-VP-NIGHTLY-1";
	$csvfile = $filename.".csv";
	$path_to_csvfile = $path . $csvfile;

	$fp = fopen($path_to_csvfile, "w")
		or die("cannot open csv");
		
	$csv = new CSV
	(
		array
		(
			"flush" 		=> false, 
			"stream" 		=> $fp, 
			"forcequotes" 	=> false
		)
	);	

	for($i=0; $i<$result_count ; $i++)
	{
		$row = $data_array[$i];
		$csv_array = array
		(
			 $fname = strtoupper($row['first_name'])
			,$lname = strtoupper($row['last_name'])
			,$address = strtoupper($row['address_1'])
			,$city = strtoupper($row['city'])
			,$state = strtoupper($row['state'])
			,$row['zip']
			,$nphone = str_replace("-", "", $row['home_phone'])
			,$email = strtoupper($row['email'])
			,$row['ip_address']
			,$row['url']
			,$row['created_date']
		);

		$csv->recordFromArray($csv_array);
	}
	
	$mycsv = $csv->_buf;
	$csv->flush();

	
	// SEND_EMAIL FUNCTION
	//============================================================		
	
	function send_email($csv, $filename, $csvfile)
	{	

		$header = array
		(
			"sender_name" => "Selling Source <no-reply@sellingsource.com>",
			"subject" 	=> $filename,
			"site_name" 	=> "sellingsource.com",
			"message" 	=> "Attached Report Files"
		);
	 	$recipients = array
	 	(
			array("email_primary_name" => "Hope",   	"email_primary" => "Hope.Pacariem@partnerweekly.com"),
			array("email_primary_name" => "Vendor",   	"email_primary" => "pwleads@19communications.com"),
			array("email_primary_name" => "Jake Ludens",   	"email_primary" => "jake.ludens@partnerweekly.com"),
			array("email_primary_name" => "Programmer",   	"email_primary" => "jason.gabriele@sellingsource.com")
		);
		for($i=0; $i<count($recipients); $i++) 
		{
			$mail = new prpc_client("prpc://smtp.2.soapdataserver.com/ole_smtp.1.php");						
			$data = array_merge($recipients[$i], $header);
			$data['attachment_id'] = $mail->Add_Attachment($csv , 'application/text', $filename.".csv", "ATTACH");
			$result = $mail->Ole_Send_Mail("CRON_EMAIL", 28400, $data);
			if($result)
			{
				print "\r\nEMAIL HAS BEEN SENT TO: ".$recipients[$i]['email_primary']." .\r\n";
			}
			else
			{
				print "\r\nERROR SENDING EMAIL TO: ".$recipients[$i]['email_primary']." .\r\n";
			}
		}

	}		

	send_email ($mycsv, $filename, $csvfile);
	
	$sql = new MySQL_3();
	$sql->Connect("BOTH", "writer.olp.ept.tss", "sellingsource", "%selling\$_db");		
	Hit::Stats_Promoless(LICENSE_KEY, $sql, STAT_COLOUMN, $result_count);

	print "\r\rDONE AND DONE\r\r";

?>
