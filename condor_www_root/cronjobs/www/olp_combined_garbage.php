<?php

/*

Combined OLP Garbage collection.

Last Update: 05/20/2004

*/

// OLP Databases and their corresponding session tables
$olp_dbs = array("olp_ucl_visitor" => "session_site", "olp_ca_visitor" => "session_site", "olp_pcl_visitor" => "session_site");

// Database connection
$db_host = "selsds001";
$db_user = "sellingsource";
$db_pass = "%selling\$_db";

$batch_size = 200;  // Number of sessions to work with at one time.

// Some required files
require_once ("/virtualhosts/lib/mysql.3.php");
require_once ("/virtualhosts/lib/error.2.php");
include_once ("/virtualhosts/lib/null_session.1.php");

// Build the sql object
$sql = new MySQL_3();

// Try the connection
$result = $sql->Connect ("BOTH", $db_host, $db_user, $db_pass, Debug_1::Trace_Code (__FILE__, __LINE__));
Error_2::Error_Test ($result, TRUE);

// Build the session handling object
$session = new Null_Session_1();

// Set the session name
session_name ("unique_id");

// Establish the session parameters
session_set_save_handler
(
	array (&$session_plop, "Open"),
	array (&$session_plop, "Close"),
	array (&$session_plop, "Read"),
	array (&$session_plop, "Write"),
	array (&$session_plop, "Destroy"),
	array (&$session_plop, "Garbage_Collection")
);

// Loop thru our databases
foreach($olp_dbs as $database => $session_table)
{
	// Get a count of all sessions in the qualified date range
	$query = "SELECT count(*) as count FROM {$session_table} WHERE  created_date < curdate()'";

	$result = $sql->Query($database, $query);
	Error_2::Error_Test ($result, TRUE);

	$count = $sql->Fetch_Column($result, "count");

	// Figure out how many iterations we will need based on the count and our batch size
	$num_iterations = ceil($count / $batch_size);
	
	for($i = 0; $i < $num_iterations; $i++)
	{
		$start_point = $i * $batch_size;  // Used to set the begenning point in our limit clause
		
		// Query the session table and left join it to application.
		$query = "SELECT 
					session_id,
					session_info,
					application.type
				 FROM
					{$session_table}
					LEFT JOIN application on {$session_table}.session_id = application.session_id
				WHERE 
					created_date < curdate() 
				LIMIT {$start_point}, {$batch_size}";
		
		$result = $sql->Query($database, $query);
		
		// Loop thru current batch
		while($row = $sql->Fetch_Object($result) )
		{
			// Set these to a blank array every iteration.
			$_SESSION = array();
			$delete_array = array();

			session_decode ($row->session_info);
			
			if ( !isset($_SESSION ["personal"]["email"]) )
			{
				$delete_array[] = $row->session_id;
			}
			elseif( !isset($_SESSION['data']['email_primary']) )
			{
				$delete_array[] = $row->session_id;
			}
			elseif( $row->type != 'QUALIFIED' && $row->type != 'VISITOR' )
			{
				$query = "SELECT 
							first_name,
							bank_name
						FROM
							personal,
							bank_info
						WHERE
							personal.application_id = {$row->application_id}
							AND	bank_info.application_id = personal.application_id";
			}
			
			// If we have elements in our delete array lets nuke em!
			if( count($delete_array) )
			{
				$query = "DELETE FROM {$session_table} WHERE session_id in(".implode(".", $delete_array).")";
			}
		}
	}
}

?>