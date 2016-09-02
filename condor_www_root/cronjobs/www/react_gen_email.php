<?php
	// Set to true for testing on RC, false for LIVE
	// $testflag = 'True';
	$testflag = 'False';
	//echo "show_aba <br>";
	//echo "Route: " . $routing_number . "<br>";
	//echo "Account: " . $account_number . "<br>";
	
	// Set the include path to have the libraries available
	ini_set ("include_path", "/virtualhosts/lib/");
	ini_set ("session.use_cookies", "1");
	ini_set ("magic_quotes_runtime", "1");
	ini_set ("magic_quotes_gpc", "1");

	// We need to include the some libs
	require_once ("library.1.php");
	require_once ("error.2.php");

	$lib_path = Library_1::Get_Library ("debug", 1, 0);
	Error_2::Error_Test ($lib_path);
	require_once ($lib_path);

	$lib_path = Library_1::Get_Library ("mysql", 3, 0);
	Error_2::Error_Test ($lib_path);
	require_once ($lib_path);

	// TSS Connection information
	// only this or the following block should be
	// active at any time
//	define ("HOST", "localhost");
//	define ("USER", "root");
//	define ("PASS", "");
	
	// Live Connection information
	define ("HOST","ds001.tss");
	define ("USER","sellingsource");
	define ("PASS","%selling\$_db");

	// Build the sql object
	$sql2 = new MySQL_3();	// connection for output of react emails
	$sql = new MySQL_3 ();	
	$database = "react_db";
	// removed d1 from list for now
	$data_files = array ("ca","pcl","ucl","ufc");

//	$data_files = array (	"ca"->"paydaycentral.com",
//				"d1"->"500fastcash.com",
//				"pcl"->"oneclickcash.com",
//				"ucl"->"unitedcashloans.com",
//				"ufc"->"usfastcash.com");

// Test the database connection
	$result2 = $sql2->Connect ("BOTH", HOST, USER, PASS, Debug_1::Trace_Code (__FILE__, __LINE__));
	$result = $sql->Connect ("BOTH", HOST, USER, PASS, Debug_1::Trace_Code (__FILE__, __LINE__));
	Error_2::Error_Test ($result, TRUE);
	foreach ($data_files as $datafile)
	{
		//echo "looking at " . $datafile .  "\n";
		$query =  "SELECT firstname,lastname,email,ssnumber FROM " . $datafile . "  WHERE status = 'INACTIVE' and email !='NULL'";
		$result = $sql->Query($database, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
		$recknt = $sql->Row_Count($result);
		switch ($datafile)
		{
			case "ca":
				$datalink = "paydaycentral.com";
				$promo = "promo_id=26184"; 
				break;
			case "d1":
				$datalink = "500fastcash.com";
				$promo = "promo_id=26181";
				break;
			case "pcl":
				$datalink = "oneclickcash.com";
				$promo = "promo_id=26182";
				break;
			case "ucl":
				$datalink = "unitedcashloans.com";
				$promo = "promo_id=26183";
				break;
			case "ufc":
				$datalink = "usfastcash.com";
				$promo = "promo_id=26185";
				break;
		}
		if ($testflag == 'True')
		{
			$datalink = "RC.".$datalink;
		}

		echo "Records selected from " .$datafile ."=" . $recknt . " " . $datalink ."\n";
		Error_2::Error_Test ($result,TRUE);
		$counter = 0;
		while ($row = $sql->Fetch_Array_Row($result)) // get a row from our result set
		{
			echo $row['email'] . "\n";
			$today = date("F j, Y, g:i a");
			$linkkey = md5('tss_'.$row['firstname'].$row['lastname'].$today);
			$link = "http://".$datalink ."/?" . $promo . "&page=ent_cs_confirm_start&reckey=" . $linkkey;
			$email_data['site_name'] = $datalink;
			$email_data['link'] = $link;
			$email_data['email_primary'] = $row['email'];
			$email_data['email_primary_name'] = $row['firstname'] . " " . $row['lastname'];
			echo "Email: <pre>";print_r($email_data);
			
			include_once("prpc/client.php");
			$ole_debug_mail = new prpc_client("prpc://smtp.2.soapdataserver.com/ole_smtp.1.php");
                        $debug_mailing_id = $ole_debug_mail->Ole_Send_Mail ("react_email", NULL, $email_data);
			
			$datetoday = mktime(date("H"),date("I"),date("S"),date("m"),date("d"),date("Y"));
			$datesent = date('YmdHIs');
			$ssnum = str_replace("-","",$row['ssnumber']);
			
			$query2 = "Insert into react_verify set reckey = '". $linkkey .
				"',namelast='" . addslashes($row['lastname']) .
				"',namefirst='" . addslashes($row['firstname']) .
				"',datesent='" . $datesent .
				"',ssn='" . $ssnum .
				"',property_short='" . $datafile ."' ;";
			
				$result2 = $sql2->Query($database, $query2, Debug_1::Trace_Code (__FILE__, __LINE__));
			Error_2::Error_Test ($result2,TRUE);
			$counter++;
		
		}
		echo $counter . " emails sent for " . $datafile . "\n";
		if (!$sql->Free_result($result))
		{
			  die ("Can't release query!");
		}
	}

?>

