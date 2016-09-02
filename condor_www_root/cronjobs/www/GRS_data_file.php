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
	require_once ("ftp.2.php");
	$data = array();
	$path = "/tmp/";
	$csvfile = "tss_legacy_2005_07.csv";
	$path_to_csvfile = $path . $csvfile;	
	echo '<pre>';
	
	
	// SQL CONNECT & QUERY
	// ======================================================================		
	
	$sql=new MySQL_3();
	$sql->connect("both","selsds001","sellingsource","%selling\$_db");

	$query = "SELECT * FROM TmpTableGRS WHERE clk_funded = ''";
	$result = $sql->query("lead_generation", $query);
	//$result_count = $sql->Row_Count($result);
	print "\r\nResult Count - ".$result_count."\r\n";

	
	// RESULTS
	//============================================================		
	
	$fp = fopen($path_to_csvfile,"w");
	
	while ($grs_row = $sql->Fetch_Array_Row($result))
	{	
		$grs_row["dob"] = str_replace("-","/",$grs_row["dob"]);
		$grs_row["created"] = substr($grs_row["created"],"6","2")."/".substr($grs_row["created"],"4","2")."/".substr($grs_row["created"],"0","4");
		fwrite($fp,"{$grs_row["email"]}\t{$grs_row["fname"]}\t{$grs_row["lname"]}\t{$grs_row["address_1"]}\t{$grs_row["address_2"]}\t{$grs_row["city"]}\t{$grs_row["state"]}\t{$grs_row["zip"]}\t\t{$grs_row["phone"]}\t\t{$grs_row["dob"]}\t\t{$grs_row["ip"]}\t{$grs_row["created"]}\r\n");
	}
	
	fclose($fp);
	
	
	// FTP
	//============================================================	
	
	$ftp_client = new FTP();
	$ftp_client->server = "ftp.grscorp.com";
	$ftp_client->user_name = "sellingsource";
	$ftp_client->user_password = "password";	
	
	print "\r\rDONE\r\r";
	

?>
