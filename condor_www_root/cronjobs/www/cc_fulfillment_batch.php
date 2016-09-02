<?php
	// Creates a pipe-delimited data file to be sent off by email

	// Make sure we keep running even if user aborts
	ignore_user_abort (TRUE);

	// Let it run forever
	set_time_limit (0);

	// server configuration
	require_once ("/virtualhosts/site_config/server.cfg.php");

	$week_ago = date ("Y-m-d", strtotime ("-1 week"))." 00:00:00";
	$last_night = date ("Y-m-d", strtotime ("yesterday"))." 23:59:59";
	$dbase = "indata_prestigegold";
	$query = "SELECT * FROM orders WHERE signup BETWEEN \"".$week_ago."\" AND \"".$last_night."\" ORDER BY signup, last_name";
	$recipient_email = "david.bryant@thesellingsource.com";
	$date_stamp = date ("Ymd");
	$filename = "prestigegold_".$date_stamp.".csv";
	$field_array = array (
		"First Name" => "first_name",
		 "Last Name" => "last_name",
		 "Address 1" => "address1",
		 "Address 2" => "address2",
		      "City" => "city",
		     "State" => "state",
		  "Zip Code" => "zip",
		"Home Phone" => "homephone"
	);
	$sep = "|";

	$result = $sql->Query ($dbase, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
	Error_2::Error_Test ($result, TRUE);

	// Begin data block
	$file_data = "";

	// If field names are not wanted, comment this line out:
	$file_data .= implode ($sep, array_keys ($field_array))."\r\n";

	while ($data = $sql->Fetch_Object_Row ($result))
	{
		foreach ($field_array as $key => $field)
		{
			$file_data .= ($data->$field).$sep;
		}
		$file_data .= "\r\n";
		$file_data = str_replace ($sep."\r", "\r", $file_data);
	}
	// End data block

	$outer_boundary = md5 ("Outer Boundary");
	$inner_boundary = md5 ("Inner Boundary");

	$batch_headers  = "MIME-Version: 1.0\r\n";
	$batch_headers .= "Content-Type: Multipart/Mixed;";
	$batch_headers .= "boundary=\"".$outer_boundary."\"\r\n\r\n";
	$batch_headers .= "--".$outer_boundary."\r\n";
	$batch_headers .= "Content-Type: text/plain;";
	$batch_headers .= " charset=\"us-ascii\"\r\n";
	$batch_headers .= "Content-Transfer-Encoding: 7bit\r\n";
	$batch_headers .= "Content-Disposition: inline\r\n\r\n";
	$batch_headers .= "PrestigeGold Submits ".$date_stamp."\r\n\r\n";
	$batch_headers .= "--".$outer_boundary."\r\n";
	$batch_headers .= "Content-Type: text/plain;";
	$batch_headers .= " name=\"".$filename."\"\r\n";
	$batch_headers .= "Content-Transfer-Encoding: 7bit\r\n";
	$batch_headers .= "Content-Disposition: attachment;";
	$batch_headers .= " filename=\"".$filename."\"\r\n\r\n";
	$batch_headers .= $file_data."\r\n\r\n";
	$batch_headers .= "--".$outer_boundary."\r\n\r\n";

	// mail server bugfix.  They changed something on IBM.
	$batch_headers = str_replace ("\r", "", $batch_headers);

		// Send the file to ed for processing
	mail ($recipient_email, $filename, NULL, $batch_headers);
?>
