<?php

/**
 * Model class to handle writing/reading the stateobject
 * stuff.
 *
 * @author Stephan Soileau <stephan.soileau@sellingsoure.com>
 * @author Andrew Minerd
 */
class VendorAPI_StateObjectModel
{
	/**
	 * Where to store database files.
	 * @var string
	 */
	const PATH_FORMAT = '/var/state/vendor_api/%%%MODE%%%/%%%NAME_SHORT%%%';
	
	/**
	 * How many times do we attempt to use an existing database
	 * before giving up.
	 */
	const OPEN_ATTEMPTS = 100;

	/**
	 * The property short
	 * @var string
	 */
	protected $name_short;

	/**
	 * Set the base path to use for this model?
	 * @var string
	 */
	protected $base_path;

	/**
	 * The property short is really just the subdirectory
	 * to use when creating the database.
	 *
	 * @throws RuntimeException
	 * @param string $name_short
	 */
	public function __construct($name_short)
	{
		$this->setNameShort($name_short);
	}

	/**
	 * Return the base path?
	 *
	 * @return string
	 */
	public function getBasePath()
	{
		return $this->base_path;
	}

	/**
	 * Set the property short for this database connection
	 *
	 * @param string $name_short
	 * @return void
	 */
	public function setNameShort($name_short)
	{
		$this->base_path = str_replace('%%%NAME_SHORT%%%', $name_short, $this->getPathFormat());
		$this->base_path = str_replace('%%%MODE%%%',EXECUTION_MODE, $this->base_path);
		if (!file_exists($this->base_path) && !mkdir($this->base_path, 0775, TRUE))
		{
			throw new RuntimeException("Could not create directory: {$this->base_path}");
		}
		$this->name_short = $name_short;
	}

	/**
	 * Mostly a silly stub for the sake of testing
	 * this thing
	 *
	 * @return string
	 */
	public function getPathFormat()
	{
		return self::PATH_FORMAT;
	}

	/**
	 * Saves a state object to a local database to be processed by the
	 * scrubber. This will attempt to reuse existing database files if they
	 * are available, otherwise it will create a new file.
	 * @param VendorAPI_StateObject $state
	 * @return int
	 */
	public function save(VendorAPI_StateObject $state)
	{
		$db = $this->openDatabase($filename);
		try
		{
			$id = $this->saveTo($db, $state);
		}
		catch (Exception $e)
		{
			$this->unlock($filename);
			
			// attempt again with a brand new database to rule
			// out corruption in existing files
			$db = $this->createNewDatabase($filename);
			$id = $this->saveTo($db, $state);
		}
		
		$this->unlock($filename);
		return $id;
	}
	
	/**
	 * Saves a state object to the given database.
	 * @param DB_IConnection_1 $db
	 * @param VendorAPI_StateObject $state
	 */
	protected function saveTo(DB_IConnection_1 $db, VendorAPI_StateObject $state)
	{
		$db->queryPrepared('INSERT INTO state_object
			(date_created, date_modified, state_object, name_short)
			VALUES (?, ?, ?, ?)',
			array( date('Y-m-d H:i:s'), date('Y-m-d H:i:s'),
				gzcompress(serialize($state)), $this->name_short)
			);
		return $db->lastInsertId();
	}

	/**
	 * Attempts to open an existing database. If an existing database
	 * cannot be opened (an unlocked database cannot be found in less than
	 * the maximum number of open attempts), then a new database file is
	 * created. The database will be locked and must be unlocked when closed.
	 * @param string $file Will be populated the database filename
	 * @return DB_Database_1
	 */
	protected function openDatabase(&$file = NULL)
	{
		$databases = glob($this->base_path.DIRECTORY_SEPARATOR.'*.db');
		
		$max = min(count($databases), self::OPEN_ATTEMPTS);
		shuffle($databases);
		
		for ($i = 0; $i < $max; $i++)
		{
			$file = array_pop($databases);
			if (is_writable($file) && $this->lock($file))
			{
				return new DB_Database_1("sqlite:{$file}");
			}
		}
		return $this->createNewDatabase($file);
	}

	/**
	 * Creates a new database file. This is guaranteed to be a completely
	 * new database. The database will be locked and must be unlocked when closed.
	 * @param string $file Will be populated with the new database filename
	 * @return DB_Database_1
	 */
	protected function createNewDatabase(&$file = NULL)
	{
		$cnt = mt_rand(0, 99999);
		while (true)
		{
			$file = $this->base_path.DIRECTORY_SEPARATOR.sprintf("%02d.db", $cnt++);
			
			// double-checked locking; ensure we have
			// a new file to prevent any issues writing
			// to existing files (permissions, etc.)
			if (!file_exists($file) && $this->lock($file)) {
				if (!file_exists($file)) {
					return $this->createDatabase($file);
				}
				$this->unlock($file);
			}
		}
	}
	
	/**
	 * Creates a lock on a database file. The locks created are advisory -- that
	 * is, every system using the file must lock the file using the same method.
	 * Only one attempt to lock the file is made, and if it is unsuccessful,
	 * false is returned. Acquiring a lock creates a .lock file in the same
	 * directory containing the PID of the process holding the lock; these lock
	 * files are opened in exclusive create mode.
	 * @param string $file Filename of the database to lock
	 * @return True if the lock was acquired, false otherwise
	 */
	protected function lock($file)
	{
		$lock_file = $this->lockFile($file);
		
		// exclusive create will return false if
		// the file already exists
		if (($fp = fopen($lock_file, 'x')) !== false)
		{
			fwrite($fp, posix_getpid());
			fclose($fp);
			return TRUE;
		}
		return FALSE;
	}
	
	/**
	 * Removes a lock on a database file.
	 * @param string $file Filename of the database to unlock
	 */
	protected function unlock($file)
	{
		$lock_file = $this->lockFile($file);
		if (file_exists($lock_file))
		{
			@unlink($lock_file);
		}
	}

	/**
	 * Take a db file and turn it into a
	 * lock file name
	 *
	 * @param string $db_file
	 * @return string
	 */
	protected function lockFile($db_file)
	{
		return str_replace('.db', '.lock', $db_file);
	}

	/**
	 * Create a new database schema in the file
	 * @param string $file
	 * @return void;
	 */
	protected function createDatabase($file)
	{
		$db = new DB_Database_1('sqlite:'.$file);
		$db->exec("
			CREATE TABLE `state_object` (
				`state_object_id` INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
				`date_created` TIMESTAMP NULL DEFAULT NULL,
				`date_modified` TIMESTAMP NULL DEFAULT NULL,
				`state_object` BLOB NOT NULL,
				`attempts` INTEGER NOT NULL DEFAULT 0,
				`name_short` VARCHAR(10) NOT NULL DEFAULT '{$this->name_short}'
			)
		");
		return $db;
	}
}
