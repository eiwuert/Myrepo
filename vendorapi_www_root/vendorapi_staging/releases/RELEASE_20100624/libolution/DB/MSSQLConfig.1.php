<?php
/**
 * @package DB
 */

/**
 * Microsoft SQL Server database configuration object.
 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
 */
class DB_MSSQLConfig_1 extends Object_1 implements DB_IDatabaseConfig_1
{
	/**
	 * MsSQL host address
	 *
	 * @var string
	 */
	private $host;

	/**
	 * MsSQL tcp port
	 *
	 * @var int
	 */
	private $port;

	/**
	 * MsSQL username
	 *
	 * @var string
	 */
	private $user;

	/**
	 * MsSQL password
	 *
	 * @var string
	 */
	private $passwd;

	/**
	 * MsSQL database name
	 *
	 * @var string
	 */
	private $database_name;

	/**
	 * Constructor
	 *
	 * @param string $host
	 * @param string $user
	 * @param string $passwd
	 * @param string $database_name
	 * @param int $port
	 */
	public function __construct($host, $user, $passwd = NULL, $database_name = NULL, $port = 3306)
	{
		$this->host = $host;
		$this->user = $user;
		$this->passwd = $passwd;
		$this->database_name = $database_name;
		$this->port = $port;
	}

	/**
	 * Returns an initialized database connection
	 * @return DB_Database_1
	 */
	public function getConnection()
	{
		$db = new DB_Database_1($this->getDSN(), $this->getUser(), $this->getPasswd());
		return $db;
	}

	/**
	 * returns pdo compatible dsn representing this configuration
	 *
	 * @return string
	 */
	public function getDSN()
	{
		return 'dblib:host='.$this->host.';port='.$this->port.';dbname=['.$this->database_name.']';
	}

	/**
	 * returns mssql username associated with this configuration
	 *
	 * @return string
	 */
	public function getUser()
	{
		return $this->user;
	}

	/**
	 * returns mssql password associated with this configuration
	 *
	 * @return string
	 */
	public function getPasswd()
	{
		return $this->passwd;
	}
}
