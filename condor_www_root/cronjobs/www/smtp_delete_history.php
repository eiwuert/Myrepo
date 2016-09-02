<?php
	$date = date ("YmdHis", strtotime ("-21 days"));
	// A cron job to clean smtp
	// Connect
	$link_id = mysql_connect ("selsds001", "sellingsource", "%selling\$_db");
	mysql_select_db ("smtp", $link_id);
	// Get the list of completed mailings
		$count = 0;
	do{		
		$query = "DELETE FROM mailing WHERE created_date < '".$date."' LIMIT 1000";
		$result_id = mysql_query ($query, $link_id);
		if( ($count%100) == 0) echo $query."\n";
		$rows = mysql_affected_rows();
		$count++;
	}while ($rows>0);

		$count = 0;
	do{		
		$query = "DELETE FROM campaign WHERE created_date < '".$date."' LIMIT 1000";
		$result_id = mysql_query ($query, $link_id);
		if( ($count%100) == 0) echo $query."\n";
		$rows = mysql_affected_rows();
		$count++;
	}while ($rows>0);
	
		$count = 0;
	do{		
		$query = "DELETE FROM package WHERE send_date < '".$date."' LIMIT 100";
		$result_id = mysql_query ($query, $link_id);
		if( ($count%100) == 0) echo $query."\n";
		$rows = mysql_affected_rows();
		$count++;
	}while ($rows>0);
	
		$count = 0;
	do{		
		$query = "DELETE FROM recipient WHERE created_date < '".$date."' LIMIT 1000";
		$result_id = mysql_query ($query, $link_id);
		if( ($count%100) == 0) echo $query."\n";
		$rows = mysql_affected_rows();
		$count++;
	}while ($rows>0);
	
	$date = date ("YmdHis", strtotime ("-4 days"));
		$count = 0;
	do{		
		$query = "DELETE FROM attachment WHERE created_date < '".$date."' LIMIT 50";
		$result_id = mysql_query ($query, $link_id);
		if( ($count%100) == 0) echo $query."\n";
		$rows = mysql_affected_rows();
		$count++;
	}while ($rows>0);
	
	// Optimize the tables now that they are smaller
	$query = "optimize table mailing, package, recipient, attachement, campaign";
	mysql_query ($query, $link_id);

	// All done
	mysql_close ($link_id);
?>
