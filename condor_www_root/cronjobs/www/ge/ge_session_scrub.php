<?php

// Make sure we keep running even if user aborts
ignore_user_abort (TRUE);

// Let it run forever
set_time_limit (0);

// Database connectivity
require_once ("mysql.3.php");
require_once ("error.2.php");
require_once ("null_session.1.php");

//functions
function Adjust_Phone_Numbers($sql, $database, $phone_map)
{
	//echo "Modifying phone array\n";
	//print_r($phone_map);
	$phone_string = "(" . implode(",", array_keys($phone_map)) . ")";
	$query = "select phone from `partial` where phone in {$phone_string}";
	//echo $query . "\n";
    $result = $sql->Query ($database, $query, Debug_1::Trace_Code (__FILE__, __LINE__));

	while($row = $sql->Fetch_Object_Row ($result))
	{
		//echo "Removing number: {$row->phone}\n";
		unset($phone_map["'".$row->phone."'"]);
	}
	//echo "Done modifying phone array\n";
	//print_r($phone_map);
	return $phone_map;
}

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

@session_start();

$mode = strtoupper($argv[1]);

$databases = array('perfectgetawaymembership_com', 'criticschoicemembership_com');

// Connection information
switch($mode)
{
//RC
 case "RC":
	 define ("HOST", "ds001.ibm.tss"); //selsds001
	 define ("USER", "admin");
	 define ("PASS", "%selling\$_db");
	 foreach($databases as $key => $value)
	 {
		 $databases[$key] = "rc_" . $value;
	 }
	 break;

//LIVE
 case "LIVE":
	 define ("HOST", "selsds001");
	 define ("USER", "admin");
	 define ("PASS", "%selling\$_db");
	 break;

//TEST
 case "LOCAL":
 default:
	 define ("HOST", "localhost");
	 define ("USER", "root");
	 define ("PASS", "");
	 break;
}

// Build the sql object
$sql = new MySQL_3 ();

// Try the connection
//echo "Connecting...";
$result = $sql->Connect ("BOTH", HOST, USER, PASS, Debug_1::Trace_Code (__FILE__, __LINE__));
Error_2::Error_Test ($result, TRUE);
//echo "Done\n";

$start = strtotime ("-90 minutes");
$end = strtotime("-1 hour");

$mysql_start = date("YmdHi01", $start);
$mysql_end = date("YmdHi00", $end);

$commit_count = 1000;

foreach($databases as $database)
{
	// Pull the user information
	$query = "select * from `session` where created_date between '".$mysql_start."' AND '".$mysql_end."'";
	//echo "$query\n";
	$result = $sql->Query ($database, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
	Error_2::Error_Test ($result, TRUE);

	$total_found = $sql->Row_Count($result);
	//echo "Count: {$total_found}\n";
	//die();

	$c = 0;

	$insert_start = "
insert into `partial`
(session_id, created_date, first_name, last_name, email, phone, dob, state)
values\n";

	$insert_map = array();

	while ($session = $sql->Fetch_Object_Row ($result))
	{
		$_SESSION = array();
		session_decode ($session->session_info);
		$data = $_SESSION;
		//print_r($data);

		if(isset($data['phone']) && $data['phone'] != '' && $data['state'] != 'NJ' && $data['action'] != 'join')
	    {
			$config = $data['config'];

			$created_date = "'". addslashes($session->created_date) ."'";
			$fname = isset($data['firstname']) ? "'". addslashes($data['firstname']) ."'" : 'NULL';
			$lname = isset($data['lastname']) ? "'". addslashes($data['lastname']) ."'" : 'NULL';
			$email = isset($data['email1']) ? "'". addslashes($data['email1']) ."'" : 'NULL';
			$phone = "'". addslashes(preg_replace ("/[^\d]/", "", $data['phone'])) ."'";

			$doby = isset($data['birthyear']) ? addslashes($data['birthyear']) : '0000';
			$dobm = isset($data['birthmonth']) ? addslashes($data['birthmonth']) : '00';
			$dobd = isset($data['birthdom']) ? addslashes($data['birthdom']) : '00';

			$dob = $doby . $dobm . $dobd . "000000";
			
			$city = isset($data['city']) ? "'". addslashes($data['city']) ."'" : 'NULL';
			$state = isset($data['state']) ? "'". addslashes($data['state']) ."'" : 'NULL';


			$insert_map[$phone] = "('{$session->session_id}', {$created_date}, {$fname}, {$lname}, {$email}, {$phone}, '{$dob}', {$state})";
			
            if(count($insert_map) % $commit_count == 0)
            {
				$insert_map = Adjust_Phone_Numbers($sql, $database, $insert_map);
				if(count($insert_map))
				{
					$insert = $insert_start . implode(',\n', $insert_map);
		        	$new_result = $sql->Query ($database, $insert, Debug_1::Trace_Code (__FILE__, __LINE__));
		        	Error_2::Error_Test ($new_result, TRUE);
				}
				//echo "Inserted $c\n";
				//echo $insert;
            }
	    }
    }

    //echo "End count: $c\n";
	//print_r($insert_map);

    if(count($insert_map) && (count($insert_map) % $commit_count != 0))
    {
		$insert_map = Adjust_Phone_Numbers($sql, $database, $insert_map);
		if(count($insert_map))
		{
			$insert = $insert_start . implode(',\n', $insert_map);
			$result = $sql->Query ($database, $insert, Debug_1::Trace_Code (__FILE__, __LINE__));
			Error_2::Error_Test ($result, TRUE);
		}
		//echo "Inserted $c\n";
		//echo $insert;
	}

} // end foreach db

?>
