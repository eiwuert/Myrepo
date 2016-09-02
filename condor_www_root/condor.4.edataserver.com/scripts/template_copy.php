<?php

/**
 * Copies template(s) from one company to another.
 * Usage:
 * 	php template_copy.php <source_name_short> <source_mode> <target_name_short> <target_mode> [<template name>]
 * Defaults to copy all templates from source name on the source_mdoe server, 
 * to target_name on the target_mode server. You can optionally provide a document name
 * to copy only one document. It also deactivates any template that has the same 
 * name as the one being copied on the targets account.
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
	echo basename(__FILE__)." source_company_short source_mode destination_company_short destination_mode name\n";
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
	die("Invalid source mode. Possible: ".join(',',$valid_modes)."\n");
}
if(!in_array($destination_mode,$valid_modes)) 
{
	die("Invalid destination mode. Possible: ".join(',',$valid_modes)."\n");
}
/*
if(empty($argv[5]))
{
	showUsage();
	die();
}
*/
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
	die("Could not find agent for source company\n");
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
	die("Could not find agent for destination company\n");
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
		company_id = $source_company_id
	AND
		status = 'ACTIVE'
";
if(!empty($argv[5]))
{
	$query .= 'AND name=\''.$argv[5].'\'';
}
$source_results = $source_db->Query($query);
while($src_tpl = $source_results->Fetch_Object_Row())
{
	$s_subject = $destination_db->Escape_String($src_tpl->subject);
	$s_data = $destination_db->Escape_String($src_tpl->data);
	$s_content_type = $destination_db->Escape_String($src_tpl->content_type);
	$s_name = $destination_db->Escape_String($src_tpl->name);
	
	$destination_db->Query("UPDATE template SET status='INACTIVE' WHERE name='$s_name' AND company_id='$destination_company_id';");
	$insert = "
		INSERT INTO
			template
		SET
			date_created=NOW(),
			date_modified=NOW(),
			status='ACTIVE',
			company_id = $destination_company_id,
			user_id = $destination_user_id,
			data = '$s_data',
			subject = '$s_subject',
			content_type = '$s_content_type',
			name = '$s_name',
			type = '{$src_tpl->type}'
	";
	echo("Copying '$src_tpl->name}' to target.\n");
	$destination_db->Query($insert);
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

