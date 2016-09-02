<?php
// classes
class serialize_db2{
	function unserialize_data( $serialized_string ) 
	{
	   $variables = array(  );
	   $a = preg_split( "/(\w+)\|/", $serialized_string, -1, PREG_SPLIT_NO_EMPTY|PREG_SPLIT_DELIM_CAPTURE );
	   for( $i = 0; $i < count( $a ); $i = $i+2 ) 
	   {
	       $variables[$a[$i]] = unserialize( $a[$i+1] );
	   }
	   return( $variables );
	}
}

class olp_insert{
	function __construct($db2,$sql,$session_id=NULL,$session_property=NULL,$session_info=NULL)
	{
		$this->db2 = $db2;
		$this->sql = $sql;
		if (isset($session_id) && isset($session_property) && isset($session_info))
		{
			$this->session_id = $session_id;
			$this->property = strtolower($session_property);
			$this->partial_db = "olp_" . $this->property . "_partial";
			$this->session_info = $session_info;
			olp_insert::run_insert();
		}
		elseif(isset($session_id))
		{
			$this->session_id = $session_id;
			olp_insert::delete_row();
		}
		else 
		{
			return FALSE;
		}
	}

	function run_insert()
	{
		@session_start();
		$_SESSION = $this->session_info;
		// check MySQL connection
		$result = $this->sql->Connect ("$this->partial_db", MySQLHOST, MySQLUSER, MySQLPASS, Debug_1::Trace_Code (__FILE__, __LINE__));
		Error_2::Error_Test($result,TRUE);
		// get application id
		if (!$_SESSION['application_id'])
		{
			$query = "INSERT INTO application (application_id,session_id) VALUES('','$olp_session_id')";
			$result = $this->sql->Query ($this->partial_db, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
			$_SESSION['application_id'] = $this->sql->Insert_Id();
		}
		
		// matching values.. 
		$_SESSION['data']['account_number'] = $_SESSION['data']['bank_account'];
		$_SESSION['data']['routing_number'] = $_SESSION['data']['bank_aba'];
		$_SESSION['data']['direct_deposit'] = $_SESSION['data']['income_direct_deposit'];
		$_SESSION["data"]["employment"] = $_SESSION['data']['employer_name'];
		$_SESSION["data"]["date_of_hire"] = $_SESSION['data']['date_hire'];
		$_SESSION["loan_note"]["fund_date"] = $_SESSION['data']['fund_date'];
		
		// insert into partial mysql
		include_once("olp_partial_insert.php");
		Partial_Insert_Database($this->sql,$this->property);
		olp_insert::delete_row();
	}
	
	function delete_row()
	{
		$query = "delete from session where session_id='" . $this->session_id . "'";
//		echo "$query\n";
		$del_row = $this->db2->Execute($query);
	}
}
// end classes
////////////////////////////////////////////////////////////////////////////////////////////////////////////
	ini_set('unserialize_callback_func', '');
	ini_set ('magic_quotes_runtime', 0);
	ini_set ('implicit_flush', 1);
	ini_set ('output_buffering', 0);
	ob_implicit_flush ();
	list ($ss, $sm) = explode (" ", microtime ());
	// Make sure we keep running even if user aborts
	ignore_user_abort (TRUE);

	// Let it run forever
	set_time_limit (0);

	// Database connectivity
	require_once("/virtualhosts/lib/mysql.3.php");
	require_once ("/virtualhosts/lib/db2.2.php");
	require_once ("/virtualhosts/lib/error.2.php");
	include_once ("/virtualhosts/lib/null_session.1.php");

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


	// Connection information
	define ("HOST", "ds23.tss");
	define ("USER", "web_d1");
	define ("PASS", "db2");
	define ("DBNAME","olp");
	define ("MySQLHOST" , "localhost");
	define ("MySQLUSER" , "root");
	define ("MySQLPASS" , "");
	
	// Build the db2 & sql object
	$sql = new MySQL_3();
	$db2 = new Db2_2(DBNAME,USER,PASS);
	Error_2::Error_Test ($db2->Connect (), TRUE);

	$query = 'select min(date_modified) as mindate from session';
	$sql_select = $db2->Query($query);
		Error_2::Error_Test($sql_select,TRUE);
	$db2_select = $db2->Execute($query);
		Error_2::Error_Test ($db2_select, TRUE);
	$session = $db2_select->Fetch_Object ();

	$strday = $session->MINDATE;
	$stamp = mktime(0,0,0,substr($strday,5,2),substr($strday,8,2),substr($strday,0,4));

	$query = "select session_info,session_id,date_modified,date_created
					from session
					where date_modified between '" .date("Y-m-d-H.i.s",$stamp) .
				"' and '" .date("Y-m-d-H.i.s", strtotime("+1 day", $stamp))  ."'
				";

//	$query = "select session_id,session_info from session where session_id='08caae67b4e49c4d74ec8761a9442b67'";
//	$query = "select session_id,session_info from session fetch first 100 row only";
	$sql_select = $db2->Query($query);
	Error_2::Error_Test($sql_select,TRUE);
	$db2_select = $db2->Execute($query);

	while($data = $db2_select->Fetch_Object($count_row))
	{
		$countrow = $countrow+1;
		$session_data = array();
		$sesinfo = new serialize_db2();
		$session_data = $sesinfo->unserialize_data($data->SESSION_INFO);
		if ($session_data['data']['app_completed']||!$session_data['data']['email_primary'])
		{
			$do_delete = new olp_insert($db2,$sql,$data->SESSION_ID);
			$complete = $complete+1;
		}
		else
		{
			$do_insert = new olp_insert($db2,$sql,$data->SESSION_ID,$session_data['config']->property_short,$session_data);
			$notcomplete_count = $notcomplete_count+1;
		}
	}
/*
echo "### " . date("Y-m-d",$stamp) . " total session found = $countrow\n";
echo "### total complete/no email delete from session = $complete\n";
echo "### total not complete/ insert to partial = $notcomplete_count\n";
*/
exit;
?>