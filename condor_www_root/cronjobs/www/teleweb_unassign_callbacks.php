<?php

// Make sure we keep running even if user aborts
ignore_user_abort (TRUE);
	
// Let it run forever
set_time_limit (0);
	
// Database connectivity
require_once ("/virtualhosts/lib/mysql.3.php");
require_once ("/virtualhosts/lib/error.2.php");

$mode = strtoupper($argv[1]);

// Connection information
switch($mode)
{
//RC
 case "RC":
	 define ("HOST", "selsds001");
	 define ("USER", "admin");
	 define ("PASS", "%selling\$_db");
	 $vdb_prefix="rc_olp_";
	 $vdb_suffix="_visitor";
	 break;

//LIVE
 case "LIVE":
	 define ("HOST", "selsds001");
	 define ("USER", "admin");
	 define ("PASS", "%selling\$_db");
	 $vdb_prefix="olp_";
	 $vdb_suffix="_visitor";
	 break;

//TEST
 case "LOCAL":
 default:
	 define ("HOST", "localhost");
	 define ("USER", "root");
	 define ("PASS", "");
	 $vdb_prefix="olp_";
	 $vdb_suffix="_visitor";
	 break;	
}


// Build the sql object
$sql = new MySQL_3 ();
	
// Try the connection
$result = $sql->Connect ("BOTH", HOST, USER, PASS, Debug_1::Trace_Code (__FILE__, __LINE__));
Error_2::Error_Test ($result, TRUE);



$freeup_callbacks_query = "
update account acc, csr_status c, application a
set acc.assigned_csr = NULL
where a.application_id = acc.active_application_id
and a.application_id = c.application_id
and a.type = 'PROSPECT'
and acc.assigned_csr is not null
and c.status = 'CALL_BACK'
and c.call_back_time < now()
and a.created_date > date_sub(now(), INTERVAL 30 day)
";

$delete_callbacks = "
delete from csr_status
where status = 'CALL_BACK'
and call_back_time < now()
";

$create_temp_table = "
create temporary table max_comments
(
    application_id int(11),
    modified_date timestamp,
)
";

$max_comments = "
insert into max_comments
select com.application_id, max(com.modified_date)
from comment com
group by application_id
";


$freeup_followups_query = "
update account acc, application a, max_comments com
left join csr_status c on a.application_id = c.application_id
set acc.assigned_csr = NULL
where 
    a.application_id = acc.active_application_id
and a.application_id = com.application_id
and c.call_back_time is null
and a.type = 'PROSPECT'
and acc.assigned_csr is not null
and com.modified_date < date_sub(now(), INTERVAL 1 day)
and a.created_date > date_sub(now(), INTERVAL 30 day)
";


//iterate through the properties
$property_array = array("ca", "ucl", "pcl"); //

foreach($property_array as $property)
{
	$visitor_database = $vdb_prefix . $property . $vdb_suffix;

	//print "$query\n";
	$result = $sql->Query ($visitor_database, $freeup_callbacks_query, Debug_1::Trace_Code (__FILE__, __LINE__));
	Error_2::Error_Test ($result, TRUE);

	$result = $sql->Query ($visitor_database, $delete_callbacks, Debug_1::Trace_Code (__FILE__, __LINE__));
	Error_2::Error_Test ($result, TRUE);

	$result = $sql->Query ($visitor_database, $create_temp_table, Debug_1::Trace_Code (__FILE__, __LINE__));
	Error_2::Error_Test ($result, TRUE);

	$result = $sql->Query ($visitor_database, $max_comments, Debug_1::Trace_Code (__FILE__, __LINE__));
	Error_2::Error_Test ($result, TRUE);

	$result = $sql->Query ($visitor_database, $freeup_followups_query, Debug_1::Trace_Code (__FILE__, __LINE__));
	Error_2::Error_Test ($result, TRUE);

} // end foreach property

?>
