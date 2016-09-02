<?php
/**
 * File System Audit CronJob
 * 
 */

define('CONDOR_DIR',realpath(dirname(__FILE__).'/../'));
define('LOCK_FILE','/tmp/condor_cron_audit.lock');
require_once('mysqli.1.php');
require_once(CONDOR_DIR.'/lib/config.php');
require_once(CONDOR_DIR.'/lib/document.action.php');
require_once(CONDOR_DIR.'/lib/part.php');


/**
 * Handler for when we encounter uncaught exceptions to unlink the lock file properly
 */
function Exception_Handler($e)
{
	if(file_exists(LOCK_FILE))
	{
		unlink(LOCK_FILE);
	}
	die('Exception: '.$e->getMessage()."\n");
}

set_exception_handler('Exception_Handler');

if(file_exists(LOCK_FILE))
{
	$msg = 'cron_audit lock file has been in place since '.date('Y-m-d H:i:s',filectime(LOCK_FILE))."\n";
	die($msg);
}	
else
{
	touch(LOCK_FILE);
	$modes = array(
		MODE_LIVE,
		MODE_RC,
		MODE_DEV
	);

	if(!empty($argv[1]) && in_array($argv[1],$modes))
	{
		if(empty($argv[2]))
		{
			$days = 31;
		}
		elseif(is_numeric($argv[2]) && $argv[2] > 0 && $argv[2] < 32)
		{
			$days = $argv[2];
		}
		elseif(strcasecmp($argv[2],'all') == 0)
		{
			$days = false;
		}
		$cron = new File_System_Audit($argv[1],$days);
		$cron->Audit_Part_Table();
		$cron->Audit_File_System();
	}
	else
	{
		echo("Usage: ".basename(__FILE__)." <".join('|',$modes)."> [days to audit | all]\n");
	}
	unlink(LOCK_FILE);
}

/**
 *
 */
class File_System_Audit
{
	private $database;
	private $doc_action;
	private $mode;
	private $execution_id;
	private $file_names;
	private $days;
		
	const USER_ID = 990;
	
	/**
	 * Instantiate the object!
	 *
	 * @param string $mode
	 */
	public function __construct($mode,$days)
	{
		try 
		{
			$this->database = MySQL_Pool::Connect('condor_'.$mode);
			$this->doc_action = Document_Action::Singleton($this->database);
			$this->file_names = NULL;
			$this->days = $days;
			$this->execution_id = NULL;
			$this->Find_Execution_Id();
		}
		catch (Exception $e)
		{
			var_dump($e);
			exit;
		}
	}
	
	/**
	 * Go through everything in the part table and make sure it 
	 * exists on the filesystem
	 *
	 */
	public function Audit_Part_Table()
	{
		$query = 'SELECT
					part_id,
					parent_id,
					content_type,
					uri,
					file_name,
					compression,
					hash,
					audit_status,
					date_created
				FROM 
					part
			';
			if(is_numeric($this->days))
			{
				$min_date = date('Y-m-d 00:00:00',strtotime("-{$this->days} days"));
				$query .= " WHERE date_created >= '$min_date'";
			}
			$res = $this->database->Query($query);
			while(($row = $res->Fetch_Object_Row()))
			{
				$part = new Part($this->database, self::USER_ID, $this->doc_action, $row->part_id);
				//this should cut down our queries in like half
				$part->Load_From_Row($row);
				$part->Load_File();
				$part->Audit($this->execution_id);
			}
	}
		
	
	/**
	 * Go through everything in the filesystem
	 *
	 */
	public function Audit_File_System()
	{
		if(is_numeric($this->days))
		{
			$part_list = array();
			for($i = 0; $i <= $this->days;$i++)
			{
				$path = CONDOR_ROOT_DIR.'/'.date('Ymd',strtotime("-{$i} days"));
				$part_list = array_merge($part_list,$this->Get_Parts($path));
			}
		}
		else 
		{
			$part_list = $this->Get_Parts(CONDOR_ROOT_DIR);
		}
		$i = 0;
		$values = array();
		foreach($part_list as $part)
		{
			$i++;
			if(preg_match('/\/data\/([\d]{8})\/([\d]+_[\d]+.gz/',$part,$matches))
			{
				$date = $matches[1];
				//We already know this file name exists
				//as we already pulled it once.
				list($doc_id, $part_id)	= explode('_', basename($part, '.gz'));
				if($this->Part_File_Exists($part_id, $part))
				{
					$status = 'LINKED';
				}
				else 
				{
					$status = 'FALSE';
				}
				$hash = md5_file($part);
				$size = filesize($path);
				$s_path = $this->database->Escape_String($part);
				$s_hash = $this->database->Escape_String($hash);
				$s_stat = $this->database->Escape_String($status);
				
				$values[] = "
					(
						NOW(),
						{$this->execution_id},
						'$s_path',
						$size,
						'$s_hash',
						'$s_stat'
					)
				";
				if($i % 50)
				{
					if($this->Insert_Into_Filesyem_Audit($values))
					{
						$values = array();
						$i = 0;
					}
				}
			}
		}
	}
	
	/**
	 * Takes an array of values and inserts them into 
	 * filesystem_audit
	 *
	 * @param array $values
	 * @return boolean
	 */
	private function Insert_Into_Filesyem_Audit($values)
	{
		if(!is_array($values)) return FALSE;
		$insert_query = 'INSERT INTO 
			filesystem_audit 
			(
				date_audit,
				execution_id,
				file_path,
				file_size,
				file_hash,
				status
			)
			VALUES
		'.join(',',$values);
		try 
		{
			$this->database->Query($insert_query);
		}
		catch (Exception $e)
		{
			return FALSE;
		}
		return TRUE;
	}
	
	/**
	 * Queries the database to find the part_id of a part with 
	 * a particular file name.
	 *
	 * @param string $file
	 * @return mixed
	 */
	private function Part_File_Exists($id, $file)
	{
		$s_file = $this->database->Escape_String($file);
		$query = "
			SELECT 
				count(*) as count
			FROM
				part
			WHERE
				part_id = $id
			AND
				file_name='$s_file'
		";
		try 
		{
			$return = FALSE;
			$res = $this->database->Query($query);
			if($res->Row_Count())
			{
				$row = $res->Fetch_Object_Row();
				$return = $row->part_id;
			}
		}
		catch (Exception $e)
		{
			return $return;
		}
		return $return;
		
	}
	
	/**
	 * Finds the execution id for this audit execution
	 *
	 */
	private function Find_Execution_Id()
	{
		$query = 'SELECT
				COALESCE(MAX(execution_id), 0) execution_id
			FROM
				part_audit
		';
		$res = $this->database->Query($query);
		$this->execution_id = $res->Fetch_Object_Row()->execution_id;
		$this->execution_id++;
	}
	
	/**
	 * Resursively Load directories that match the %4d%2d condition
	 */
	private function Get_Parts($path,$dir_pattern="/[\d]{8}/",$file_pattern="/[\d]+_[\d]+.gz/")
	{
		$path = rtrim($path,'/');
		
		if(!is_dir($path)) $path = dirname($path);
		
		if(!is_readable($path))
		{
			return array();
		}
		$d = glob($path.'/*');
		$ret = array();
		foreach($d as $file)
		{
			if($file != "." && $file != "..")
			{
				if(is_dir("$file"))
				{
					if(preg_match($dir_pattern,$file))
					{
						$ret = array_merge($ret, $this->Get_Parts("$file", $dir_pattern,$file_pattern));
					}
				}
				elseif(preg_match($file_pattern, basename($file)))
				{
					$ret[] = $file;
				}
			}
		}
		return $ret;
	}
}
