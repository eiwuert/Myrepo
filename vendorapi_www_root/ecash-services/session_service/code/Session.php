<?php

require_once 'null_session.1.php';
class Session
{
	/**
	 * @var PDO
	 */
	protected $db;
	
	const SLEEP_FIRST = 0.5;
	const SLEEP_MAX = 10.0;
	
	/**
	 * Really, bz isn't in use anymore, add it here if needed.
	 * @var string The compression type, either 'gz' (default) or 'bz'
	 */
	protected $compression = 'gz';	// could be 'bz' too, but not really in use.
	
	public function __construct(PDO $db)
	{
		$this->db = $db;
	}
	/**
	 * @throws RuntimeException
	 * @return string session_id that has been created. NOTE: The caller does
	 * NOT have a lock on this session, it is merely created.
	 */
	public function create($session_id = NULL)
	{
		$tries = 0;
		
		// 5 tries should be enough to generate a unique session_id
		do
		{
			try 
			{
				$id = $session_id ? $session_id :  md5(strval(time()) . strval(rand(0, getrandmax())));
				
				$this->createSessionFromID($id);
				break;
			}
			catch (InvalidArgumentException $e)
			{
				throw new SenderException("session id $id is an invalid session identifier.");
			}
			catch (Exception $e)
			{
				$id = '';
				$tries = $session_id ? 5 : $tries + 1;
			}
			
		} while ($tries < 5);
		
		if (!$id)
		{
			if ($session_id)
			{
				throw new SenderException("Session $session_id already exists, cannot create.");
			} 
			else
			{
				// something has gone bananas
				throw new RuntimeException('Unable to create session.');
			}
		}
		
		return $id;
	}
	
	/**
	 * @throws PDOException
	 */
	protected function createSessionFromID($id)
	{
		$stmnt = $this->db->prepare(
			"INSERT INTO " . $this->tableFromSessionID($id)
			. " (session_id, date_modified, date_created, date_locked, compression, session_info)
			VALUES (?, NOW(), NOW(), DATE_SUB(NOW(), INTERVAL 5 SECOND), ?, ?)"
		);
				
		$empty_array = $this->pack(array());
		$stmnt->execute(array($id, $this->compression, $empty_array));
	}
	
	public function lock($session_id, $seconds_to_block_for = 70, $seconds_to_lock_for = 60)
	{
		$seconds_to_block_for = floatval($seconds_to_block_for);
		$start = microtime(TRUE);
		$sleep_interval = self::SLEEP_FIRST;
		
		$session_exists = 'unknown';
		
		do
		{
			$lock_id = $this->tryToGetLock($session_id, $seconds_to_lock_for);
			if ($lock_id) return $lock_id;
			sleep($sleep_interval);
			$next_step = $sleep_interval * 2;
			$sleep_interval = $next_step > self::SLEEP_MAX ? self::SLEEP_MAX : $next_step;
			
			if ($session_exists == 'unknown')
			{
				if (!$this->sessionExists($session_id))
				{
					throw new InvalidArgumentException(
						"Session $session_id does not exist, cannot acquire lock."
					);
				}
				else 
				{
					$session_exists = TRUE;
				}
			}
			
		} while ($seconds_to_block_for >= (microtime(TRUE) - $start));
		
		throw new SessionLockException(
			'Unable to acquire lock for session ' . $session_id . ' - tried for ' 
			. round(microtime(TRUE) - floatval($start), 2) . ' seconds.'
		);
	}
	
	protected function sessionExists($session_id)
	{
		$stmnt = $this->db->prepare('SELECT COUNT(*) AS session_count FROM ' 
			. $this->tableFromSessionID($session_id) . ' WHERE session_id = ?'
		);
		$stmnt->execute(array($session_id));
		return ($stmnt->fetch(PDO::FETCH_OBJ)->session_count > 0);
	}
	
	protected function tryToGetLock($session_id, $seconds_to_lock_for)
	{
		$stmnt = $this->db->prepare(
			"UPDATE " . $this->tableFromSessionID($session_id) 
			. " SET date_locked = DATE_ADD(NOW(), INTERVAL ? SECOND), session_lock = 1
			WHERE session_id = ? 
			AND (session_lock = 0
				OR (date_locked = NULL OR date_locked < NOW()))"
		);
		try
		{
			// attempt atomic lock by "claiming" the row with an update
			$stmnt->execute(array(intval($seconds_to_lock_for), $session_id));
			$row_count = $stmnt->rowCount();
			
			if ($row_count)
			{
				// our update succeeded (updated at least 1 row)
				$stmnt = $this->db->prepare("SELECT date_locked FROM "
					. $this->tableFromSessionID($session_id) 
					. " WHERE session_id = ?"
				);
				$stmnt->execute(array($session_id));
				$row = $stmnt->fetch(PDO::FETCH_ASSOC);
				
				// the "date locked" will allow the caller to identify later
				return $row['date_locked'];
			}
		}
		catch (PDOException $e) 
		{
			trigger_error('Unable to acquire lock, sql error: ' . $e->getMessage(), E_USER_WARNING);
		}
		
		return FALSE;
	}
	
	public function read($session_id, $session_lock_key)
	{
		$stmnt = $this->db->prepare(
			"SELECT session_info, compression FROM " . $this->tableFromSessionID($session_id)
			. " WHERE session_id = ? AND date_locked = ?"
		);
		
		try
		{
			$stmnt->execute(array($session_id, $session_lock_key));
			$row = $stmnt->fetch(PDO::FETCH_ASSOC);
			
			if (is_array($row))
			{
				$unserialized = $this->unpack($row['session_info'], $row['compression']);
				if (is_array($unserialized)) 
				{
					return $unserialized;
				}
				else 
				{
					trigger_error(
						"unserialized session for $session_id and result was not array, instead was " 
						. var_export($unserialized, TRUE), E_USER_WARNING
					);
					return array();
				}
			} 
			else
			{
				throw new SessionException("Could not read session $session_id with key $session_lock_key.");
			}
		}
		catch (PDOException $e) 
		{
			throw new SessionException("Error attempting to read session $session_id");
		}
	}
	
	/**
	 * @throws RuntimeException
	 * @param string $session_lock_key The session lock key generated by {@see open()}
	 * @param string $session JSON encoded session variable.
	 * @return string
	 */
	public function save($session_id, $session_lock_key, array $session)
	{
		$stmnt = $this->db->prepare('SELECT COUNT(*) AS session_count FROM '
			. $this->tableFromSessionID($session_id) 
			. ' WHERE session_id = ? AND date_locked = ?'
		);
		
		try
		{
			$stmnt->execute(array($session_id, $session_lock_key));
			if ($stmnt->fetch(PDO::FETCH_OBJ)->session_count < 1)
			{
				throw new SenderException(
					"Unable to look up session with id ($session_id) and lock key ($session_lock_key)"
				);
			}
		}
		catch (PDOException $e)
		{
			throw new SessionException('Error looking up session for save: ' . $e->getMessage());
		}
		
		
		$packt_like_sardines = $this->pack($session);
		
		$stmnt = $this->db->prepare(
			"UPDATE " . $this->tableFromSessionID($session_id)
			. " SET session_info = ?
			WHERE session_id = ? AND date_locked = ?");
		try 
		{
			$stmnt->execute(array($packt_like_sardines, $session_id, $session_lock_key));
		}
		catch (PDOException $e)
		{
			throw new SessionException('Unable to save session: ' . $e->getMessage());
		}
	}
	
	/**
	 * @throws RuntimeException
	 * @param string $session_lock_key The lock key generated by {@see open()}
	 * @return string
	 */
	public function release($session_id, $session_lock_key)
	{
		// date_locked cannot be NULL so we set it in the past to release it.
		$stmnt = $this->db->prepare(
			"UPDATE " . $this->tableFromSessionID($session_id)
			. " SET date_locked = DATE_SUB(NOW(), INTERVAL 61 SECOND), session_lock = 0
			WHERE session_id = ? AND date_locked = ?");
		try
		{
			$stmnt->execute(array($session_id, $session_lock_key));
			if (!$stmnt->rowCount())
			{
				throw new InvalidArgumentException(
					"Session id ($session_id) or lock key ($session_lock_key) was incorrect, unable to release lock."
				);
			}
		}
		catch (PDOException $e)
		{
			throw new SessionException('Unable to release lock: ' . $e->getMessage());
		}
		
	}
	
	public function tableFromSessionID($session_id)
	{
		if (!is_string($session_id) || strlen($session_id) != 32)
		{
			throw new InvalidArgumentException(
				'Session ID must be 32 character string, got ' . strval($session_id)
			);
		}
		
		return 'session_' . $session_id{0};
	}
	
	// -------------------------------------------------------------------------
	
	protected function pack(array $data)
	{
		$this->startNullSession();
		$_SESSION = $data;
		$data = session_encode();
		session_write_close();
		
		$function = ($this->compression == 'gz' ? 'gzcompress' : 'bzcompress');
		$data = $function($data);
		
		if ($data === FALSE)
		{
			throw new SessionException('Unable to compress data.');
		}
		
		$data = base64_encode($data);
		if ($data === FALSE)
		{
			throw new SessionException('Unable to base64 encode session.');
		}
		
		return $data;
	}
	
	protected function startNullSession()
	{
		$null = new Null_Session_1();
		session_set_save_handler(
			array($null, 'Open'),
			array($null, 'Close'),
			array($null, 'Read'),
			array($null, 'Write'),
			array($null, 'Destroy'),
			array($null, 'Garbage_Collection')
		);
		@session_start();
	}
	
	protected function unpack($data, $compression = 'gz')
	{
		if (preg_match('/^[a-zA-Z0-9\/+]*={0,2}$/', $data))
		{
			$data = base64_decode($data);
			if ($data === FALSE)
			{
				throw new SessionException("Could not base64 decode session data.");
			}
		}

		$function = ($this->compression == $compression ? 'gzuncompress' : 'bzdecompress');
		$data = $function($data);
		if ($data === FALSE)
		{
			throw new SessionException("Unable to uncompress data using '$function.'");
		}
			
		$this->startNullSession();
		if (!session_decode($data))
		{
			session_write_close();
			throw new SessionException("Unable to decode session data.");
		}
		
		$copy = $_SESSION;
		session_write_close();
		return $copy;
	}
}
?>
