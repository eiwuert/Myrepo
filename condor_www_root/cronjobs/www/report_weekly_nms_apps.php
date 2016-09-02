#!/usr/bin/php
<?php
// *************************************************
// Version 1.0.0
// NMS WEEKLY REPORT :: APPS & PREQUALS
//
// **Even though this report says weekly it's really a monthly report**
//
// By John Hargrove
// 10/26/2004 - SellingSource.com
// Descended from NMS MONTHLY REPORT
// *************************************************
// *************************************************

// *************************************************
// DEFINE INCLUDES/REQUIRES
// *************************************************
	define('BFW_CODE_DIR','/virtualhosts/bfw.1.edataserver.com/include/code/');

	require_once ("/virtualhosts/lib/mysql.3.php");
	require_once(BFW_CODE_DIR.'crypt_config.php');
	require_once(BFW_CODE_DIR.'crypt.singleton.class.php');
	
	// David Bryant
	function convert_timestamp ($timestamp, $adjust="") {
		$timestring = substr($timestamp,0,8)." ".
				substr($timestamp,8,2).":".
				substr($timestamp,10,2).":".
				substr($timestamp,12,2);
		return strtotime($timestring." $adjust");
	}

// *************************************************
// DEFINE CONSTANTS & VARIABLES
// *************************************************
// One should only need to adjust these Vars to customize this script.

	// Set the mode for determining connection information
	$mode = 'LIVE';

	//  Kill The Timeout
		set_time_limit(0);

	//Promos
	$urls = "'maxoutloan.com','speedycashadvance.com','autorepairloans.com','ineedbeermoney.com'";

	// CONSTANTS
		define ("ROOT_PATH", realpath ("./")."/");			// Determine root path

	// CONTROL STRUCTURES
		$report = "prequals";								// Sets initial report type
		$running = TRUE;									// Initializes reporting process
		$crypt_config 	= Crypt_Config::Get_Config('LIVE');
		$cryptSingleton 	= Crypt_Singleton::Get_Instance($crypt_config['KEY'],$crypt_config['IV']);	
	// Determine Environment and Set Vars accordingly

	// VARS - Database Connection - PRODUCTION slave server that's replicated from live
		$host_type = "BOTH";
		$host_read = "reporting.olp.ept.tss";
		$host_login = "sellingsource";
		$host_password = 'password';
		$host_database = "olp";

		$TEST_INDICATOR = "";

	// Email Recipient(s) - PRODUCTION
		$recipients = array
	 	(
			array("email_primary_name" => "Chery McDermott", "email_primary" => "admin@dcei.com"),
			array("email_primary_name" => "OLP Cron",        "email_primary" => "olpcron@sellingsource.com")
//			array("email_primary_name" => "OLP Cron",        "email_primary" => "adam.englander@sellingsource.com")
		);	
		
	// Initialize Query Object
		$sql = new MySQL_3 ();

	// Open DB Connection
		$link_id = $sql->Connect ($host_type, $host_read, $host_login, $host_password, Debug_1::Trace_Code (__FILE__, __LINE__));

	// VARS - Relevant Dates

	// Generates the appropriate dates for defining the query range.
		$currentdate = date("Ymd");				
		$lastmonth = date("n")-1;
		$daterangeopen = mktime(0,0,0,$lastmonth,1,date("Y"));
		$daterangeclose = mktime(0,0,0,date("n",$daterangeopen),date("t",$daterangeopen),date("Y",$daterangeopen));
		$date_open = (date('D F j, Y',$daterangeopen));														// Resets first date in extract for display
		$date_close = (date('D F j, Y',$daterangeclose));													// Resets first date in extract for display
		$extractperiod_txt = (date('m/d/Y',$daterangeopen) ." -> ".date('m/d/Y',$daterangeclose));
		$extractperiod_num = (date('mdY',$daterangeopen)."-".date('mdY',$daterangeclose));

// *************************************************
// END DEFINE CONSTANTS & VARIABLES
// *************************************************

// *************************************************
// BUILD EMAIL REPORT AND SEND
// *************************************************
// NOTE: Unless adding Recipients or testing, no
//       edits should be required below this line.
//       Then, only uncommenting is required.
// *************************************************

	echo "\r\n\r\nDotCom Endeavors\r\nMonthly Order Report Generator\r\n\r\n";
	echo "Running ...\r\n";
	echo "Source DB: ".$host_read."\r\n";
	echo "Current Date: ".$currentdate."\r\n";
	echo "Date Open: ".$date_open."\r\n";
	echo "Date Close: ".$date_close."\r\n";

// Run Querys and Write Report Files as CSV
// *************************************************
	WHILE ($running == TRUE) {
		SWITCH ($report){

			// Pulls All Prequal Applications
			case "prequals":
					$query = "
						SELECT
							campaign_info.application_id as id,
							personal_encrypted.email as email_address,
							CONCAT_WS(' ',personal_encrypted.first_name,personal_encrypted.last_name) as full_name,
							campaign_info.promo_sub_code,
							campaign_info.url,
							campaign_info.modified_date as date
						FROM
							campaign_info
						JOIN personal_encrypted
						USING (application_id)
						WHERE
							campaign_info.modified_date
							    BETWEEN '".date('Y-m-d',$daterangeopen)."' AND '".date('Y-m-d',$daterangeclose)."'
							AND campaign_info.application_id = personal_encrypted.application_id
							AND campaign_info.url IN (" . $urls . ")
						ORDER BY date,campaign_info.url";

					// Build Attachment
						$attachment1 = Build_CSV ($query, $sql,"olp_bb_partial", $report, $currentdate, $extractperiod_num);
					// Reset Vars For Next Loop
						$report = "complete";
						$query = "";
					break;

				// Pulls All Completed Applications
				case 'complete':
					$query = "
						SELECT
							application.application_id as id,
							application.created_date as date,
							campaign_info.url,
							CONCAT_WS(' ',personal_encrypted.first_name,personal_encrypted.last_name) as full_name,
							personal_encrypted.email as email_address,
							campaign_info.promo_sub_code,
							target.property_short as winner,
							residence.city,
							residence.state
						FROM campaign_info as campaign_info
						 JOIN application on campaign_info.application_id=application.application_id
						 JOIN personal_encrypted on campaign_info.application_id=personal_encrypted.application_id
						 JOIN target on application.target_id=target.target_id
						 JOIN residence on campaign_info.application_id=residence.application_id
						WHERE
							campaign_info.modified_date BETWEEN '".date('Y-m-d',$daterangeopen)."' AND '".date('Y-m-d',$daterangeclose)."'
							AND campaign_info.url in (" . $urls . ")
							AND application_type NOT IN ('VISITOR','FAILED')
						GROUP BY campaign_info.application_id
						ORDER BY campaign_info.modified_date, campaign_info.url";

					// Build Attachment
					$attachment2 = Build_CSV ($query, $sql, "olp", $report, $currentdate, $extractperiod_num);
					//$attachment2 = Build_CSV ($query, $sql, "olp_bb_visitor", $report, $currentdate, $extractperiod_num);
					// Reset Vars For Next Loop
						$report = "stop";
						$query = "";
					break;

			// KILL THE PROCESS
			default:
				echo "\r\n\r\nAll CSV files have been generated.\r\n\r\n";
				$running = FALSE;
				break;
		}
	}
	// end while

// *************************************************
// BUILD CSV FUNCTION
// *************************************************
	function Build_CSV ($query, $sql, $host_database, $report, $currentdate, $extractperiod_num)
	{
		// Run The Query
		$query_output = $sql->Query ($host_database, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
		// Obtain Row Count

		$app_count=0;
		$removed_count=0;
		$attachment_raw = new StdClass();

		// Strip Dupes From Result Set [By Todd H.]
			while ($data = $sql->Fetch_Object_Row($query_output))
			{
				if ($report == "prequals")
				{
					$attachment_raw->{"ap".$data->id} = date('m/d/Y', convert_timestamp($data->date)).", ".$data->url.", ".strtolower($data->email_address).", ".ucwords(strtolower(str_replace(",","",$data->full_name))).", ".$data->city.", ".$data->state.", ".$data->promo_sub_code."\r\n";
					$app_count++;
				}
				else
				{
					// added 11-04-2004 By John Hargrove
					// Remove leads that are not first tier from this list..
					if( $data->winner == 'ucl' || $data->winner == 'pcl' || $data->winner == 'ca' || $data->winner == 'ufc' || $data->winner == 'd1' )
					{
						$attachment_raw->{"ap".$data->id} = date('m/d/Y', convert_timestamp($data->date)).", ".$data->url.", ".strtolower($data->email_address).", ".ucwords(strtolower(str_replace(",", ".", $data->full_name))).", ".$data->city.", ".$data->state.", ".$data->promo_sub_code."\r\n";
						$app_count++;
					}
					else
					{
						$removed_count++;
					}
				}
			}
		// end while

	 	echo "\r\n";
		echo "DCE had ".$app_count." ".$report." orders.";
		echo "\r\n$removed_count were rejected.";
		echo "\r\n";


		// Write Report DataSet
		$attachment_new = "APP DATE, DOMAIN NAME, EMAIL, FULL NAME, CITY, STATE, PROMO_SUB_CODE\r\n"; 			// SETS HEADERS FOR THE CSV FILE COLUMNS
		foreach ($attachment_raw as $data_rows)
		{
			$attachment_new .= $data_rows;															// WRITES CSV FILE TO MEMORY
		}

		if($app_count == 0) return false;

		// Build Attachment [Only if there is data available]
		$filename = $extractperiod_num."_DCE_".$report."_apps.csv";
		// take attachment data and write it to a file

		// Return Values
		return array('name' => $filename, 'data' => $attachment_new);
	}

	// BUILD EMAIL REPORT AND SEND
	// *************************************************
	// VARS - Email Generation
	// Email Headers
	/*
		$email_subject = "DotCom Endeavors :: Monthly Order Extracts :: [".$extractperiod_txt.$TEST_INDICATOR."]";

	// Email Content
		$mailbody_text = "Good morning, \r\n\r\n
						  Attached, you will find the following files:\n\t"
						  .$extractperiod_num."_DCE_complete_apps.csv\n\t"
						  .$extractperiod_num."_DCE_prequal_apps.csv\n\n
						  These files are database extracts which represent the
						  Prequal and Completed Applications placed with NMS during "
						  .$extractperiod_txt.". \r\n\r\n
						  Regards, \r\n \r\n
						  SellingSource.com \r\n
						 ";
	*/
	// Send Email via SOAP
	// Create the Mail Object and Send the Mail
	include_once("prpc/client.php");
	require_once(BFW_CODE_DIR.'OLP_TX_Mail_Client.php');

	// Benchmark
	list ($sm, $ss) = explode (" ", microtime ());

	$header = array
	(
		"sender_name"           => "Selling Source <no-reply@sellingsource.com>",
		"subject_text" 	        => $extractperiod_txt.$TEST_INDICATOR,
		"site_name" 	        => "sellingsource.com",
		"extractperiod_num" 	=> $extractperiod_num,
		"extractperiod_text"    => $extractperiod_txt
	);

	$attachments = array(
		1 => array(
			'method' => 'ATTACH',
			'filename' => $attachment1['name'],
			'mime_type' => 'text/plain',
			'file_data' => gzcompress($attachment1['data']),
			'file_data_size' => strlen($attachment1['data'])
		),
		2 => array(
			'method' => 'ATTACH',
			'filename' => $attachment2['name'],
			'mime_type' => 'text/plain',
			'file_data' => gzcompress($attachment2['data']),
			'file_data_size' => strlen($attachment2['data'])
		),
	);
	
 	for($i=0; $i<count($recipients); $i++)
	{
		//$mail = new prpc_client("prpc://smtp.2.soapdataserver.com/ole_smtp.1.php");
		$tx = new OlpTxMailClient(false,$mode);
		$data = array_merge($recipients[$i], $header);

		try
		{
			$result = $tx->sendMessage('live', 'CRON_WEEKLY_NMS_APPS', $data['email_primary'], '', $data, array($attachments[1]));
		}
		catch(Exception $e)
		{
			$result = FALSE;
		}
		//Send attachment 1
		//$data['attachment_id'] = $mail->Add_Attachment($attachment1['data'], 'application/text', $attachment1['name'], "ATTACH");
		//$result = $mail->Ole_Send_Mail("CRON_WEEKLY_NMS_APPS", 17176, $data);

		if($result)
		{
			print "\r\nEMAIL HAS BEEN SENT TO: ".$recipients[$i]['email_primary']." .\n";
		}
		else
		{
			print "\r\nERROR SENDING EMAIL TO: ".$recipients[$i]['email_primary']." .\n";
		}

		try
		{
			$result = $tx->sendMessage('live', 'CRON_WEEKLY_NMS_APPS', $data['email_primary'], '', $data, array($attachments[2]));
		}
		catch(Exception $e)
		{
			$result = FALSE;
		}
		//Send attachment 2
		//$data['attachment_id'] = $mail->Add_Attachment($attachment2['data'], 'application/text', $attachment2['name'], "ATTACH");
		//$result = $mail->Ole_Send_Mail("CRON_WEEKLY_NMS_APPS", 17176, $data);

		if($result)
		{
			print "\r\nEMAIL HAS BEEN SENT TO: ".$recipients[$i]['email_primary']." .\n";
		}
		else
		{
			print "\r\nERROR SENDING EMAIL TO: ".$recipients[$i]['email_primary']." .\n";
		}
	}

	list ($em, $es) = explode (" ", microtime ());

	echo "\n";
	echo " ... Process Time: ".(((float)$es + (float)$em) - ((float)$ss + (float)$sm));
	echo "\n";
	echo "Process Completed.";
	echo "\n\n";
?>
