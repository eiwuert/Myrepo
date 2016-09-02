<?php
/**
 * @package DB
 */

require_once('libolution/Object.1.php');
require_once('libolution/DB/IDatabaseConfig.1.php');

/**
 * SQLite database configuration object
 *
 * Handling Errors with SQLiteConfig
 *
 * These can be used to compare errors against
 * <code>
 * try
 * {
 * 		$db->exec("insert into data(event_name) values('visitor')");
 * }
 * catch (Exception $e)
 * {
 *     	$error = $db->errorInfo();
 *     	if($error[1] == DB_SQLiteConfig_1::ERROR_CODE_READONLY)
 *     	{
 * 			echo "Read only error: ", $error[2], "\n";
 *     	}
 * }
 * </code>
 *
 * @author Justin Foell <justin.foell@sellingsource.com>
 */
class DB_SQLiteConfig_1 extends Object_1 implements DB_IDatabaseConfig_1
{
	/**
	 * Success
	 *
	 */
	const ERROR_CODE_OK = 0;

	/**
	 * SQL Error or missing database
	 *
	 */
	const ERROR_CODE_ERROR = 1;

	/**
	 * Internal logic error in SQLite
	 *
	 */
	const ERROR_CODE_INTERNAL = 2;

	/**
	 * Access permission denied
	 *
	 */
	const ERROR_CODE_PERM = 3;

	/**
	 * Callback routine requested an abort
	 *
	 */
	const ERROR_CODE_ABORT = 4;

	/**
	 * The database file is locked
	 *
	 */
	const ERROR_CODE_BUSY = 5;

	/**
	 * A table in the database is locked
	 *
	 */
	const ERROR_CODE_LOCKED = 6;

	/**
	 * A malloc() failed
	 *
	 */
	const ERROR_CODE_NOMEM = 7;

	/**
	 * Attempt to write to a readonly database
	 *
	 */
	const ERROR_CODE_READONLY = 8;

	/**
	 * Operation terminated by sqlite_interrupt()
	 *
	 */
	const ERROR_CODE_INTERRUPT = 9;

	/**
	 * Some kind of disk I/O error occurred
	 *
	 */
	const ERROR_CODE_IOERR = 10;

	/**
	 * The database disk image is malformed
	 *
	 */
	const ERROR_CODE_CORRUPT = 11;

	/**
	 * Insertion failed because database is full
	 *
	 */
	const ERROR_CODE_FULL = 13;

	/**
	 * Unable to open the database file
	 *
	 */
	const ERROR_CODE_CANNOTOPEN = 14;

	/**
	 * Database lock protocol error
	 *
	 */
	const ERROR_CODE_PROTOCOL = 15;

	/**
	 * The database schema changed
	 *
	 */
	const ERROR_CODE_SCHEMA = 17;

	/**
	 * Too much data for one row of a table
	 *
	 */
	const ERROR_CODE_TOOBIG = 18;

	/**
	 * Abort due to constraint violation
	 *
	 */
	const ERROR_CODE_CONSTRAINT = 19;

	/**
	 * Data type mismatch
	 *
	 */
	const ERROR_CODE_MISMATCH = 20;

	/**
	 * Library used incorrectly
	 *
	 */
	const ERROR_CODE_MISUSE = 21;

	/**
	 * Feature not supported by operating system
	 *
	 */
	const ERROR_CODE_NOLFS = 22;

	/**
	 * Authorization denied
	 *
	 */
	const ERROR_CODE_AUTH = 23;

	/**
	 * Auxiliary database format error
	 *
	 */
	const ERROR_CODE_FORMAT = 24;

	/**
	 * 2nd parameter to sqlite_bind() was out of range
	 *
	 */
	const ERROR_CODE_RANGE = 25;

	/**
	 * File opened that is not a database
	 *
	 */
	const ERROR_CODE_NOTADB = 26;

	/**
	 * @var string
	 */
	private $version = "sqlite"; //can be "sqlite" (version 3) or "sqlite2" (version 2)

	/**
	 * @var string
	 */
	private $location; //can be absolute path "/tmp/my.db" or ":memory:"

	/**
	 * Constructor
	 *
	 * @param string $location
	 * @param string $ver2
	 */
	public function __construct($location, $ver2 = FALSE)
	{
		$this->location = $location;
		if ($ver2)
		{
			$this->version = "sqlite2";
		}
	}

	/**
	 * Returns an initialized database connection
	 * @return DB_IConnection_1
	 */
	public function getConnection()
	{
		$db = new DB_Database_1($this->getDSN());
		return $db;
	}

	/**
	 * returns pdo compatible dsn representing this configuration
	 *
	 * @return string
	 */
	public function getDSN()
	{
		return $this->version.':'.$this->location;
	}

	/**
	 * returns location
	 *
	 * @return string
	 */
	public function getLocation()
	{
		return $this->location;
	}
}
