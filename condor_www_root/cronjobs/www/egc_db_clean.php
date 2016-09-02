<?PHP
//This cronjob is designed to remove the test applications entered throughout the day

	$outside_web_space = realpath ("../")."/";
	$inside_web_space = realpath ("./")."/";
	define ("OUTSIDE_WEB_SPACE", $outside_web_space);
	define ("DATABASE", "expressgoldcard");

	require_once ("/virtualhosts/lib/debug.1.php");
	require_once ("/virtualhosts/lib/error.2.php");
	require_once ("/virtualhosts/lib/mysql.3.php");

        /*	
	$server = new stdClass ();
	$server->host = "read1.ds04.tss";
	$server->user = 'root';
	$server->pass = '';
	*/
	$server->host = "selsds001";
	$server->user = "sellingsource";
	$server->pass = "%selling\$_db";
	
	// Create sql connection(s)
	$sql = new MySQL_3 ();
	$result = $sql->Connect (NULL, $server->host, $server->user, $server->pass, Debug_1::Trace_Code (__FILE__, __LINE__));
	
	// Get the cc_numbers of the records to remove
	$query = "SELECT cc_number FROM `customer` WHERE  last_name = 'Higarashi' AND first_name = 'Kagome'";
	$result = $sql->Query (DATABASE, $query, Debug_1::Trace_Code (__FILE__, __LINE__));

	while (FALSE !== ($row_data = $sql->Fetch_Object_Row ($result)))
	{
		$acct = $row_data->cc_number;
		$record->{$acct}=$row_data;
		$loop = 1;
	}
	
	if($loop)
	{
		foreach($record AS $key)
		{
			$kill_list .= "'".$key->cc_number."', ";	
		}
	}
	

	// Get the transaction id's of the records to remove
	$query = "SELECT transaction_id FROM `transaction_0` WHERE cc_number IN(".substr($kill_list,0,-2).")";

	$result = $sql->Query (DATABASE, $query, Debug_1::Trace_Code (__FILE__, __LINE__));

	while (FALSE !== ($row_data = $sql->Fetch_Object_Row ($result)))
	{
		$acct = $row_data->transaction_id;
		$trans->{$acct}=$row_data;
		
		$pass = 1;
	}
	
	if($pass)
	{
		foreach($trans AS $key)
		{
			$trans_kill_list .= "'".$key->transaction_id."', ";	
		}
	}
	
		
	$kill_list = substr($kill_list,0,-2);
	
	
	$query ="DELETE FROM `account` WHERE cc_number IN(".$kill_list.")";
	$sql->Query (DATABASE, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
	
	$query = "DELETE FROM `customer` WHERE cc_number IN(".$kill_list.")";
	$sql->Query (DATABASE, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
	 
	$query = "DELETE FROM `account_status` WHERE cc_number IN(".$kill_list.")";
	$sql->Query (DATABASE, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
	
	$query = "DELETE FROM `certificates` WHERE cc_number IN(".$kill_list.")";
	$sql->Query (DATABASE, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
	
	$query = "DELETE FROM `comments` WHERE cc_number IN(".$kill_list.")";
	$sql->Query (DATABASE, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
	
	$query = "DELETE FROM `orders` WHERE cc_number IN(".$kill_list.")";
	$sql->Query (DATABASE, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
	
	$query = "DELETE FROM `transaction_0` WHERE cc_number IN(".$kill_list.")";
	$sql->Query (DATABASE, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
	
	$query = "DELETE FROM `transaction_line_item` WHERE rel_transaction_id IN(".$trans_kill_list.")";
	$sql->Query (DATABASE, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
	
	$query = "DELETE FROM `audit_trail` WHERE cc_number IN(".$kill_list.")";
	$sql->Query (DATABASE, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
	
	$query = "DELETE FROM `session` WHERE created_date <= ".date('Ymdhis')."";
	$sql->Query (DATABASE, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
	
	// Must optimize tables else delteing records is useless.
	$query = "optimize table `account`, `customer`, `account_status`, `certificates`, `comments`, `orders`, `transaction_0`, `transaction_line_item`, `audit_trail`, `session`";
	$sql->Query (DATABASE, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
	

?>
