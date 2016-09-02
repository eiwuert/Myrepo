<?php
/**
*	Create React Emails from Cashline completetions from the last week.
*
*	Using entries in MySQL database react_db, generate emails inviting
*	good clients back for another loan.  The react_db has a table for
*	each company, with a listing for each client that paid off their
*	loans during the past week.
*
*	Beside creating the email, an entry is made in the react_verify
*	table that is used by the react login process to validate user
*	and collect the user information from the olp database.
*
*/


	// Set to true for testing on RC, false for LIVE
	// $testflag = 'True';
	$testflag = 'False';
	
	// Set the include path to have the libraries available
	//ini_set ("include_path", "/virtualhosts/lib/");
	ini_set ("session.use_cookies", "1");
	ini_set ("magic_quotes_runtime", "0");
	ini_set ("magic_quotes_gpc", "1");

	// We need to include the some libs
	require_once ("library.1.php");
	require_once ("error.2.php");
	require_once("prpc/client.php");

	$lib_path = Library_1::Get_Library ("debug", 1, 0);
	Error_2::Error_Test ($lib_path);
	require_once ($lib_path);

	$lib_path = Library_1::Get_Library ("mysql", 3, 0);
	Error_2::Error_Test ($lib_path);
	require_once ($lib_path);

	
	// Live Connection information
	define ("HOST","ds001.tss");
	define ("USER","sellingsource");
	define ("PASS","%selling\$_db");

	// Build the sql object
	$sql2 = new MySQL_3();	// connection for output of react emails
	$sql = new MySQL_3 ();	
	$database = "react_db";
	//$data_files = array ("ca","d1","pcl","ucl","ufc");
	$data_files = array ("ucl","ufc","ca","d1","pcl");


// Test the database connection
	$result2 = $sql2->Connect ("BOTH", HOST, USER, PASS, Debug_1::Trace_Code (__FILE__, __LINE__));
	$result = $sql->Connect ("BOTH", HOST, USER, PASS, Debug_1::Trace_Code (__FILE__, __LINE__));
	Error_2::Error_Test ($result, TRUE);
	foreach ($data_files as $datafile)
	{
		echo "looking at " . $datafile .  "\n";
		// select each entry for this company, fill out required data and ship to prpc email process
		$query =  "SELECT firstname,lastname,email,ssnumber FROM " . $datafile . "  WHERE status = 'INACTIVE' and email !='NULL'";
		$result = $sql->Query($database, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
		$recknt = $sql->Row_Count($result);
		switch ($datafile)
		{
			case "ca":
				$datalink = "ameriloan.com";
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

		echo "\n ---> Records selected from " .$datafile ."=" . $recknt . " " . $datalink ."\n";
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
			
			$ole_react_mail = new Prpc_Client("prpc://smtp.2.soapdataserver.com/ole_smtp.1.php",TRUE);
            //$ole_react_mail->setPrpcDieToFalse();
			$count = 0;
			//echo "Email try count: ".$count."\n";
			//while($react_mailing_id = $ole_react_mail->Ole_Send_Mail ("react_email", NULL, $email_data)==0)
			try
			{
				while($react_mailing_id = $ole_react_mail->Ole_Send_Mail ("react_email", NULL, $email_data)==0)
				{
					$count++;
					echo "Resend: " . $row['email'] . "\n";
					sleep(1);
					if ($count>5) break;
				}
			}
			catch (Exception $e)
			{
				echo "\nException: ".$e->getMessage();
				die;
			}
			
			$datetoday = mktime(date("H"),date("I"),date("S"),date("m"),date("d"),date("Y"));
			//$datesent = date('Ymd').'000000';
			$datesent = date('YmdHis');
			echo "Dates: ".$datesent;
			$ssnum = str_replace("-","",$row['ssnumber']);

			// update the react_verify record with all info.
			
			$query2 = "Insert into react_verify set reckey = '". $linkkey .
				"',namelast='" . addslashes($row['lastname']) .
				"',namefirst='" . addslashes($row['firstname']) .
				"',datesent='" . $datesent .
				"',ssn='" . $ssnum .
				"',property_short='" . $datafile ."' ;";
			
			$result2 = $sql2->Query($database, $query2, Debug_1::Trace_Code (__FILE__, __LINE__));
			Error_2::Error_Test ($result2,TRUE);
			//echo "React Updated \n";
			$counter++;
		
		}
		echo "---> ".$counter . " emails sent for " . $datafile . "\n";
		if (!$sql->Free_result($result))
		{
			  die ("Can't release query!");
		}
	}

?>
