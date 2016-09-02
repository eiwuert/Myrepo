<?php

/**
 * Clears the template cache for a given company and mode
 * Usage:
 * 	php template_copy.php <name_short> <mode>
 * Example:
 *  php template_cache_clear.php nsc rc
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
	echo basename(__FILE__)." company_short company_mode\n";
}

if(empty($argv[1]) || empty($argv[2]))
{
	showUsage();
	exit;
}

$valid_modes = array('rc','live');

$company_short    = strtolower($argv[1]);
$mode             = strtolower($argv[2]);

if(!in_array($mode,$valid_modes))
{
	die("Invalid mode. Possible: ".join(',',$valid_modes)."\n");
}

$db = MySQL_Pool::Connect('condor_'.$mode);

$ret = Find_User_And_Company_Id($db,$company_short);

if(is_array($ret))
{
	list($company_id,$user_id) = $ret;
}
elseif($ret == -1)
{
	die("Could not find company: $company_short\n");
}
elseif($ret == -2)
{
	die("Could not find agent for company: $company_short\n");
}

$api_auth = Find_API_Auth($db, $company_short);

switch(strtolower($mode))
{
	case 'rc':
		$url = 'rc.condor.4.edataserver.com/condor_api.php';
		break;
	case 'live':
		$url = 'condor.4.edataserver.com/condor_api.php';
		break;
}

$condor_string = "prpc://{$api_auth}@{$url}";

$condor = new PRPC_Client($condor_string);

$condor->Clear_All_Cache();

function Find_API_Auth($db, $name_short)
{
	$s_name = $db->Escape_String($name_short);

	$query = "
		SELECT
			api_auth,
			name_short
		FROM
			condor_admin.company
		WHERE
			condor_admin.company.name_short = '{$s_name}'
	";

	$res = $db->Query($query);

	if (!($row = $res->Fetch_Object_Row()))
	{
		die("Invalid Name Short, this should never happen");
	}

	$api_auth = Security::Decrypt($row->api_auth,md5($row->name_short));

	return $api_auth;
}

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

