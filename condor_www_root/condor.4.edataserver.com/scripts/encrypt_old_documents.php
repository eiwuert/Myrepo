<?php
set_include_path(get_include_path().':/virtualhosts');

//Attempt and figure out what path we're in
define('CONDOR_DIR',realpath(dirname(__FILE__).'/../').'/');


require_once(CONDOR_DIR.'lib/config.php');
require_once(CONDOR_DIR.'lib/condor_crypt.php');
define('USAGE',"Use: $argv[0] --mode=RC --startdate=20070201 [--enddate=20070301]");

array_shift($argv);
$continue = false;
/**
 * Loop through and figure out all of our awesome arguments
 */
$test_mode = false;
foreach($argv as $idx => $arg)
{
	if($continue === true)
	{
		$continue = false;
		continue;
	}
	if(($pos = strpos($arg,'-')) !== false)
	{
		$arg_name = ltrim(substr($arg,$pos+1),'-');
		if(strpos($arg_name,'=') !== false)
		{
			list($arg_name, $val) = explode('=',$arg_name);
		}
		else 
		{
			$val = $argv[$idx + 1];
			$continue = true;
		}
	}
	else 
	{
		$arg_name = $arg;
		if(strpos($arg_name,'=') !== false)
		{
			list($arg_name, $val) = explode('=',$arg_name);
		}
		else 
		{
			$val = $argv[$idx + 1];
			$continue = true;
		}
	}
	switch(strtolower($arg_name))
	{
		case 'm':
		case 'mode':
			if(strcasecmp($val,MODE_LIVE) == 0)
			{
				$mode = MODE_LIVE;
			}
			elseif(strcasecmp($val,MODE_DEV) == 0 || strcasecmp($val,'dev') == 0)
			{
				$mode = MODE_DEV;
			}
			elseif(strcasecmp($val,MODE_RC) == 0)
			{
				$mode = MODE_RC;
			}
			else
			{
				die("Invalid mode ({$val})\n".USAGE."\n");
			}
		break;
		case 's':
		case 'sd':
		case 'startdate':
			$start_date = validate_date($val);		
			if($start_date === false)
			{
				die("Invalid date format.\n".USAGE."\n");
			}
		break;
		case 'e':
		case 'ed':
		case 'enddate':
			$end_date = validate_date($val);
			if($end_date === false)
			{
				die("Invalid date format.\n".USAGE."\n");
			}
		break;
		case 'test':
			$test_mode = true;
			$continue = false;
			break;
	}
}
if(!isset($end_date))
{
	$end_date = date('YmdHis');
}
if(!isset($start_date))
{
	die("You must provide a start date.\n".USAGE."\n");
}
if(!isset($mode))
{
	die("You must provide a valid mode.\n".USAGE."\n");
}
//die(" Mode: $mode\n End_date: $end_date\n Start_Date: $start_date\n Test Mode: ".(($test_mode === true) ? 'TRUE' : 'FALSE')."\n");


define('EXECUTION_MODE',$mode);

require_once(CONDOR_DIR.'lib/condor_exception.php');
require_once('libolution/AutoLoad.1.php');
require_once('template_parser.1.php');


function Display($str)
{
        static $old_text;

        if(($len = strlen($old_text)) > 0)
        {
        	echo("\033[{$len}D\033[{$len}K");
        }
        echo $str;
        $old_text = $str;
}
/**
 * This "code" encrypts old "condor4" documents. It 
 * tries to link the documents/templates/parts to only encrypt the 
 * necessary ones, but if it doesn't know it'll just assume it needs to 
 * be ENCRYPTZORED
 */

/**
 * Real crap way to validate a date string
 * but it works and gets me the stuff that I want 
 * so I guess it's not that crap but whatever.
 *
 * @param date $date
 * @return unknown
 */

function validate_date($date)
{
	$tim = strtotime($date);
	if(is_numeric($tim))
	{
		return date('YmdHis',$tim);
	}
	return FALSE;
}

function Get_Tokens_To_Encrypt($company_id, $db)
{
	$ret_val = array();
	$query = '
		SELECT
			token
		FROM
			condor_admin.tokens
		WHERE
			company_id = ?
		AND
			encrypted = 1
		';
	$stmt = $db->queryPrepared($query,array($company_id));
	while($row = $stmt->fetch(PDO::FETCH_OBJ))
	{
		$ret_val[] = substr($row->token,3,strlen($row->token) - 6);
	}
	
	return $ret_val;			
}

function Get_Tokens($data)
{
	$parser = new Template_Parser($data, '%%%');
	return $parser->Get_Tokens(FALSE);
}

function Encrypt($root_id, $db)
{
	$query = '
		SELECT
			file_name,
			encrypted
		FROM
			part
		WHERE
			part_id = ?
	';
	$stmt = $db->queryPrepared($query,array($root_id));
	$row = $stmt->fetch(PDO::FETCH_OBJ);
	//We only want to encrypt it if it wasn't done before
	if($row->encrypted == 0)
	{
		$file = $row->file_name;
		if(file_exists($file) && is_writeable($file) && is_readable($file))
		{
			$data = file_get_contents($file);
			if(!empty($data))
			{
				$data = Condor_Crypt::Encrypt($data);
				if(!empty($data))
				{
					file_put_contents($file,$data);
					$query = 'UPDATE part SET encrypted = 1	WHERE part_id = ?';
					$stmt = $db->queryPrepared($query,array($root_id));
					//Encrypt all of it's children
					$query = 'SELECT part_id FROm part WHERE parent_id = ?';
					$stmt = $db->queryPrepared($query,array($root_id));
					while ($row = $stmt->fetch(PDO::FETCH_OBJ))
					{
						Encrypt($row->part_id,$db);
					}
				}
				else 
				{
					die("Could not encrypt data for $root_id\n");
				}
			}
			else 
			{
				die("Could not read data for $file\n");
			}
		}
		else 
		{
			die("Could not read or write to file $file\n");	
		}
	}
}
function Get_Company_Name_Short($id, $db)
{
	$query = "SELECT name_short FROM condor_admin.company WHERE company_id=?";
	$stmt = $db->queryPrepared($query, array($id));
	$row = $stmt->fetch(PDO::FETCH_OBJ);
	return $row->name_short;
}

$db_info = MySQL_Pool::Get_Definition('condor_'.EXECUTION_MODE);
$db_config = new DB_MySQLConfig_1($db_info['host'],$db_info['username'],$db_info['password'],$db_info['database'],$db_info['port']);
$db = $db_config->getConnection();
//$db = new DB_Database_1($db_config->DSN,$db_config->User,$db_config->Passwd);

//Very first thing is to pull a giant list of
//all 
$query = '
	SELECT
		doc.document_id,
		doc.root_id,
		doc.template_id,
		agt.company_id
	FROM
		document doc
	JOIN
		condor_admin.agent agt
	ON
		agt.agent_id = doc.user_id
	JOIN
		part
	ON
		part.part_id = doc.root_id
	WHERE
		doc.date_created BETWEEN ? and ?
	AND
		part.encrypted = 0
';
$stmt = $db->queryPrepared($query,array($start_date,$end_date));
$cnt = $stmt->rowCount();
$start = microtime(TRUE);
$i = 0;
while($row = $stmt->fetch(PDO::FETCH_OBJ))
{
	$i++;
	if($i % 50 == 0 || $i < 5 || $i > ($cnt - 5) && $test_mode !== true)
	{
		$perc = sprintf('%02.02f',(($i / $cnt) * 100));
		$elapsed = sprintf("%05f",(microtime(TRUE) - $start));
		$remaining = sprintf("%05f",(($elapsed / $i) * ($cnt - $i)));
		Display("Processing $i of $cnt($perc)   Elapsed Seconds: $elapsed   ETA: $remaining");
	}
		
	//If we don't know the template, lets just encrypt it for fun
	if($row->template_id == NULL || $row->template_id == 'NULL' || !is_numeric($row->template_id))
	{
		Encrypt($row->root_id, $db); continue;
	}
	$query = '
		SELECT
			data
		FROM
			template
		WHERE
			template_id = ?
	';
	$template_stmt = $db->queryPrepared($query,array($row->template_id));
	$t_data = $template_stmt->fetch(PDO::FETCH_OBJ)->data;
	if(count(array_intersect(Get_Tokens_To_Encrypt($row->company_id,$db),Get_Tokens($t_data))) > 0)
	{
		Encrypt($row->root_id, $db);
		//If it's test mode, just die after we necrypt stuff
		if($test_mode === true)
		{
			$owned_by = Get_Company_Name_Short($row->company_id, $db);
			echo("Encrypted document id {$row->document_id}. Owned By: $owned_by");	
			die();
		}
		
	}

}
if($test_mode === true)
{
	echo("Did not find a document worth encrypting\n");
}
else 
{
	$elapsed = microtime(TRUE) - $start;
	echo("\n");
	echo("Conversion complete. Total Elapsed time ".sprintf("%05f",$elapsed)." seconds.\n");
}
