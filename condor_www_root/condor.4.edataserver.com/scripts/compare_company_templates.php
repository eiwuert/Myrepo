<?php

/**
 * Compares the templates of one company to the templates of another
 * Usage:
 * 	php template_copy.php <source_name_short> <source_mode> <target_name_short> <target_mode>
 */

require('mysqli.1.php');
require('mysql_pool.php');
//MySQL_Pool::Define('condor_rc',   'db101.ept.tss', 'condor', 'andean', 'condor', 3313);
//MySQL_Pool::Define('condor_live', 'writer.condor2.ept.tss', 'condor', 'flyaway', 'condor', 3308);


MySQL_Pool::Define('condor_local',  'localhost', 'root','','condor',3306);
MySQL_Pool::Define('condor_dev',    'localhost', 'root','','condor',3306);
MySQL_Pool::Define('condor_rc',     'db101.ept.tss', 'condor', 'andean', 'condor', 3313);
MySQL_Pool::Define('condor_live',   'writer.condor2.ept.tss', 'condor', 'flyaway', 'condor', 3308);
MySQL_Pool::Define('condor_demo',   'ps39.ept.tss', 'ecash', 'eca$hdem0', 'condor', 3306);


function showUsage()
{
	echo basename(__FILE__)." company1_short company1_mode company2_short company2_mode [--diff_only]\n";
}

if(empty($argv[1]) || empty($argv[2]) || empty($argv[3]) || empty($argv[4]))
{
	showUsage();
	exit;
}
$valid_modes = array('local','rc','live','dev','demo');
$source_mode = strtolower($argv[2]);
$destination_mode = strtolower($argv[4]);
if(!in_array($source_mode,$valid_modes))
{
	die("Invalid Company 1 mode. Possible: ".join(',',$valid_modes)."\n");
}
if(!in_array($destination_mode,$valid_modes)) 
{
	die("Invalid Company 2 mode. Possible: ".join(',',$valid_modes)."\n");
}

$source_name = $argv[1];
$destination_name = $argv[3];

$source_db = MySQL_Pool::Connect('condor_'.$source_mode);
$destination_db = MySQL_Pool::Connect('condor_'.$destination_mode);

$ret = Find_User_And_Company_Id($source_db,$source_name);

if(is_array($ret))
{
	list($source_company_id,$source_user_id) = $ret;
}
elseif($ret == -1)
{
	die("Could not find company with $source_name\n");
}
elseif($ret == -2)
{
	die("Could not find agent for company 1\n");
}

$ret = Find_User_And_Company_Id($destination_db,$destination_name);

if(is_array($ret))
{
	list($destination_company_id,$destination_user_id) = $ret;
}
elseif($ret == -1)
{
	die("Could not find company with $destination_name\n");
}
elseif($ret == -2)
{
	die("Could not find agent for company 2\n");
}

echo "\n****** Comparing " . strtoupper($source_name) . " " . strtoupper($source_mode) . " to " . strtoupper($destination_name) . " " . strtoupper($destination_mode) ." ******\n";

$query = "
	SELECT
		name,
		subject,
		data,
		content_type,
		type
	FROM
		template
	WHERE
		company_id = $source_company_id
	AND
		status = 'ACTIVE'
";

$source_results = $source_db->Query($query);
$company_1_templates = array();
while($src_tpl = $source_results->Fetch_Object_Row())
{
	$s_subject = $destination_db->Escape_String($src_tpl->subject);
	$s_data = $destination_db->Escape_String($src_tpl->data);
	$s_content_type = $destination_db->Escape_String($src_tpl->content_type);
	$s_name = $destination_db->Escape_String($src_tpl->name);
	
	$company_1_templates[$s_name] = $s_data;
}

$query = "
	SELECT
		name,
		subject,
		data,
		content_type,
		type
	FROM
		template
	WHERE
		company_id = $destination_company_id
	AND
		status = 'ACTIVE'
";

$dest_results = $destination_db->Query($query);
$company_2_templates = array();
while($src_tpl = $dest_results->Fetch_Object_Row())
{
	$s_subject = $destination_db->Escape_String($src_tpl->subject);
	$s_data = $destination_db->Escape_String($src_tpl->data);
	$s_content_type = $destination_db->Escape_String($src_tpl->content_type);
	$s_name = $destination_db->Escape_String($src_tpl->name);
	
	$company_2_templates[$s_name] = $s_data;
}

foreach($company_1_templates as $name => $data)
{
	$status = strcmp($company_2_templates[$name], $data);
	if($argv[5] == '--diff_only')
	{
		if(strcmp($company_2_templates[$name], $data) <> 0){
			$results[$name] = color('DIFF', '1;5;31');
		}
	}else{
		$results[$name] = (strcmp($company_2_templates[$name], $data) == 0) ? color('SAME', '32') : color('DIFF', '1;5;31');	
	}
	
}
ksort($results);

foreach($results as $name => $result)
{
	echo str_pad(substr($name,0,39), 40, '-') . $result . "\n";
}

echo "********************************************\n";

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

function color($text, $num)
{
	return "\x1B[{$num}m{$text}\x1B[0m";
}

