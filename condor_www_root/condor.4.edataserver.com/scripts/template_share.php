<?php

/**
 * This script shares templates from one company to many
 * Usage:
 * 	php template_share.php <name_short> <mode> <company to share to> [company 2 to share to] ...
 * Example:
 *  php template_share.php nsc rc bgc ezc csg tgc gtc obb cvc
 * The above would share all templates from company 'nsc' on Condor RC
 * with the companies: 'bgc', 'ezc', 'csg', 'tgc', 'gtc', 'obb', and 'cvc'
 */

require_once('mysqli.1.php');
require_once('mysql_pool.php');
require_once('prpc/client.php');
require_once(dirname(__FILE__) . "/../lib/security.php");

MySQL_Pool::Define('condor_local',  'localhost', 'root','','condor',3306);
MySQL_Pool::Define('condor_dev',    'localhost', 'root','','condor',3306);
MySQL_Pool::Define('condor_rc',     'db101.ept.tss', 'condor', 'andean', 'condor', 3313);
MySQL_Pool::Define('condor_live',   'writer.condor2.ept.tss', 'condor', 'flyaway', 'condor', 3308);
MySQL_Pool::Define('condor_demo',   'ps39.ept.tss', 'ecash', 'eca$hdem0', 'condor', 3306);

function showUsage()
{
	echo basename(__FILE__)." company_short company_mode share_company1 [share_company2] [share_company3] ...\n";
}

if(empty($argv[1]) || empty($argv[2]) || empty($argv[3]))
{
	showUsage();
	exit;
}

$valid_modes = array('rc','live');

$source_company_short  = strtolower($argv[1]);
$mode                  = strtolower($argv[2]);

if(!in_array($mode,$valid_modes))
{
	die("Invalid mode. Possible: ".join(',',$valid_modes)."\n");
}

$db = MySQL_Pool::Connect('condor_'.$mode);

$ret = Find_User_And_Company_Id($db,$source_company_short);

if(is_array($ret))
{
	list($source_company_id,$user_id) = $ret;
}
elseif($ret == -1)
{
	die("Could not find company: $source_company_short\n");
}
elseif($ret == -2)
{
	die("Could not find agent for company: $source_company_short\n");
}

for ($i = 3; $i < $argc; $i++)
{
	$ret = Find_User_And_Company_Id($db,$argv[$i]);

	if ($ret == -1)
	{
		die("Could not find company: $argv[$i]\n");
	}
	elseif($ret == -2)
	{
		die("Could not find agent for company: $argv[$i]\n");
	}
	
	list($dest_id, $dest_user_id) = $ret;

	$destination_company_shorts[] = $argv[$i];
	$destination_company_ids[]    = $dest_id;
}

$delete_company_ids = '(' . $source_company_id . ',' . join(',', $destination_company_ids) . ')';

$query = "
	DELETE
	FROM
		shared_template
	WHERE
		company_id IN {$delete_company_ids}
";

$db->Query($query);

$share_company_ids = '(' . join(',', $destination_company_ids) . ')';

$query = "
	INSERT INTO shared_template (company_id, template_id)
	SELECT
		c.company_id,
		t.template_id
	FROM
		condor_admin.company c
	JOIN
		condor.template t ON (t.company_id = {$source_company_id} AND t.status = 'ACTIVE')
	WHERE
		c.company_id IN {$share_company_ids}
";

$db->Query($query);

echo "Script ran successfully\n";
exit;
	
function Find_User_And_Company_Id($db,$name)
{
	$s_name = $db->Escape_String($name);
	$query = "
		SELECT
		company_id
		FROM
		condor_admin.company
		WHERE
		name_short = '$s_name'
		OR
		name = '$s_name'
		";
	$res = $db->Query($query);

	if($row = $res->Fetch_Object_Row())
	{
		$company_id = $row->company_id;
	}
	else
	{
		return -1;
	}
	//Now find a user_id for that
	$query = "
		SELECT
		agent_id
		FROM
		condor_admin.agent
		WHERE
		company_id=$company_id
		AND
		system_id=1
		LIMIT 1;
	";
	$res = $db->Query($query);
	if($row = $res->Fetch_Object_Row())
	{
		$user_id = $row->agent_id;
	}
	else
	{
		return -2;
	}
	return array($company_id,$user_id);
}

