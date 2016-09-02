<?php

/**
 * @package Stats.StatPro
 *
 */

/**
 * base class for StatPro client and EnterprisePro client
 *
 * @author John Hargrove <john.hargrove@sellingsource.com>
 *
 */
abstract class Stats_StatPro_ClientBase_1 //extends Object_1
{
	const STATPRO_BASE_DIR = "/opt/statpro/var/";

	const MAX_OPEN_ATTEMPTS = 3;
	const MAX_JOURNAL_ATTEMPTS = 100;
	const MAX_QUERY_ATTEMPTS = 4;

	const OPEN_RETRY_DELAY = 500000;
	const QUERY_RETRY_DELAY = 200000;

	const ROW_RECORD_EVENT = 1;
	const ROW_CREATE_SPACE = 2;

	/**
	 * @var string
	 */
	protected $statpro_key;

	/**
	 * @var string
	 */
	protected $base_path;

	/**
	 * @var int
	 */
	protected $pid;

	/**
	 * @var string
	 */
	protected $journal_lock_file;

	/**
	 * @var string
	 */
	protected $journal_name;

	/**
	 * @var DB_IConnection_1
	 */
	protected $database;

	/**
	 * @var bool
	 */
	protected $is_batch = FALSE;

	/**
	 * @var array
	 */
	protected $batch = array();

	/**
	 * @var string
	 */
	protected $statpro_base_dir;

	/**
	 * @var string
	 */
	protected $auth_user;

	/**
	 * @var string
	 */
	protected $auth_password;

	/**
	 * Returns the current user
	 * @return string
	 */
	public function getAuthUser()
	{
		return $this->auth_user;
	}

	/**
	 * Returns the current password
	 * @return string
	 */
	public function getAuthPassword()
	{
		return $this->auth_password;
	}

	/**
	 * Sets the current user
	 * @return void
	 */
	public function setAuthUser($value)
	{
		$this->auth_user = $value;
	}

	/**
	 * Sets the current password
	 * @return void
	 */
	public function setAuthPassword($value)
	{
		$this->auth_password = $value;
	}


	/**
	 * @param string $statpro_key
	 * @param string $auth_user
	 * @param string $auth_password
	 * @param string $statpro_base_dir
	 */
	public function __construct($statpro_key, $auth_user, $auth_password, $statpro_base_dir = self::STATPRO_BASE_DIR)
	{
		$this->statpro_base_dir = $statpro_base_dir;
		$this->setStatproKey($statpro_key);
		$this->auth_user = $auth_user;
		$this->auth_password = $auth_password;
	}

	/**
	 * Sets the statpro key
	 * Usually in the form of spc_customer_mode
	 *
	 * @param string $value
	 * @return void
	 */
	public function setStatproKey($value)
	{
		if (!preg_match('/^spc_.*$/', $value))
		{
			throw new Exception("Invalid StatPro key ({$statpro_key})");
		}
		$this->statpro_key = $value;
		$this->base_path = $this->statpro_base_dir . $this->statpro_key . '/journal';
		$this->ensureWritable();

		// reserve space in the batch for this key
		if ($this->is_batch
			&& !isset($this->batch[$value]))
		{
			$this->batch[$value] = array();
		}
	}

	/**
	 * Returns the current StatPro key
	 * @return string
	 */
	public function getStatproKey()
	{
		return $this->statpro_key;
	}

	/**
	 * Ensures that the base path is writable
	 * @throws Exception
	 * @return void
	 */
	protected function ensureWritable()
	{
		if (!file_exists($this->base_path))
		{
			if (!mkdir($this->base_path, 0775, TRUE))
			{
				throw new Exception("Statpro is unable to create the journal directory.");
			}
		}
		if (!is_writable($this->base_path))
		{
			throw new Exception("Statpro is unable to write to the journal directory.");
		}
	}

	/**
	 * Returns a hashed version of $msg
	 * If $key is provided, it's used as salt
	 *
	 * @param string $msg
	 * @param string $key
	 * @return string
	 */
	protected function hash($msg, $key = NULL)
	{
		return Util_Convert_1::bin2String(Util_Convert_1::hex2Bin(sha1($msg.$key)));
	}

	/**
	 * Opens a statpro journal
	 * @return void
	 */
	protected function openJournal()
	{
		$journal_path = $this->base_path . "/";

		// get list of existing databases
		$glob = glob($journal_path."journal_*.db");

		// if there wasn't any
		if (!is_array($glob) || count($glob) == 0)
		{
			// generate a random one
			$glob = array($journal_path."journal_".sprintf("%06d", mt_rand(0, 999)).".db");
		}

		// randomize our list
		shuffle($glob);

		// Fetch our process ID. Could use posix_getpid(), but I don't think
		// this one requires any lib dependancies
		$this->pid = getmypid();

		$this->lh = NULL;
		// 100 tries
		for ($i = 0, $found = FALSE; $i < self::MAX_JOURNAL_ATTEMPTS && !$found; $i++)
		{
			// check each existing journal
			//foreach ($glob as $journal_name)
			for ($j = 0; $j < count($glob) && !$found; $j++)
			{
				$journal_name = $glob[$j];

				// Set the lock file for this journal
				$this->journal_lock_file = $journal_name.'-lock';

				// Check to see if lock or journal exists for this item
				if (!file_exists($this->journal_lock_file)
					&& !file_exists($journal_name . '-journal'))
				{
					$this->lh = @fopen($this->journal_lock_file, 'x');
					if (! $this->lh) continue;

					$wb = NULL;
					if (!flock($this->lh, LOCK_EX|LOCK_NB, $wb))
					{
						fclose($this->lh);
						$this->lh = NULL;
						continue;
					}

					// If we made it here, we have found a db that is not locked
					try {
						$this->openDatabase($journal_name);
						$found = TRUE;
					} catch (Exception $e) {
						$found = FALSE;
					}
				}
			}

			// If we iterated through our entire list of possible journals and they're all locked
			// Add a random entry to the list
			if (!$found)
			{
				// Shove it on the beginning so it gets hit first (hopefully)
				array_unshift($glob, $journal_path."journal_".sprintf("%06d", mt_rand(0, 999)).".db");

				// Remove duplicate items and renumber
				$glob = array_values(array_unique($glob));

				clearstatcache();
			}
		}

		// If we've made it to this point without finding the item, throw exception
		if (!$found)
		{
			throw new Exception("Unable to open a journal after {$i} tries.");
		}
		
		$this->database->exec('begin');
	}

	/**
	 * Attempts (possibly multiple times) to open the database at $path
	 * Opens the database and creates the base schema
	 *
	 * @param string $path
	 * @return void
	 */
	protected function openDatabase($path)
	{
		$failed = FALSE;
		$attempts = 0;

		do
		{
			try
			{
				$config = new DB_SQLiteConfig_1($path, TRUE);
				$this->database = $config->getConnection();
				$failed = FALSE;
			}
			catch (PDOException $e)
			{
				$failed = TRUE;
				usleep(self::OPEN_RETRY_DELAY);
			}
		}
		while ($failed && $attempts++ < self::MAX_OPEN_ATTEMPTS);

		if ($this->database === NULL)
		{
			throw new Exception("Unable to open database after ".self::MAX_OPEN_ATTEMPTS." tries.");
		}

		$this->journal_name = $path;

		$count = $this->database->querySingleValue("select count(*) from sqlite_master where name = 'data'");

		if ($count == 0)
		{
			try {
				$this->database->exec("
					CREATE TABLE data
					(action_date,
					action,
					ap01, ap02, ap03, ap04, ap05, ap06, ap07, ap08, ap09, ap10,
					status_flag)"
				);
			}
			catch (PDOException $e)
			{
				throw new Exception("Unable to create data table!");
			}
			@chmod($this->journal_name, 0660);
		}
	}

	/**
	 * Attempts to execute a query, possibly multiple times
	 * If $params is provided, the query will be prepared and executed
	 *
	 * @param string $query
	 * @param array $params
	 * @return DB_IStatement_1
	 */
	protected function query($query, array $params = NULL)
	{
		$tries = 0;

		while (TRUE)
		{
			try
			{
				$tries++;
				if ($params === NULL)
				{
					$stmt = $this->database->query($query);
				}
				else
				{
					$stmt = $this->database->prepare($query);
					$stmt->execute($params);
				}
				return $stmt;
			}
			catch (Exception $e)
			{
				if ($tries >= self::MAX_QUERY_ATTEMPTS)
				{
					throw new Exception("Failed to execute query after $tries attempts.");
				}
				usleep(self::QUERY_RETRY_DELAY);
			}
		}
	}

	/**
	 * Inserts into a journal
	 *
	 * @param array $args
	 * @return void
	 */
	protected function insertJournal(array $args)
	{
		$query = "
		insert into data values(
			?, ?, ?,
			?, ?, ?,
			?, ?, ?,
			?, ?, ?,
			'LOCAL')";

		$this->query($query, array_pad($args, 12, ''));
	}

	/**
	 * Commits and closes the database
	 * @return void
	 */
	protected function closeJournal()
	{
		$this->database->exec("commit");
		fclose($this->lh);
		unlink($this->journal_lock_file);
		$this->database = NULL;
	}

	/**
	 * Begins a stat batch
	 * @throws Exception
	 * @return void
	 */
	public function beginBatch()
	{
		if ($this->is_batch)
			throw new Exception("Batch already started.");

		$this->is_batch = TRUE;
		$this->batch = array(
			$this->statpro_key => array()
		);
	}

	/**
	 * Ends the current batch
	 * This closes the batch and inserts all queued events
	 * @return void
	 */
	public function endBatch()
	{
		foreach ($this->batch as $statpro_key=>$batch)
		{
			$this->setStatproKey($statpro_key);
			$this->openJournal();

			foreach ($batch as $batch_item)
			{
				$this->insertJournal($batch_item);
			}

			$this->closeJournal();
		}

		$this->is_batch = FALSE;
		$this->batch = array();
	}

	/**
	 * Inserts a single event, adding the standard row items
	 * If in a batch, the insert is queued until endBatch()
	 *
	 * @param int $row_type
	 * @param array $args
	 * @param int $action_date
	 * @return void
	 */
	public function insert($row_type, array $args, $action_date = NULL)
	{
		if ($action_date === NULL) $action_date = time();
		$args = array_merge(array($action_date, $row_type, $this->auth_user, $this->auth_password), $args);

		if ($this->is_batch)
		{
			$this->batch[$this->statpro_key][] = $args;
		}
		else
		{
			for ($i = 0 ; $i < 10 ; $i++)
			{
				try
				{
					$this->openJournal();
					$this->insertJournal($args);
					$this->closeJournal();
				}
				catch (Exception $e)
				{
					//echo date('Y-m-d H:i:s')." WARNING: caught exception on ".$this->database->getDSN()."\n".$e->__toString()."\n";
					continue;
				}
				break;
			}
		}
	}
}
?>
