<?php

require_once('dispatch.php');
require_once('condor_applog.php');
/**
 * Basically queue some documents for sending via our LOVELY CONDOMAIL system!
 * and also get them out so CONDOMAIL can like send it to someone or something
 *
 */

class Mail_Queue
{
	const DEFAULT_SEND_PRIORITY = 10;
	
	private $mysqli;
	private $mode;

	/**
	 * Create the object and sets up database connections
	 *
	 * @param unknown_type $mode
	 * @param unknown_type $db
	 */
	public function __construct($mode, $db = NULL)
	{
		if($db instanceof MySQLi_1)
		{
			$this->mysqli = $db;
		}
		else 
		{
			$this->mysqli = MySQL_Pool::Connect('condor_' . $mode);
		}
		$this->mode = $mode;
	}
	
	/**
	 * Insert a Document into the Queue to Be Sent
	 */
	public function Insert_Queue($document_id, $account_id, $dispatch_id, $send_priority = self::DEFAULT_SEND_PRIORITY)
	{
		$status_id = $this->Get_Status_Id('queued');
		if($status_id === FALSE)
		{
			$status_id = $this->Insert_New_Status('queued','INFO');
		}
		if($status_id === FALSE)
		{
			throw new Exception("Could not find or create new status.");
		}
		$query = "
			INSERT INTO
				mail_queue
			SET
				date_created = NOW(),
				date_modified = NOW(),
				document_id = $document_id,
				account_id = $account_id,
				dispatch_id = $dispatch_id,
				status_id = $status_id,
				send_priority = $send_priority
		";
		try 
		{
			$this->mysqli->Query($query);
		}
		catch (Exception $e)
		{
			return FALSE;
		}
		return $this->mysqli->Insert_Id();
	}
	
	/**
	 * Update the status of an item in the queue
	 */
	public function Update_Status($queue_id, $status, $status_type = NULL, $message = false)
	{
		$return = false;		
		$status_id = $this->Get_Status_Id($status, $status_type);
		if(!is_numeric($status_id))
		{
			$status_id = $this->Insert_New_Status($status, ((is_string($status_type)) ? $status_type : 'INFO'));
		}
		if(is_numeric($status_id))
		{
			if(!is_string($status_type))
			{
				$status_type = $this->Get_Status_Type($status_id);
			}
			$query = "
				UPDATE
					mail_queue
				SET
					status_id = $status_id,
					date_modified = NOW()
				WHERE
					mail_queue_id=$queue_id
			";
			try 
			{
				$this->mysqli->Query($query);
				$this->Update_Dispatch_Status($queue_id, $status, $status_type, $message);
			}
			catch(Exception $e)
			{
				return FALSE;
			}	
			return TRUE;
		}
		return FALSE;
	}
	
	/**
	 * Takes an array of junk and builds an array of status ids
	 *
	 * @param unknown_type $status
	 */
	protected function getStatusIdArray($status)
	{
		if(!is_array($status)) $status = array($status);
		$status_ids = array();
		foreach($status as $key => $stat)
		{
			if(is_array($stat) && isset($stat['name']) && isset($stat['type']))
			{
				$id = $this->Get_Status_Id($stat['name'],$stat['type']);
			}
			elseif(is_array($stat) && isset($stat['type']))
			{
				$id = $this->getStatusIdsByType($stat['type']);
			}
			elseif(is_string($stat))
			{
				$id = $this->Get_Status_Id($stat);
			}
			if(!is_array($id)) $id = array($id);
			foreach($id as $i)
			{
				//only add it if it's a real number
				if(is_numeric($i))
				{
					$status_ids[] = $i;
				}
			}
		}
		$status_ids = array_unique($status_ids);
		return $status_ids;
	}
	
	/**
	 * Returns the number of  failures
	 */
	public function Get_History_Count($dispatch_id, $types = false)
	{
		$query = "
			SELECT
				count(*) as cnt
			FROM
				dispatch_history
			WHERE
				document_dispatch_id=$dispatch_id
		";
		$status_ids = $this->getStatusIdArray($types);
		if(is_array($status_ids) && count($status_ids) > 0)
		{
			$query .= ' AND dispatch_status_id IN ('.join(',',$status_ids).')';
		}
		try 
		{
			$return = FALSE;
			$res = $this->mysqli->Query($query);
			if(($row = $res->Fetch_Object_Row()))
			{
				$return = $row->cnt;
			}
		}
		catch (Exception $e)
		{
			return FALSE;			
		}
		return $return;
	}
	/**
	 * Log the status into the dispatch history
	 *
	 * @param int $queue_id
	 * @param string $status
	 * @param string $type
	 * @param string $message
	 * @return boolean
	 */
	private function Update_Dispatch_Status($queue_id, $status, $type, $message)
	{
		//First find the dispatch id
		//then insert into the history
		$query = "
			SELECT
				dispatch_id,
				document_id
			FROM
				mail_queue
			WHERE
				mail_queue_id=$queue_id
		";
		$res = $this->mysqli->Query($query);
		if(($row = $res->Fetch_Object_Row()))
		{
			$dispatch_id = $row->dispatch_id;
			$dispatch = Dispatch::Singleton($this->mysqli,'condor',$this->mode);
			$dispatch->Log_Dispatch_Status($dispatch_id, $status, $type, $message);
		}
		return TRUE;
	}
		
	/**
	 * Pull all documents in the queue for sending based with a given.
	 * The status can be an array of various status strings or an array containing
	 * arrays with a 'name' element being the name of the status and a 'type' element
	 * being the status type. You can optionally also pass date constraints in
	 * the format of YmdHis. It'll return an array of stdClass objects containing
	 * data about each item in the queue.
	 *
	 * @param mixed $status
	 * @param date $start_date
	 * @param date $end_date
	 * @return array
	 */
	public function Get_Queued_Mails(
		$status,
		$start_date=NULL,
		$end_date=NULL,
		$account_id=NULL)
	{
		$ret_val = FALSE;
		$wheres = array();
		if(!is_null($start_date))
		{
			//if there's no end date, the end date is now!
			if(is_null($end_date))
			{
				$end_date = date('YmdHis');
			}
			$wheres[] = "mq.date_modified BETWEEN '$start_date' AND '$end_date'";
		}
		elseif(is_null($start_date) && !is_null($end_date))
		{
			$wheres[] = "mq.date_modified < '$end_date'";
		}
		if(is_array($account_id))
		{
			$wheres[] = "account_id IN (".join($account_id).")";
		}
		elseif(is_numeric($account_id))
		{
			$wheres[] = "account_id = ".$account_id;
		}
		$status_ids = $this->getStatusIdArray($status);
						
		//If we have any valid status_ids start pulling queued documents
		if(count($status_ids) > 0)
		{
			if(count($status_ids) == 1)
			{
				$wheres[] = 'status_id = '.$status_ids[0];
			}
			else 
			{
				$wheres[] =  'status_id IN ('.join(',',$status_ids).')';
			}
			$query = '
				SELECT
					mq.mail_queue_id,
					mq.date_modified,
					mq.date_created,
					mq.document_id,
					mq.status_id,
					mq.dispatch_id,
					mq.account_id,
					mq.send_priority,
					t.name AS template_name,
					t.template_id
				FROM
					mail_queue mq
					INNER JOIN document d
						ON mq.document_id = d.document_id
					INNER JOIN template t
						ON d.template_id = t.template_id
			';
			//if we have where clauses defined, append them to the end of the query.
			if(is_array($wheres) && count($wheres) > 0)
			{
				$query .= " WHERE ".join(' AND ',$wheres);
			}
			
			try 
			{
				$query .= " ORDER BY send_priority";
				$res = $this->mysqli->Query($query);
				if($res->Row_Count() > 0)
				{
					$ret_val = array();
					while(($row = $res->Fetch_Object_Row()))
					{
						$ret_val[] = $row;
					}
				}
			}
			catch (Exception $e)
			{
				Condor_Applog::Log("EXCEPTION:".$e->getMessage());
				return FALSE;
			}
		}
		return $ret_val;
	}
	
	/**
	 * Returns all status ids for dispatch_status
	 * that have a particular type./
	 *
	 * @param string $type
	 * @return unknown
	 */
	protected function getStatusIdsByType($type)
	{
		$return = false;
		if(is_string($type))
		{
			$s_type = $this->mysqli->Escape_String($type);
			$query = "SELECT
				dispatch_status_id
			FROM
				dispatch_status
			WHERE
				type='$s_type';
			";
			try 
			{
				$res = $this->mysqli->Query($query);
				$return = array();
				while(($row = $res->Fetch_Object_Row()))
				{
					$return[] = $row->dispatch_status_id;
				}
			}
			catch (Exception $e)
			{
				
			}
		}
		return $return;
	}
	
	/**
	 * Returns a status id based on a name and a TYPE!
	 *
	 * @param string $name
	 * @param string $type
	 * @return int
	 */
	private function Get_Status_Id($name, $type=NULL)
	{
		$s_name = $this->mysqli->Escape_String($name);
		if(!is_null($type))
		{
			$s_type = $this->mysqli->Escape_String($type);
			$where = " AND type='$s_type'";
		}
		else 
		{
			$where = '';
		}
		$query = "
			SELECT
				dispatch_status_id
			FROM
				dispatch_status
			WHERE
				name='$s_name'
				$where
			LIMIT 1;
		";
		try 
		{
			$res = $this->mysqli->Query($query);
		}
		catch (Exception $e)
		{
			return FALSE;
		}
		if(($row = $res->Fetch_Object_Row()))
		{
			return $row->dispatch_status_id;
		}
		else 
		{
			return FALSE;
		}
	}
	
	private function Get_Status_Type($status_id)
	{
		$ret_val = FALSE;
		try 
		{
			$query = 
			"
				SELECT
					type
				FROm
					dispatch_status
				WHERE
					dispatch_status_id='$status_id'
			";
			$res = $this->mysqli->Query($query);
			if(($row = $res->Fetch_Object_Row()))
			{
				$ret_val = $row->type;
			}
		}
		catch (Exception $e)
		{
			return FALSE;
		}
		return $ret_val;
	}
	
	/**
	 * Insert a new status and return it's new id
	 * @param string $name
	 * @param string $type
	 * @return int
	 */
	private function Insert_New_Status($name, $type='INFO')
	{
		$s_name = $this->mysqli->Escape_String($name);
		$s_type = $this->mysqli->Escape_string($type);
		
		$query = "
			INSERT INTO
				dispatch_status
			SET
				name='$s_name',
				type='$s_type',
				date_created=NOW()
		";
		try 
		{
			$this->mysqli->Query($query);
			return ($this->mysqli->Insert_Id() > 0) ? $this->mysqli->Insert_Id() : FALSE;
		}
		catch (Exception $e)
		{
			return FALSE;
		}
			
	}
}
