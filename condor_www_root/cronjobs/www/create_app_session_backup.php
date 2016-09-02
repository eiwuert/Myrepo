<?PHP

require_once("/virtualhosts/edataserver.com/nms.1/live/include/code/olp_db.mysql.class.php");
require_once ("/virtualhosts/lib/security.3.php");
require_once("/virtualhosts/lib/mysql.3.php");

// Let it run forever
set_time_limit (0);

// MYSQL Connection information

define ("HOST", "selsds001");
define ("USER", "sellingsource");
define ("PASS", "%selling\$_db");
/*
define ("HOST", "selsds001");
define ("USER", "root");
define ("PASS", "");
*/
define ("SESSION_DB", "session_backup");

define("FATAL_DEBUG", TRUE);		// only for local development

// Build the sql object
$mysql = new MySQL_3 ();

// Try the connection
$result = $mysql->Connect ("BOTH", HOST, USER, PASS, Debug_1::Trace_Code (__FILE__, __LINE__));
Error_2::Error_Test ($result, TRUE);

$olp_db = new OLP_DB($mysql);

// get all db2 records
//$query = "SELECT * FROM session WHERE date_modified like '".date("Ymd", strtotime("-2 day"))."%'";
$query = "SELECT * FROM session";	// debug only
$result = $mysql->Query(SESSION_DB, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
Error_2::Error_Test($result_id, TRUE);

// start the session
session_start();
$x = 0;
$test = 0;
$app_ids = array();
while( $row = $mysql->Fetch_Object_Row($result) ) 
{
	$_SESSION = array();
	session_decode($row->session_info);
	
	// as long as we don't have a null session
	// and we have some account information
	if( $_SESSION["data"]["unique_id"] != "" && $_SESSION["data"]["email_primary"] )
	{	
		// decide what database to use
		switch( strtoupper($row->site) ) 
		{
			case "D1":
				$database = "olp_d1_visitor";
			break;
			case "PCL":
				$database = "olp_pcl_visitor";
			break;
			case "UCL":
				$database = "olp_ucl_visitor";
			break;
			case "UFC":
				$database = "olp_ufc_visitor";
			break;
			case "CA":
				$database = "olp_ca_visitor";
			break;
			
			default:
				continue;
			break;
		}	
		
		// unset the application_id
		unset($_SESSION["application_id"]);
	
		// create new security record for session
		$_SESSION["security"] = new Security_3($mysql, $database, "account");
		
		// need to run check_accoutn for all application
		$array = $olp_db->Check_Account( $_SESSION, $_SESSION["data"]["unique_id"], $database );
				
		// check to see if we should insert this into mysql
		if( isset($_SESSION["data"]["app_completed"]) && $_SESSION["data"]["app_completed"] )
		{
			$_SESSION['application_id'] = $olp_db->Insert_Customer($_SESSION, $database);
			
			// run PAPERLESS specific funtion
			if( strtoupper($row->site) == "PCL" || strtoupper($row->site) ==  "UCL" ) 
			{
				$tmp = $olp_db->Paperless_Inserts($_SESSION["application_id"], $database);
			}
			
			//echo "<pre>".$_SESSION["application_id"]." -- good<br>";
			$x++;
		}
		
		$app_ids[] = $_SESSION["application_id"];
	}
}


// erase the session information from 3 days ago
$query = "DELETE FROM session where date_modified like '".date("Ymd", strtotime("-3 day"))."%'";
$result = $mysql->Query(SESSION_DB, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
Error_2::Error_Test($result_id, TRUE);

//echo "<pre>Completed: $x<br><br>";print_r($app_ids);echo "</pre><br><br><br>";
//echo serialize($app_ids);
?>