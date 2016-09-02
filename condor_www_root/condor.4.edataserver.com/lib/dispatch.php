<?php
require_once('statpro_client.php');

/**
 * Class to handle dispatch writing to the Condor database.
 *
 * @author Brian Feaver
 * @copyright Copyright 2006 The Selling Source, Inc.
 */
class Dispatch
{
	private $sql;
	private $db;
	private $mode;
	private $statpro;
	private static $instance;
	
	const STATPRO_KEY = 'clk';
	const STATPRO_PASS = 'dfbb7d578d6ca1c136304c845';
	
	// Type constants
	const TYPE_SENT = 'SENT';
	const TYPE_INFO = 'INFO';
	const TYPE_RETRY = 'RETRY';
	const TYPE_FAIL = 'FAIL';
	const TYPE_COMPLETED = 'COMPLETED';
	
	
	/**
	 * Dispatch constructor.
	 *
	 * @param object $sql
	 * @param string $db
	 * @param string $mode
	 */
	private function __construct(&$sql, $db, $mode = 'LIVE')
	{
		$this->sql =& $sql;
		$this->db = $db;
		$this->mode = $mode;
	}
	
	/**
	 * Creates a singleton version of the class and returns the instance.
	 *
	 * @param object $sql
	 * @param string $db
	 * @param string $mode
	 * @return object
	 */
	public static function Singleton(&$sql, $db, $mode = 'LIVE')
	{
		if(!isset(self::$instance))
		{
			$class = __CLASS__;
			self::$instance = new $class($sql, $db, $mode);
		}
		
		return self::$instance;
	}
	
	/**
	 * Adds a dispatch to the database and returns the dispatch ID.
	 *
	 * @param int $document_id
	 * @param string $transport
	 * @param array $recipient
	 * @param array $sender
	 * @param int $user_id
	 * @return int
	 */
	public function Add_Dispatch($document_id, $transport, $recipient, $sender, $user_id, $message = NULL)
	{
		$ret_val = TRUE;
		
		if(isset($recipient['email_primary']))
		{
			$recipient = $recipient['email_primary'];
		}
		elseif(isset($recipient['fax_number']))
		{
			$recipient = $recipient['fax_number'];
		}
		else
		{
			$ret_val = FALSE;
		}
		
		if(isset($sender['email_primary']))
		{
			$sender = $sender['email_primary'];
		}
		elseif(isset($sender['fax_number']))
		{
			$sender = $sender['fax_number'];
		}
		else
		{
			$ret_val = FALSE;
		}
		
		if($ret_val)
		{
			$query = "
				INSERT INTO
					document_dispatch
				SET
					document_id = $document_id,
					date_created = NOW(),
					transport = '$transport',
					recipient = '$recipient',
					sender = '$sender',
					user_id = $user_id";

			try
			{
				$this->sql->Query($query);
				$ret_val = $this->sql->Insert_Id();
			}
			catch(Exception $e)
			{
//				echo $e->getMessage(); die();
				$ret_val = FALSE;
			}
		}
		
		return $ret_val;
	}
	
	/**
	 * Logs a dispatch history event for the given dispatch ID.
	 *
	 * @param int $dispatch_id
	 * @param string $status
	 * @param string $type
	 * @param string $message
	 */
	public function Log_Dispatch_Status($dispatch_id, $status, $type, $message = NULL)
	{
		/*
			We could probably generate status names directly from the status name itself
			but I don't trust anyone, so I'm making sure the status matches up first. ;P
			
			If we don't know what the status is, then we'll still hit an action with that
			status, but it won't hit a stat.
			
			We check this first, so that we can override the type if the status already exists.
			We really only use the $type parameter when the $status is unknown.
		*/
		switch(strtolower($status))
		{
			case 'timedout':
			case 'rejected':
			case 'format_failed':
			case 'no_formatter':
			case 'poll_rejected':
			case 'poll_no_document':
			case 'poll_failed':
			case 'failed':
			case 'removed':
			case 'killed':
			case 'bounced':
			case 'denied':
				$stat = $status;
				$type = self::TYPE_FAIL;
				break;
			case 'sent':
			case 'done':
				$stat = $status;
				$type = self::TYPE_SENT;
				break;
			case 'blocked':
			case 'open':
				$stat = $status;
				$type = self::TYPE_INFO;
				break;
			case 'requeued':
				$stat = $status;
				$type = self::TYPE_RETRY;
				break;
			default:
				$stat = '';
				break;
		}
		
		$status_id = $this->Get_Dispatch_Status_Id($status, $type);
		$query = "
			INSERT INTO
				dispatch_history
			SET
				date_created = NOW(),
				document_dispatch_id = $dispatch_id,
				dispatch_status_id = $status_id";
		
		try
		{
			$this->sql->Query($query);
			$id = $this->sql->Insert_Id();
			//Update the "dispatch_history_id" which
			//represents the most recent entry for this dispatch
			//in the history table. We track this for easy access
			//to the latest status update for the sake of reporting
			//failed documents.
			if(is_numeric($id))
			{
				$query = "
					UPDATE
						document_dispatch
					SET
						dispatch_history_id='$id'
					WHERE
						document_dispatch_id=$dispatch_id
					LIMIT 1
				";
				$this->sql->Query($query);
				
				//Certain times we'll want ot provide a 
				//more detailed message (failed emails for exmaple)
				//of why something does stuff. So we just insert it
				if(!empty($message))
				{
					$s_message = $this->sql->Escape_String($message);
					$query = "
					INSERT INTO 
						dispatch_history_message
					(
						dispatch_history_id,
						message	
					)
					VALUES
					(
						'$id',
						'$message'
					)
					ON DUPLICATE KEY
						UPDATE 
							message=VALUES(message)
					";
					$this->sql->Query($query);
				}
			}
		}
		catch(Exception $e)
		{
			//exit($e->getMessage());
		}
		
		/*
			We'll hit stats inside dispatch as we're only hitting StatPro stats for faxing and
			emails.
		*/
		//only bother hitting stats if we're like not on our private box
		if($this->mode != MODE_DEV && !empty($stat))
		{
			$stat_prefix = '';
			$keys = $this->Setup_Stats($dispatch_id, $stat_prefix);
			//We need to make sure we have a stat, AND space/track key before
			//hitting a stat
			if(!empty($keys['space_key']) && !empty($keys['track_key']))
			{
				$stat = $stat_prefix.$stat;
				$this->Hit_Stat($stat, $keys['space_key'], $keys['track_key']);
			}
		}
	}
	
	/**
	 * Returns the dispatch status ID. We only search by status, but we need
	 * the type in case the status doesn't exist.
	 *
	 * @param string $status
	 * @param string $type
	 * @return int
	 */
	public function Get_Dispatch_Status_Id($status, $type)
	{
		$status_id = FALSE;
		
		$query = "
			SELECT
				dispatch_status_id
			FROM
				dispatch_status
			WHERE
				name = '$status'";
		if(!empty($type))
		{
			$query .= " AND type='$type'";
		}
		
		try
		{
			
			$result = $this->sql->Query($query);
			
			if($row = $result->Fetch_Array_Row())
			{
				$status_id = $row['dispatch_status_id'];
			}
			else
			{
				$status_id = $this->Insert_New_Status($status, $type);
			}
			
		}
		catch(Exception $e)
		{
			
		}
		
		return $status_id;
	}
	
	/**
	 * Inserts a new status into the condor_2.dispatch_status table.
	 *
	 * @param string $status
	 * @param string $type
	 * @return int
	 */
	private function Insert_New_Status($status, $type = 'INFO')
	{
		$query = "
			INSERT INTO
				dispatch_status
			SET
				date_created = NOW(),
				name = '$status',
				type = '$type'";
		
		try
		{
			$this->sql->Query($query);
		}
		catch(Exception $e)
		{
			
		}
		
		return $this->sql->Insert_Id();
	}
	
	/**
	 * Hits stats, what else?
	 *
	 * @param string $space_key
	 * @param string $track_key
	 */
	private function Hit_Stat($stat, $space_key, $track_key)
	{
		//We have to set it in the session, statPro
		//will die
		$_SESSION['statpro']['space_key'] = $space_key;
		$_SESSION['statpro']['track_key'] = $track_key;
		if($this->statpro == NULL)
		{
			$mode = (strtoupper($this->mode) !== 'LIVE') ? 'test' : 'live';
			// create statpro object
			$bin = '/opt/statpro/bin/spc_'.self::STATPRO_KEY.'_'.$mode;
			$this->statpro = new StatPro_Client($bin, NULL, self::STATPRO_KEY, self::STATPRO_PASS);
		}
		
		$this->statpro->Space_Key($space_key);
		$this->statpro->Track_Key($track_key);
		$this->statpro->Record_Event($stat);
	}
	
	/**
	 * Retrieves the space key and track key from the document table and sets the stat prefix.
	 *
	 * @param int $dispatch_id
	 * @return array
	 */
	private function Setup_Stats($dispatch_id, &$stat_prefix)
	{
		$ret_val = FALSE;
		
		$query = "
			SELECT
				dd.document_id,
				dd.transport,
				d.space_key,
				d.track_key
			FROM
				document_dispatch dd
				JOIN document d ON dd.document_id = d.document_id
			WHERE
				dd.document_dispatch_id = $dispatch_id";
		
		try
		{
			$result = $this->sql->Query($query);
		
			if(($row = $result->Fetch_Array_Row()))
			{
				$ret_val = array(
					'space_key' => $row['space_key'],
					'track_key' => $row['track_key']
				);
				
				$stat_prefix = $row['transport'] = 'FAX' ? 'hylafax_' : 'ole_';
			}
		}
		catch(Exception $e)
		{
			// Do nothing for now
		}
		
		return $ret_val;
	}
}
?>
