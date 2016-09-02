<?php
	// A cron job to clean smtp

	// Connect
	$link_id = mysql_connect ("write1.iwaynetworks.net", "admin", "%selling\$_db");
	mysql_select_db ("smtp", $link_id);

	// Get the list of completed mailings
	$query = "select mailing_id, package_id from package where send_date < '".date ("YmdHis", strtotime ("-1 hour"))."'";
	$result_id = mysql_query ($query, $link_id);

	$count = 0;

	while (FALSE !== ($row_info = mysql_fetch_object ($result_id)))
	{
		$count ++;
		$mail_id_list .= $row_info->mailing_id.",";
		$package_id_list .= $row_info->package_id.",";
	}

	// Purge the mess
	$query = "delete from mailing where mailing_id in (".substr ($mail_id_list, 0, -1).")";
	mysql_query ($query, $link_id);

	$query = "delete from attachment where package_id in (".substr ($package_id_list, 0, -1).")";
	mysql_query ($query, $link_id);

	$query = "delete from recipient where package_id in (".substr ($package_id_list, 0, -1).")";
	mysql_query ($query, $link_id);

	// Do the package last to prevent orphan attachment/recipient rows in the event of a failure
	$query = "delete from package where package_id in (".substr ($package_id_list, 0, -1).")";
	mysql_query ($query, $link_id);

	// Optimize the tables now that they are smaller
	$query = "optimize table mailing, package, recipient, attachement";
	mysql_query ($query, $link_id);

	// All done
	mysql_close ($link_id);

	//echo "Processed: ".$count."<br>";
?>
