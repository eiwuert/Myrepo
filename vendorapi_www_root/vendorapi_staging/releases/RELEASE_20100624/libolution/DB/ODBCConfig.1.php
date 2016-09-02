<?php
/**
 * @package DB
 */

/**
 * ODBC database configuration object.
 * @author John Hargrove <john.hargrove@sellingsource.com>
 */
class DB_ODBCConfig_1 extends Object_1 implements DB_IDatabaseConfig_1
{
	/**
	 * ODBC system dsn
	 *
	 * @var string
	 */
	private $system_dsn;

	/**
	 * ODBC user
	 *
	 * @var string
	 */
	private $user;

	/**
	 * ODBC password
	 *
	 * @var string
	 */
	private $passwd;

	/**
	 * Constructor for ODBC configuration
	 *
	 * @param string $system_dsn ODBC-relevant System DSN
	 * @param string $user ODBC user
	 * @param string $passwd ODBC pass
	 */
	public function __construct($system_dsn, $user = NULL, $passwd = NULL)
	{
		$this->user = $user;
		$this->passwd = $passwd;
		$this->system_dsn = $system_dsn;
	}

	/**
	 * Returns an initialized database connection
	 * @return DB_IConnection_1
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
		return "odbc:$this->system_dsn";
	}

	/**
	 * returns ODBC username associated with this configuration
	 *
	 * @return string
	 */
	public function getUser()
	{
		return $this->user;
	}

	/**
	 * returns ODBC password associated with this configuration
	 *
	 * @return string
	 */
	public function getPasswd()
	{
		return $this->passwd;
	}
}
