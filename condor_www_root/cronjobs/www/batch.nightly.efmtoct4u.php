<?php
	// ======================================================================
	// EMF(BMG167) => CT4U Nightly Batch
	// 
	// Send leads that were sent to BMG167 the night before to CT4U
	// Most code was taken from Page2Drops Cron
	//
	// myya.perez@thesellingsource.com 05-27-2005
	// ======================================================================
	
	
	// INCLUDES / DEFINES / INITIALIZE VARIABLES
	// ======================================================================
	require_once('mysql.3.php');
	require_once('debug.1.php');
	require_once('error.2.php');
	require_once('csv.1.php');
	require_once('ftp.2.php');
	require_once('hit_stats.1.php');
	require_once('lgen.record.1.php');
	require_once("HTTP/Request.php"); // pear
	
	define('MAX_RECORDS', 100);
	define('LICENSE_KEY',  '3301577eb098835e4d771d4cceb6542b');
	define('STAT_COL', 'h11');	
	define('URL', 'http://www.cash2day4u.com/leadpost.php');
	//define('URL', 'http://test.ds28.tss/catch_post_data.php'); // testing
	echo '<pre>';
	//$start 	= date("Ymd000000", strtotime("-2 day"));
	//$end 	= date("Ymd235959", strtotime("-2 day"));	
	$start 	= "20050624000000";
	$end 	= "20050624235959";	
	
	
	// SQL CONNECT
	// ======================================================================		
	
	$sql=new MySQL_3();
	$sql->connect("both","selsds001","sellingsource","%selling\$_db");	


	// MANAGE TMPTABLE - DROP AND CREATE NEW
	// ======================================================================

	$sql->query("lead_generation", "DROP TABLE TmpTableCT4U");
	$_TmpTableCT4U = $sql->query("lead_generation","
	CREATE TABLE `TmpTableCT4U` (
	`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
	`modified` timestamp(14) NOT NULL,
	`CreatedDate` timestamp(14) NOT NULL,
	`Referrer` varchar(250) NOT NULL DEFAULT '',
	`ApplicationID` varchar(25) NOT NULL DEFAULT '',
	`Source` varchar(50) NOT NULL DEFAULT '',
	`FirstName` varchar(50) NOT NULL DEFAULT '',
	`LastName` varchar(50) NOT NULL DEFAULT '',
	`DOB` varchar(20) NOT NULL DEFAULT '',
	`License` varchar(25) NOT NULL DEFAULT '',
	`LicenceState` varchar(2) NOT NULL DEFAULT '',	
	`Email` varchar(100) NOT NULL DEFAULT '',
	`Addr` varchar(120) NOT NULL DEFAULT '',
	`City` varchar(40) NOT NULL DEFAULT '',
	`State` char(2) NOT NULL DEFAULT '',
	`Zip` varchar(15) NOT NULL DEFAULT '',	
	`HomePhone` varchar(20) NOT NULL DEFAULT '',
	`WorkPhone` varchar(20) NOT NULL DEFAULT '',
	`CellPhone` varchar(20) NOT NULL DEFAULT '', 
	`CallTime` varchar(25) NOT NULL DEFAULT '',
	`Employer` varchar(100) NOT NULL DEFAULT '',
	`HireDate` date NOT NULL DEFAULT '',	
	`MonthlyIncome` int(9) NOT NULL DEFAULT 0,
	`NextPayDate1` varchar(10) NOT NULL DEFAULT '',
	`NextPayDate2` varchar(10) NOT NULL DEFAULT '',	
	`IncomeType` varchar(50) NOT NULL DEFAULT '',	
	`PayPeriod` varchar(45) NOT NULL DEFAULT '',
	`DirectDeposit` varchar(3) NOT NULL DEFAULT '',
	`BankName` varchar(100) NOT NULL DEFAULT '',
	`RoutingNumber` varchar(12) NOT NULL DEFAULT '',
	`BankAccountNumber` varchar(12) NOT NULL DEFAULT '',
	`BankAccountType` varchar(12) NOT NULL DEFAULT '',
	`IPAddress` varchar(15) NOT NULL DEFAULT '',
	`Name1` varchar(50) NOT NULL DEFAULT '',
	`Phone1` varchar(12) NOT NULL DEFAULT '',
	`Relationship1` varchar(25) NOT NULL DEFAULT '',
	`Name2` varchar(50) NOT NULL DEFAULT '',
	`Phone2` varchar(12) NOT NULL DEFAULT '',
	`Relationship2` varchar(25) NOT NULL DEFAULT '',	
	`SSN` varchar(20) NOT NULL DEFAULT '',
	PRIMARY KEY(`id`),
	UNIQUE KEY `email` (`email`)
	) TYPE=MyISAM;
	", Debug_1::Trace_Code(__FILE__,__LINE__));
	
	print_r($_TmpTableCT4U);
	
	
	// QUERY
	// ======================================================================		
		
	echo $query = "	
		SELECT *
		FROM blackbox_post 
		WHERE date_created between '$start' and '$end' 
		AND winner = 'efm'
		AND success = 'TRUE'
	";

	$result = $sql->query("olp", $query);
	$count 	= $sql->Row_Count($result);
	
	print "\r\nResult Count: ".$count."\r\n";
	
	
	// MANAGE DATA
	// ======================================================================		
	
	while($row = $sql->Fetch_Array_Row($result))
	{

		// GRAB EXTRA DATA NEEDED FOR THIS OFFER
		
		$reference = array();
		$query2 = "
			SELECT *
			FROM personal_contact
			WHERE application_id = '".mysql_escape_string($row["application_id"])."'
			LIMIT 10
		";		
	
		$result2 = $sql->query("olp", $query2);		
		
		$i = 0;
		while($row2 = $sql->Fetch_Array_Row($result2))
		{
			$reference[$i]["name"] = $row2["full_name"];
			$reference[$i]["phone"] = $row2["phone"];
			$reference[$i]["relationship"] = $row2["relationship"];
			$i++;
		}

		$extra_info = array();
		$query3 = "
			SELECT *
			FROM 
				application a,
				personal p,
				bank_info b,
				campaign_info c,
				employment e
			WHERE a.application_id = '923859'
			AND a.application_id = p.application_id
			AND a.application_id = b.application_id
			AND a.application_id = c.application_id
			AND a.application_id = e.application_id
			LIMIT 1
		";

		$result3 = $sql->query("olp", $query3);
		
		while($row3 = $sql->Fetch_Array_Row($result3))
		{
			$extra_info["CreatedDate"] 		= $row3["created_date"];
			$extra_info["Referrer"] 		= $row3["url"];
			$extra_info["CellPhone"] 		= $row3["cell_phone"];
			$extra_info["CallTime"] 		= $row3["best_call_time"];
			$extra_info["HireDate"] 		= $row3["date_of_hire"];			
			$extra_info["BankName"] 		= $row3["bank_name"];
			$extra_info["BankAccountType"] 	= $row3["bank_account_type"];
			$extra_info["IncomeType"] 		= $row3["income_type"];
		}	
		
		// INSERT IGNORE INTO TMP TABLE TO REMOVE DUPES
		
		$sent_ary = unserialize($row["data_sent"]);
		$query4 = "
			INSERT IGNORE INTO TmpTableCT4U 
			SET 
				 Source='Partner'
				,CreatedDate='".mysql_escape_string($extra_info["CreatedDate"])."'
				,Referrer='".mysql_escape_string($extra_info["Referrer"])."'
				,ApplicationID='".mysql_escape_string($row["application_id"])."'
				,FirstName='".mysql_escape_string($sent_ary["First_Name"])."'
				,LastName='".mysql_escape_string($sent_ary["Last_Name"])."'
				,DOB='".mysql_escape_string($sent_ary["DOB"])."'
				,License='".mysql_escape_string($sent_ary["DL_Number"])."'
				,LicenceState='".mysql_escape_string($sent_ary["DL_State"])."'
				,Email='".mysql_escape_string($sent_ary["Email"])."'
				,Addr='".mysql_escape_string($sent_ary["Address"])."'
				,City='".mysql_escape_string($sent_ary["City"])."'
				,State='".mysql_escape_string($sent_ary["State"])."'
				,Zip='".mysql_escape_string($sent_ary["Zip"])."'
				,HomePhone='".mysql_escape_string($sent_ary["Alt_Phone"])."'
				,WorkPhone='".mysql_escape_string($sent_ary["Phone"])."'
				,CellPhone='".mysql_escape_string($extra_info["CellPhone"])."'
				,CallTime='".mysql_escape_string($extra_info["CallTime"])."'
				,Employer='".mysql_escape_string($sent_ary["Employer"])."'
				,HireDate='".mysql_escape_string($extra_info["HireDate"])."'
				,MonthlyIncome='".mysql_escape_string($sent_ary["Employee_Income"])."'
				,NextPayDate1='".mysql_escape_string($sent_ary["Employee_Next_Pay_Day"])."'
				,NextPayDate2='".mysql_escape_string($sent_ary["Employee_Next_Next_Pay_Day"])."'
				,IncomeType='".mysql_escape_string($extra_info["IncomeType"])."'
				,PayPeriod='".mysql_escape_string($sent_ary["Employee_Days_Paid"])."'
				,DirectDeposit='".mysql_escape_string($sent_ary["Direct_Deposit"])."'
				,BankName='".mysql_escape_string($extra_info["BankName"])."'
				,RoutingNumber='".mysql_escape_string($sent_ary["Bank_ABA"])."'
				,BankAccountNumber='".mysql_escape_string($sent_ary["Account_Number"])."'
				,BankAccountType='".mysql_escape_string($extra_info["BankAccountType"])."'
				,IPAddress='".mysql_escape_string($sent_ary["IPAddress"])."'
				,Name1='".mysql_escape_string($reference["0"]["name"])."'
				,Phone1='".mysql_escape_string($reference["0"]["phone"])."'
				,Relationship1='".mysql_escape_string($reference["0"]["relationship"])."'
				,Name2='".mysql_escape_string($reference["1"]["name"])."'
				,Phone2='".mysql_escape_string($reference["1"]["phone"])."'
				,Relationship2='".mysql_escape_string($reference["1"]["relationship"])."'
				,SSN='".mysql_escape_string($sent_ary["SSN"])."'
		";
		
		$result4 = $sql->query("lead_generation", $query4);

		if($sql->Affected_Row_Count($result4)==0)
		{
			print "\nRemoved: Not Unique: {$row['email']}";
		}
	}

	
	// REGRAB DATA FROM TMP TABLE THIS TIME
	// ======================================================================		
	
	$three_months_ago = date("Y-m-d", strtotime("-3 month"));
	$query5 = " 
		SELECT * 
		FROM TmpTableCT4U 
		WHERE HireDate < '".mysql_escape_string($three_months_ago)."'
		AND DirectDeposit = 'Y'	
	";
	$result5 = $sql->query("lead_generation", $query5);
	while($row5 = $sql->Fetch_Array_Row($result5))
	{
		$data[] = $row5;
	}
	
	
	// POST DATA TO CT4U
	// ======================================================================		
	$cnt = 0;
	foreach($data as $row)
	{
		if ( $cnt == MAX_RECORDS ) break;
		
		$fields = array 
		(
			"Source" 			=> "Partner",
			"FirstName" 		=> $row["FirstName"],
			"LastName" 			=> $row["LastName"],
			"DOB" 				=> date("Y/m/d", strtotime($row['DOB'])),
			"License" 			=> $row["License"],
			"LicenceState" 		=> $row["LicenceState"],
			"Email" 			=> $row["Email"],
			"Addr" 				=> $row["Addr"],
			"City" 				=> $row["City"],
			"State" 			=> $row["State"],
			"Zip" 				=> $row["Zip"],
			"HomePhone" 		=> $row["HomePhone"],
			"WorkPhone" 		=> $row["WorkPhone"],
			"CellPhone" 		=> $row["CellPhone"],
			"CallTime" 			=> $row["CallTime"],
			"Employer" 			=> $row["Employer"],
			"EmploymentStatus"	=> (strtolower($row['IncomeType']) == 'employment') ? 'Employed' : 'Benefits',
			"MonthlyIncome" 	=> $row["MonthlyIncome"],
			"NextPayDate1" 		=> date("Y/m/d", strtotime($row['NextPayDate1'])),
			"NextPayDate2" 		=> date("Y/m/d", strtotime($row['NextPayDate2'])),
			"IncomeType" 		=> $row["IncomeType"],
			"PayPeriod" 		=> $row["PayPeriod"],
			"DirectDeposit" 	=> 'Yes',
			"BankName" 			=> $row["BankName"],
			"RoutingNumber" 	=> $row["RoutingNumber"],
			"BankAccountNumber" => $row["BankAccountNumber"],
			"BankAccountType" 	=> $row["BankAccountType"],
			"IPAddress" 		=> $row["IPAddress"],
			"Name1" 			=> $row["Name1"],
			"Phone1" 			=> $row["Phone1"],
			"Relationship1" 	=> $row["Relationship1"],
			"Name2" 			=> $row["Name2"],
			"Phone2" 			=> $row["Phone2"],
			"Relationship2" 	=> $row["Relationship2"],	
			"SSN" 				=> $row["SSN"],
		);

		//print_r($fields);
		
		
		// ==================================================
		// BEGIN SEND
		
		print "\n\n" . date("H:i:s") . " - START CT SEND ". $row["ApplicationID"];
		
		$net = new HTTP_Request(URL);
		$net->setMethod(HTTP_REQUEST_METHOD_POST);
		reset($fields);
		while (list($k, $v) = each($fields))
		{
			$net->addPostData($k, $v);
		}
		$net->sendRequest();
		$resp = $net->getResponseBody();
		
		print "\nRESPONSE: " . $resp;

		if ( trim($resp) == "OK" )
		{
			$cnt++;
		}
		
		print "\n" . date("H:i:s") . " - END CT SEND " . $row["ApplicationID"]; 
		
		// END SEND
		// ==================================================
		
	}		
	
	
	// HIT STAT
	// ======================================================================		
	
	Hit::Stats_Promoless(LICENSE_KEY, $sql, STAT_COL, $result_count);

	print "\r\rDONE AND DONE\r\r";
	



?>
