<?php
/**
 * @package DB
 */

/**
 * MySQL database configuration object.
 * @author John Hargrove <john.hargrove@sellingsource.com>
 */
class DB_MySQLConfig_1 extends Object_1 implements DB_IDatabaseConfig_1
{
	/**
	 * MySQL host address
	 *
	 * @var string
	 */
	private $host;

	/**
	 * MySQL tcp port
	 *
	 * @var int
	 */
	private $port;

	/**
	 * MySQL username
	 *
	 * @var string
	 */
	private $user;

	/**
	 * MySQL password
	 *
	 * @var string
	 */
	private $passwd;

	/**
	 * MySQL database name
	 *
	 * @var string
	 */
	private $database_name;
	
	/**
	 * Array of options to pass to PDO.
	 *
	 * @var array
	 */
	private $driver_options;

	/**
	 * Constructor
	 *
	 * @param string $host
	 * @param string $user
	 * @param string $passwd
	 * @param string $database_name
	 * @param int $port
	 * @param array $driver_options an array of driver options to pass to the Database constructor
	 */
	public function __construct($host, $user, $passwd = NULL, $database_name = NULL, $port = 3306, array $driver_options = NULL)
	{
		$this->host = $host;
		$this->user = $user;
		$this->passwd = $passwd;
		$this->database_name = $database_name;
		$this->port = $port;
		$this->driver_options = $driver_options;
	}

	/**
	 * Returns an initialized database connection
	 * @return DB_Database_1
	 */
	public function getConnection()
	{
		$db = new DB_Database_1($this->getDSN(), $this->getUser(), $this->getPasswd(), $this->getDriverOptions());
		$db->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, TRUE);

		return $db;
	}

	/**
	 * Returns mysql host associated with this configuration.
	 *
	 * @return string
	 */
	public function getHost()
	{
		return $this->host;
	}

	/**
	 * Returns mysql port associated with this configuration.
	 *
	 * @return int
	 */
	public function getPort()
	{
		return $this->port;
	}

	/**
	 * Returns mysql database name associated with this configuration.
	 *
	 * @return string
	 */
	public function getDatabaseName()
	{
		return $this->database_name;
	}

	/**
	 * returns pdo compatible dsn representing this configuration
	 *
	 * @return string
	 */
	public function getDSN()
	{
		return 'mysql:host='.$this->host.';port='.$this->port.';dbname='.$this->database_name;
	}

	/**
	 * returns mysql username associated with this configuration
	 *
	 * @return string
	*/
	public function getUser()
	{
		return $this->user;
	}

	/**
	 * returns mysql password associated with this configuration
	 *
	 * @return string
	 */
	public function getPasswd()
	{
		return $this->passwd;
	}
	
	/**
	 * Returns the driver options.
	 *
	 * @return array
	 */
	public function getDriverOptions()
	{
		return $this->driver_options;
	}
}

?>
