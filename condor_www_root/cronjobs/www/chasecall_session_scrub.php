<?php

ini_set('session.bug_compat_42', 1);
ini_set('session.bug_compat_warn', 0);
ini_set('magic_quotes_runtime', 0);
ini_set('implicit_flush', 1);
ini_set('output_buffering', 0);
ob_implicit_flush();
ignore_user_abort(TRUE);
set_time_limit (0);

// Database connectivity
require_once ("mysql.3.php");
require_once ("error.2.php");
require_once ("null_session.1.php");
require_once ("security.3.php");
require_once ("lib_mail.1.php");


// fake the blackbox class
class Blackbox
{
};


$mode = strtoupper($argv[1]);

// Connection information
switch($mode)
{
//RC
case "RC":
	define ("HOST", "selsds001");
	define ("USER", "admin");
	define ("PASS", "%selling\$_db");
	define ("DB_OLP", "rc_olp");
	define ("DB_CHASECALL", "rc_olp_chasecall");
	break;

//LIVE
case "LIVE":
	define ("HOST", "selsds001");
	define ("USER", "admin");
	define ("PASS", "%selling\$_db");
	define ("DB_OLP", "olp");
	define ("DB_CHASECALL", "olp_chasecall");
	break;

//TEST
case "LOCAL":
default:
	define ("HOST", "localhost");
	define ("USER", "root");
	define ("PASS", "");
	define ("DB_OLP", "olp");
	define ("DB_CHASECALL", "olp_chasecall");
	break;
}

print("\nRunning in mode $mode:");
print("\nDB Host: " . HOST);
print("\nDB User: " . USER);
print("\nDB Pass: " . PASS);
print("\nOLP DB Name: " . DB_OLP);
print("\nChasecall DB Name: " . DB_CHASECALL);

function Scrub_Sessions($db, $session_table, $date_start, $date_end)
{
	// Pull the application and session records
	$query = "select a.application_id, a.created_date, a.session_id, a.application_type, s.session_info, s.compression
				from application a, `$session_table` s
				where a.session_id = s.session_id
						and a.created_date between '$date_start' and '$date_end'
						and a.application_type = 'VISITOR'";
	$result = $db->Query (DB_OLP, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
	Error_2::Error_Test ($result, TRUE);

	$total_found = $db->Row_Count($result);

	$count = 0;

	$insert_start = "
insert into application
(application_id, session_id, site_name, date_created, date_modified, first_name, middle_name, last_name,
	address1, address2, city, state, zip, home_phone, cell_phone, fax_phone, work_phone, work_ext,
	employer, email, assigned_csr)
values\n";

    $insert = $insert_start;
	print("\nStarting $session_table. Total: $total_found ...");

	while ($session = $db->Fetch_Object_Row ($result))
	{

		switch($session->compression)
		{
			case "gz":
				$info = gzuncompress($session->session_info);
				break;

			case "bz":
				$info = bzdecompress($session->session_info);
				break;

			default:
				$info = $session->session_info;
				break;
		}
		
		$_SESSION = array();
		session_decode ($info);
		$data = $_SESSION;
		$config = $data['config'];

		//print_r($data);

		//only call those who we have at least one number for
		if ((isset($data['data']['phone_home']) && trim($data['data']['phone_home']) != "" ||
			 isset($data['data']['phone_work']) && trim($data['data']['phone_work']) != "" ||
			 isset($data['data']['phone_cell']) && trim($data['data']['phone_cell']) != "")
			 && $config->site_type != "soap")
		{
			$created_date = "'". addslashes($session->created_date) ."'";
			$site_name = isset($config->site_name) ? "'".addslashes($config->site_name)."'" : 'NULL';
			$unique_id = isset($data['unique_id']) ? "'". addslashes($data['unique_id']) ."'" : "'". addslashes($session->session_id) ."'";
			$first_name = isset($data['data']['name_first']) ? "'". addslashes($data['data']['name_first']) ."'" : 'NULL';
			$middle_name = isset($data['data']['name_middle']) ? "'". addslashes($data['data']['name_middle']) ."'" : 'NULL';
			$last_name = isset($data['data']['name_last']) ? "'". addslashes($data['data']['name_last']) ."'" : 'NULL';
			$email = isset($data['data']['email_primary']) ? "'". addslashes($data['data']['email_primary']) ."'" : 'NULL';
			$phone_home = "'". addslashes($data['data']['phone_home']) ."'";
			$phone_cell = isset($data['data']['phone_cell']) ? "'". addslashes($data['data']['phone_cell']) ."'" : 'NULL';
			$phone_fax = isset($data['data']['phone_fax']) ? "'". addslashes($data['data']['phone_fax']) ."'" : 'NULL';
			$phone_work = isset($data['data']['phone_work']) ? "'". addslashes($data['data']['phone_work']) ."'" : 'NULL';
			$ssn = isset($data['data']['social_security_number']) ? "'". addslashes($data['data']['social_security_number']) ."'" : 'NULL';

			$home_street = isset($data['data']['home_street']) ? "'". addslashes($data['data']['home_street']) ."'" : 'NULL';
			$home_unit = isset($data['data']['home_unit']) ? "'". addslashes($data['data']['home_unit']) ."'" : 'NULL';
			$home_city = isset($data['data']['home_city']) ? "'". addslashes($data['data']['home_city']) ."'" : 'NULL';
			$home_state = isset($data['data']['home_state']) ? "'". addslashes($data['data']['home_state']) ."'" : 'NULL';
			$home_zip = isset($data['data']['home_zip']) ? "'". addslashes($data['data']['home_zip']) ."'" : 'NULL';

			$employer_name = isset($data['data']['employer_name']) ? "'".$data['data']['employer_name']."'" : 'NULL';

			$application_id = is_numeric($data['application_id']) ? "'".$data['application_id']."'" : "'".$session->application_id."'";

			$insert_add = "({$application_id}, {$unique_id}, {$site_name}, {$created_date}, {$created_date}, {$first_name}, {$middle_name},
							{$last_name}, {$home_street}, {$home_unit}, {$home_city}, {$home_state}, {$home_zip}, {$phone_home}, {$phone_cell},
							{$phone_fax}, {$phone_work}, {$ssn}, {$employer_name}, {$email}, NULL)\n";

		    $count++;
			$insert .= $insert_add;

			$insert = substr($insert, 0, -1);
			$new_result = $db->Query (DB_CHASECALL, $insert, Debug_1::Trace_Code (__FILE__, __LINE__));
			print("\t$count");
			Error_2::Error_Test ($new_result, TRUE);
			$insert = $insert_start;
		}
		else
		{
			//no phone number, skip the record
			//print "No phone in session: {$session->session_id}\n";
		}
	}

    print "\nEnd count for $session_table: $count\n";

    //delete drops
    //$delete = "delete from chasecall where chase_type = '24HR_DROP' and created_date < {$beg_of_day}";
	//$result = $sql->Query ($chasecall_database, $delete, Debug_1::Trace_Code (__FILE__, __LINE__));
	//Error_2::Error_Test ($result, TRUE);

    //delete opt-ins
    //$delete = "delete from chasecall where ole_partial = 1 and viewed = 1 and created_date < {$opt_in_expire}";
	//$result = $sql->Query ($chasecall_database, $delete, Debug_1::Trace_Code (__FILE__, __LINE__));
	//Error_2::Error_Test ($result, TRUE);

}

function Delete_Old_Records($db)
{
	$delete_query = "delete from application where date_created < date_sub(now(), interval 31 day)";
	$db->Query(DB_CHASECALL, $delete_query, Debug_1::Trace_Code (__FILE__, __LINE__));
}

 

//main procedure

// Build the session handling object
$session_plop = new Null_Session_1 ();
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


$start = strtotime ("-90 minutes");
$end = strtotime("-1 hour");


$mysql_start = date("YmdHi01", $start);
$mysql_end = date("YmdHi00", $end);

@session_start();

// Build the sql object
$sql = new MySQL_3 ();

// Try the connection
//print "Connecting...";
$result = $sql->Connect ("BOTH", HOST, USER, PASS, Debug_1::Trace_Code (__FILE__, __LINE__));
Error_2::Error_Test ($result, TRUE);

$table_ext = array (0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 'a', 'b', 'c', 'd', 'e', 'f');
foreach($table_ext as $ext)
{
	$table = "session_" . $ext;
	Scrub_Sessions($sql, $table, $mysql_start, $mysql_end);
}

Delete_Old_Records($sql);

?>
