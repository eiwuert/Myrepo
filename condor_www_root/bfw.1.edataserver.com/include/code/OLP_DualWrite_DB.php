<?php
/**
 * Handles DualWriting 
 */
require_once('mysql.4.php');
class OLP_DualWrite_DB extends MySQL_4
{
	protected $dual_write_db;
			
	/**
	 * Construct the database, and setup the dual write  connection
	 *
	 * @param string $host
	 * @param string $user
	 * @param string $password
	 * @param boolean $debug
	 * @param string  $type
	 * @param string $mode
	 */
	public function __construct($host = NULL, $user = NULL, $password = NULL, $debug = TRUE, $type = "BLACKBOX", $mode = BFW_MODE)
	{
		parent::__construct($host, $user, $password, $debug);
		if(defined('OLP_DUAL_WRITE') && OLP_DUAL_WRITE == TRUE)
		{
			$db_info = Server::Get_Server($mode.'_PARALLEL',$type);
			//Now make sure they aren't the same server, because that'd be stupid
			//to dual write ot the same place
			list($h,$p) = strpos($host,':') !== false ? explode(':',$host) : array($host,'3306');
			if( $h == $db_info['host'] && 
				$p == $db_info['port'] &&
				$user = $db_info['user'] && 
				$password = $db_info['password'] 
			)
			{
				$this->dual_write_db = false;
				$this->dw_selected_db = false;
			}
			else 
			{
				if(isset($db_info['port']) && strpos($db_info['host'],':') === false)
				{
					$h = $db_info['host'].':'.$db_info['port'];
				}
				else 
				{
					$h = $db_info['host'];
				}
				$this->dual_write_db = new MySQL_4(
					$h,
					$db_info['user'],
					$db_info['password'],FALSE);
				$this->dual_write_db->Connect(TRUE);
			}
		}
		else 
		{
			$this->dual_write_db = false;
		}
	}
	
	private function alog($str)
	{
		$applog = OLP_Applog_Singleton::Get_Instance(APPLOG_SUBDIRECTORY, APPLOG_SIZE_LIMIT, APPLOG_FILE_LIMIT, NULL, APPLOG_ROTATE, APPLOG_UMASK);
		$applog->Write('[OLP Dual Write] - '.$str);
	}
	/**
	 * Query the database, and write to the dual_write db
	 *
	 * @param string $database
	 * @param string $query
	 * @return resource
	 */
	public function Query($database, $query)
	{
		$return = parent::Query($database, $query);
	
		if($this->dual_write_db !== false)
		{
			//We really only want to do replace/insert/update as anything else is kind of pointless
			//to run on the dual write thing.
			try 
			{
				$this->dual_write_db->Query($database, $query);
			}
			catch (Exception $e)
			{
				//We encountered an exception. We should theoritically 
				//probably maybe just possibly write something to a log
				//but I'd rather just write a really long comment saying
				//what we should do, rather than just doing it.
				$this->alog("Exception: ".$e->getMessage());
			}
		}
		return $return;
	}
	
	/**
	 * Write to the main db, but not the parallel db regardless
	 * of the dual write status.
	 *
	 * @param string $database
	 * @param string $query
	 * @return resource
	 */
	public function QueryMainDB($database, $query)
	{
		return parent::Query($database, $query);
	}
	
	public function QueryParallelDB($database, $query)
	{
		$return = false;
		if($this->dual_write_db !== false)
		{
			//We really only want to do replace/insert/update as anything else is kind of pointless
			//to run on the dual write thing.
			try 
			{
				$return = $this->dual_write_db->Query($database, $query);
			}
			catch (Exception $e)
			{
				//We encountered an exception. We should theoritically 
				//probably maybe just possibly write something to a log
				//but I'd rather just write a really long comment saying
				//what we should do, rather than just doing it.
				$this->alog("Exception: ".$e->getMessage());
			}
		}
		return $return;
	}
	
}