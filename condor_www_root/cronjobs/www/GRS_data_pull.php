<?PHP
	// ======================================================================
	// GRS 30 DAY BATCH => batch.legacy.GRS.1.php
	//
	// Grab the last 30 days worth of leads from the BlackBox database and 
	// then scrub them against the CLK funded table. Then, the remaining 
	// records need to be put in to a CSV file and uploaded to GRS. 
	//
	// myya.perez@thesellingsource.com 08-02-2005
	// ======================================================================
	
	
	// INCLUDES / DEFINES / INITIALIZE VARIABLES
	// ======================================================================
	
	require_once ("mysql.3.php");
	echo '<pre>';
	
	
	// SQL CONNECT & QUERY
	// ======================================================================		
	
	$sql=new MySQL_3();
	$sql->connect("both","selsds001","sellingsource","%selling\$_db");

	$query = "
		SELECT 
			 application.application_id
			,personal.email AS email
			,personal.first_name AS fname
			,personal.last_name AS lname
			,residence.address_1
			,residence.address_2
			,residence.city
			,residence.state
			,residence.zip
			,personal.home_phone AS phone
			,personal.date_of_birth AS dob
			,campaign_info.ip_address AS ip
			,application.created_date AS created
			,personal.social_security_number AS ssn
			,target.name AS target_name
			,target.tier_id as tier
		FROM 
			application,
			personal,
			residence,
			campaign_info,
			target 
		WHERE application.created_date BETWEEN '20050729000000' AND '200507292359559'
		AND application.application_id = personal.application_id
		AND application.application_id = residence.application_id
		AND application.application_id = campaign_info.application_id
		AND application.application_type != 'VISITOR'
		AND application.target_id = target.target_id 
		AND personal.first_name != ''
		AND personal.first_name IS NOT NULL
		AND personal.last_name != ''
		AND personal.last_name IS NOT NULL
		AND personal.home_phone != ''
		AND personal.home_phone IS NOT NULL	
		AND personal.email != ''
		AND personal.email IS NOT NULL
		AND residence.address_1 != ''
		AND residence.address_1 IS NOT NULL
		AND campaign_info.ip_address != ''
		AND campaign_info.ip_address IS NOT NULL
		AND personal.social_security_number != ''
		AND personal.social_security_number IS NOT NULL		
	";

	$result = $sql->query("olp", $query);
	
	$result_count = $sql->Row_Count($result);
	print "\r\nResult Count - ".$result_count."\r\n";

	
	// RESULTS
	//============================================================		

	$i=0;
	while ($row = $sql->Fetch_Array_Row($result))
	{
		echo $i."<br>";
		$query2 = "
			INSERT IGNORE INTO TmpTableGRS
			SET
				 date_created = NOW()
				,application_id = '" . mysql_escape_string($row["application_id"]) . "'
				,email = '" . mysql_escape_string($row["email"]) . "'
				,fname = '" . mysql_escape_string($row["fname"]) . "'
				,lname = '" . mysql_escape_string($row["lname"]) . "'
				,address_1 = '" . mysql_escape_string($row["address_1"]) . "'
				,address_2 = '" . mysql_escape_string($row["address_2"]) . "'
				,city = '" . mysql_escape_string($row["city"]) . "'
				,state = '" . mysql_escape_string($row["state"]) . "'
				,zip = '" . mysql_escape_string($row["zip"]) . "'
				,phone = '" . mysql_escape_string($row["phone"]) . "'
				,dob = '" . mysql_escape_string($row["dob"]) . "'
				,ip = '" . mysql_escape_string($row["ip"]) . "'
				,created = '" . mysql_escape_string($row["created"]) . "'
				,ssn = '" . mysql_escape_string($row["ssn"]) . "'
				,target_name = '" . mysql_escape_string($row["target_name"]) . "'
				,tier = '" . mysql_escape_string($row["tier"]) . "'
		";		
		$result2 = $sql->Query("lead_generation", $query2);
		$i++;
	}

	print "\r\rDONE AND DONE\r\r";

?>
