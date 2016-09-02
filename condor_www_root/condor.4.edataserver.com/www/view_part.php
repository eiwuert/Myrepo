<?php
/**
 * This script will output the data for a specific part inside Condor 2.0.
 * 
 * @author Brian Feaver
 * @copyright Copyright 2006 The Selling Source, Inc.
 */

if ($_SERVER['REQUEST_METHOD'] === 'GET')
{
	require_once('automode.1.php');
	require_once('../lib/security.php');
	require_once('mysqli.1.php');
	require_once('mysql_pool.php');
	
	MySQL_Pool::Define('condor_' . MODE_DEV, 'monster.tss', 'condor', 'andean', 'condor', 3311);
	MySQL_Pool::Define('condor_' . MODE_RC, 'db101.ept.tss', 'condor', 'andean', 'condor', 3313);
	MySQL_Pool::Define('condor_' . MODE_LIVE, 'writer.condor2.ept.tss', 'condor', 'flyaway', 'condor', 3308);
	
	$automode = new Auto_Mode();
	$mode = $automode->Fetch_Mode($_SERVER['HTTP_HOST']);
	
	$logged_in = FALSE;
	
	$username = ($_SERVER['PHP_AUTH_USER'] ? $_SERVER['PHP_AUTH_USER'] : FALSE);
	$password = ($_SERVER['PHP_AUTH_PW'] ? $_SERVER['PHP_AUTH_PW'] : FALSE);
	
	if($username && $password)
	{
		$security = new Security($mode);
		$logged_in = $security->Login_User('condorapi', $username, $password);
	}
	
	if($logged_in)
	{
		
		$part_id = isset($_GET['p']) ? $_GET['p'] : FALSE;
		
		if(get_magic_quotes_gpc())
		{
			$part_id = stripslashes($part_id);
		}
		
		if($part_id)
		{
			
			// Setup MySQL connection
			$db  = MySQL_Pool::Get_Definition('condor_' . $mode);
			$sql = MySQL_Pool::Connect('condor_' . $mode);
			
			$part_id = intval($part_id);
			
			$query = "
				SELECT
					part_id,
					content_type,
					file_name,
					compression
				FROM
					part
				WHERE
					part_id = $part_id";
			
			try
			{
				$result = $sql->Query($query);
			}
			catch(Exception $e)
			{
				exit($e->getMessage());
			}
			
			if(($row = $result->Fetch_Object_Row()))
			{
				
				$data = file_get_contents($row->file_name);
				
				if($row->compression == "GZ")
				{
					$data = gzuncompress($data);
				}
				
				$content_type = $row->content_type;
				
			}
			
			header("Content-Type: $content_type");
			print $data;
			
		}
		
	}
	else
	{
		
		header('WWW-Authenticate: Basic realm="Condor"');
		header('HTTP/1.0 401 Unauthorized');
		include('./unauthorized.html');
		
	}
}
	
?>
