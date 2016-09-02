#!/usr/lib/php5/bin/php
<?php
/**
 * Check for unsynced apps
 * 
 * Check for apps that remain unsynced for more than x minutes 
 * 
 * @author Jason Gabriele <jason.gabriele@sellingsource.com>
 * 
 * @version
 * 	    1.0.0 Oct 16, 2006 - Jason Gabriele <jason.gabriele@sellingsource.com>
 */

define('BFW_OLP_DIR', '/virtualhosts/bfw.1.edataserver.com/include/modules/olp/');
define('BFW_CODE_DIR','/virtualhosts/bfw.1.edataserver.com/include/code/');

require_once('mysql.4.php');
require_once(BFW_CODE_DIR.'server.php');
require_once(BFW_CODE_DIR.'setup_db.php');
require_once 'reported_exception.1.php';

$mail_list = "jason.gabriele@sellingsource.com";

//Get the mode
if(isset($argv[1]))
{
	$mode = strtoupper($argv[1]);
}
else
{
	echo "You must pass the mode\n";
	exit(1);
}

//Get Unsynced Apps
$unsynced = Get_Unsynced_Apps($mode);
if(empty($unsynced))
{
	exit(0);
}

//Send Mail
Reported_Exception::Add_Recipient('EMAIL','brian.feaver@sellingsource.com');
Reported_Exception::Add_Recipient('EMAIL','christopher.barmonde@sellingsource.com');
Reported_Exception::Add_Recipient('EMAIL','mike.genatempo@sellingsource.com');
Reported_Exception::Add_Recipient('EMAIL','jeff.fiegel@sellingsource.com');

if($mode == "LIVE")
{
	Reported_Exception::Add_Recipient('SMS','6613191881'); //Brian F
	Reported_Exception::Add_Recipient('SMS','7023540056'); //Mike G
	Reported_Exception::Add_Recipient('SMS','4353130543'); //Chris B
	Reported_Exception::Add_Recipient('SMS','7024160369'); //Jeff Fiegel
}

$e = new Exception("There are " . count($unsynced) . " remaining unsynced in the queue");
Reported_Exception::Report($e);

function Get_Unsynced_Apps($mode)
{
	$unsynced = array();
	
	$olp_sql = Setup_DB::Get_Instance("blackbox", $mode);
	$olp_db = $olp_sql->db_info["db"];
	
	//Get synced status id
	$query = "SELECT application_status_id
			  FROM application_status
			  WHERE name = 'ldb_unsynched'";
		
    $result = $olp_sql->Query($olp_db, $query);
	$u = $olp_sql->Fetch_Array_Row($result);
	
	$query = "
		SELECT
			application_id,
			date_created
		FROM
			status_history
		WHERE
			application_status_id = {$u['application_status_id']}
			AND date_created BETWEEN DATE_SUB(NOW(), INTERVAL 20 MINUTE)
				AND DATE_SUB(NOW(), INTERVAL 5 MINUTE)";
	
	$result = $olp_sql->Query($olp_db, $query);
	
	while(($row = $olp_sql->Fetch_Array_Row($result)))
	{
		$unsynced[] = array('application_id' => $row['application_id'],
					        'date_created'   => $row['date_created']);
	}
	
	return $unsynced;
}
?>
