<?php

# 6/05 RSK
# major code cleanup/rewrite , going back 14 days and working with that set of data to purge and push
# to olp_bb_partial database

# 3/07 [VT]
# Changed code to use mysql.4.php which inherently has debug logging and tracing built in.
# mysql.3 which was used previous to this was causing major memory leaks and fatal errors.
# Tables are now processed ($chunksize) rows at a time to lesson query load on server.
# Changes also made to queries in session_partial_insert.php
# functionality and queries were kept the same.


$debug = FALSE;
//$debug = TRUE;

ini_set ('magic_quotes_runtime', 0);

include_once("session_partial_insert.php");

// Let it run forever
set_time_limit (0);

// Database connectivity

require_once('/virtualhosts/bfw.1.edataserver.com/include/code/server.php');
require_once('mysql.4.php');
require_once('mysqli.1.php');

$server = Server::Get_Server('LIVE','BLACKBOX');
$sql = new MySQL_4($server['host'], $server['user'], $server['password'],FALSE);
$sql->Connect();


$table_ext = array ('0','1','2','3','4','5','6','7','8','9','a','b','c','d','e','f');

$dbsets = array( array("visitor_db"=> $server['db'], "visitor_partial_db"=> $server['db']."_bb_partial"));

//$dbsets = array( array("visitor_db"=>VISITOR_DB, "visitor_partial_db"=>VISITOR_PARTIAL_DB), array("visitor_db"=>OLD_VISITOR_DB, "visitor_partial_db"=>OLD_VISITOR_PARTIAL_DB));

// just olp_bb_visitor
//$dbsets = array( array("visitor_db"=>OLD_VISITOR_DB, "visitor_partial_db"=>OLD_VISITOR_PARTIAL_DB));

$cutoff_date = date("Y-m-d H:i:s", strtotime("-14 days"));

// variables used to split up tables into smaller processes
$chunk_size = 2000;
$chunk_count = 0;
$chunk_offset = 0;

foreach ($dbsets as $dbset) {

	$visitor_db = $dbset["visitor_db"];
	$partial_db = $dbset["visitor_partial_db"];

	foreach ($table_ext as $ext)
	{
		$table = "session_".$ext;

		echo "Database: $visitor_db Table: $table\n";

		// grab apps first
		echo "Cutoff date: $cutoff_date\n";

		$chunk_count = 0;
		while(isset($chunk_count)){

			// Increment offset and continue processing
			$chunk_offset = $chunk_count * $chunk_size;
			$chunk_count++;

			echo "Memory Usage: ".memory_get_usage()."\n";
			$query = "
				SELECT
					session_id,
					session_info,
					compression
				FROM
					{$table}
				WHERE
					date_modified < '$cutoff_date'
				LIMIT $chunk_offset,$chunk_size";

			$result = $sql->Query ($visitor_db, $query);

			// Check to see if session_x table is completely processed
			if($rowcount = $sql->Row_Count($result))
				echo "Rows To Process: $rowcount\n";
			else
			{
				unset($chunk_count);
			}


			// zero out counters
			$count = 0;
			$complete =0;

			while ($session = $sql->Fetch_Object_Row ($result))
			{

				unset($data);
				unset($email);
				$session_id = $session->session_id;

				switch ($session->compression)
				{
					case 'gz':
						$session_info = gzuncompress ($session->session_info);
						break;
					case 'bz':
						$session_info = bzdecompress ($session->session_info);
						break;
					case 'none':
						$session_info = $session->session_info;
						break;
				}

				// initialize session and copy session into $data
				@session_start();
				session_decode($session_info);
				$data = $_SESSION;
				@session_destroy();

				$email = strtoupper($data["data"]["email_primary"]);

				// make sure none of our test emails slip through
				// don't send over customers who confirmed and have the cs array in their session data
				// don't send over customers who are denied (we dont want failed apps in the partials database [RL] 01/12/2006)
				if(count($data['cs']) || !$email || $data["app_completed"] ||
					$data['blackbox']['denied'] || strpos($email, 'TSSMASTERD') ||
					strpos($email, 'SELLINGSOURCE') )
				{
					if (isset($data["app_completed"]))
					{
						$complete++;
					}
					else if($data['blackbox']['denied'])
					{
						$failed++;
					}
				}
				else
				{
					// push them to the partial table and delete from session
					// set to insert into campaign_info next
					$data['date_modified'] = $session->date_modified;
					if (!$debug)
					{
						Partial_Insert_Database($sql, 'bb', $partial_db, $data);
					}
				}
			}
			
			/*
				Mantis #10674 - We no longer delete sessions as part of the scrubber. Sessions will
				be automatically deleted with PHP session garbage collection. [BF]
			*/

			echo $complete . " records were completed apps.\n";
			echo $failed . " records were failed apps.\n";
			echo "\n\n";
			flush(); ob_flush();
		}
	}

}

// until 4.1.1, OPTIMIZE TABLE commands don't replicate!
//exec('./session_optimize.php > /dev/null &');

?>
