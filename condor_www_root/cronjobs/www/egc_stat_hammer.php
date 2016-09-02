<?PHP
//This is the big hammer for egc stats.  don't run it...

	$outside_web_space = realpath ("../")."/";
	$inside_web_space = realpath ("./")."/";
	define ("OUTSIDE_WEB_SPACE", $outside_web_space);
	define ("DATABASE", "expressgoldcard");

	require_once ("/virtualhosts/lib/debug.1.php");
	require_once ("/virtualhosts/lib/error.2.php");
	require_once ("/virtualhosts/lib/mysql.3.php");
	require_once ("/virtualhosts/lib/crypt.3.php");
	require_once ("/virtualhosts/lib/setstat.1.php");

     /*
	$server = new stdClass ();
	$server->host = "read1.ds04.tss";
	$server->user = 'root';
	$server->pass = '';
     */
	
	$server->host = "read1.iwaynetworks.net";
	$server->user = "sellingsource";
	$server->pass = "%selling\$_db";
     
	// Create sql connection(s)
	$sql = new MySQL_3 ();
	$result = $sql->Connect (NULL, $server->host, $server->user, $server->pass, Debug_1::Trace_Code (__FILE__, __LINE__));

	define ('SQL_BASE', 'egc_stat');

	$tables = $sql->Get_Table_List (SQL_BASE);
	foreach ($tables as $table => $one)
	{
		if (preg_match ('/^stats\d+_\d{4}_\d{2}/', $table))
		{
			$query = 'update '.$table.' set approved = 0, denied = 0';
			$sql->Query (SQL_BASE, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
			echo $query, ".\n";
		}
	}
	echo "\n";


     
	$query = "SELECT customer.promo_id, customer.promo_sub_code, account.account_status, account.cc_number, DATE_FORMAT(account.sign_up, '%Y-%m-%d') AS sign_up FROM `customer`, `account` WHERE
	customer.cc_number = account.cc_number AND account.sign_up < 20030717000001";
    
	$result = $sql->Query (DATABASE, $query, Debug_1::Trace_Code (__FILE__, __LINE__));

	while (FALSE !== ($row_data = $sql->Fetch_Object_Row ($result)))
	{
		$acct = $row_data->cc_number;
		$record->{$acct}=$row_data;
		$loop = 1;
	}

	if($loop)
	{
		foreach($record AS $hit)
		{
			$skip = FALSE;
			$promo_id = $hit->promo_id;
			$promo_sub_code = $hit->promo_sub_code;

			switch($hit->account_status)
			{
				// If these status means you are approved
				case "INACTIVE":
				case "ACTIVE":
				case "COLLECTIONS":
				$column = 'approved';
				break;

				// If these status means you are denied
				case "HOLD":
				case "DENIED":
				case "CANCELLED":
				case "WITHDRAWN":
				$column = 'denied';
				break;

				// Else we ignore you,.. your most likely still pending
				default:
				$skip = TRUE;
				echo $column."\n";
			}

			$promo_status = new stdclass();
			$promo_status->valid = "valid";

			// stat database
			$base = "egc_stat";

			// Add 14 days to the sign-up date.
			$day = strtotime($hit->sign_up);
			$day = strtotime('+14 day', $day);
			$day = date('Y-m-d', $day);

			if($skip)
			{
				NULL;
			}
			else
			{
				$stat_data = Set_Stat_1::_Setup_Stats($day,'1833', '0', '1835', $promo_id, $promo_sub_code, $sql, $base, $promo_status->valid, $batch_id = NULL);
				Set_Stat_1::Set_Stat ($stat_data->block_id, $stat_data->tablename, $sql, $base, $column);
                    echo "hit ".$stat_data->block_id."\n";
                    
                    $query = "UPDATE `account` SET stat_hit = 'Y' WHERE cc_number = '".$hit->cc_number."'";
                    $result = $sql->Query (DATABASE, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
                    echo "update account to 'Y'.\n";
			}
		}
	}
?>
