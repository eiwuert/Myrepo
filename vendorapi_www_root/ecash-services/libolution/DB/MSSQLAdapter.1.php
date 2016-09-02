<?php
/**
 * @package DB
 */

/**
 * Generic database interface class. Wraps MSSQL_Connect.
 * NOTE: in most cases, you should be type-hinting for DB_IConnection_1
 * @author Richard Bunce <richard.bunce@sellingsource.com>
 */
class DB_MSSQLAdapter_1 extends Object_1 implements DB_IConnection_1
{

	/**
	 * server of the current connection
	 * @var string
	 */
	protected $server = NULL;

	/**
	 *the current db
	 * @var string
	 */
	protected $db = NULL;

	/**
	 *the current db port
	 * @var string
	 */
	protected $port = NULL;

	/**
	 * Username for the current connection
	 * @var string
	 */
	protected $user = NULL;

	/**
	 * Password for the current connection
	 * @var strng
	 */
	protected $password = NULL;


	/**
	 * Contains underlying mssql resource. 
	 *
	 * @var Resource
	 */
	protected $mssql_resource;


	/**
	 * Set if the current connection is in the middle of a transaction
	 *
	 * @var boolean
	 */
	private $in_transaction = FALSE;

	/**
	 * Constructor
	 *
	 * @param string $dsn
	 * @param string $user
	 * @param string $password
	 * @param array $driver_options
	 */
	public function __construct($server, $user = NULL, $password = NULL, $db = NULL, $port = 1433)
	{
		$this->server = $server;
		$this->port = $port;
		$this->db = $db;
		$this->user = $user;
		$this->password = $password;
		$this->mssql_resource = NULL;
	}

	/**
	 * @return string
	 */
	public function getUser()
	{
		return $this->user;
	}
	/**
	 * @return resource
	 */
	public function getResource()
	{
		return $this->mssql_resource;
	}
	/**
	 * @return string
	 */
	public function getPassword()
	{
		return $this->password;
	}

	/**
	 * @return string
	 */
	public function getServer()
	{
		return $this->server;
	}


	/**
	 * Indicates whether the database is currently in a transaction
	 *
	 * @return bool
	 */
	public function getInTransaction()
	{
		return $this->in_transaction;
	}

	/**
	 * Returns whether the connection has been established
	 *
	 * @return bool
	 */
	public function getIsConnected()
	{
		return !empty($this->mssql_resource);
	}

	/**
	 * Connects to the configured database selected
	 * @return void
	 */
	public final function connect()
	{
		if (!empty($this->mssql_resource))
		{
			$this->mssql_resource = NULL;
		}

		$this->mssql_resource = mssql_connect(
			$this->server . ':' . $this->port,
			$this->user,
			$this->password
		);
		
		if (empty($this->mssql_resource))
		{
			throw new DB_MSSQLAdapterException_1('Failed to create MSSQL Connection Resource');
		}
		
		$this->selectDatabase($this->db);
	}

	/**
	 * Disconnects from the database
	 * This will destroy the underlying MSSQL resource.
	 * @return void
	 */
	public final function disconnect()
	{
		if ($this->mssql_resource !== NULL)
		{
			mssql_close($this->mssql_resource);
			$this->mssql_resource = NULL;
		}
	}
	
	/**
	 * Checks if the database connection is still alive.
	 * 
	 * If the optional $reconnect parameter is TRUE, it will attempt to reconnect to the database if it fails the ping.
	 *
	 * @param bool $reconnect
	 * @return bool
	 */
	public final function ping($reconnect = FALSE)
	{
		$connected = FALSE;
		
		if (!empty($this->mssql_resource))
		{
			$result = mssql_query('SELECT 1', $this->mssql_resource);
			if($result !== FALSE)	
			{
				$connected = TRUE;
			}
		}
		
		if (!$connected && $reconnect)
		{
			$this->connect();
			$connected = TRUE;
		}
		return $connected;
	}

	/**
	 * Called prior to serialization, this closes the connection
	 * Note that there is no __wakeup; that's because the connection
	 * will automatically be reinstated if necessary
	 * @return array
	 */
	public function __sleep()
	{
		$this->disconnect();

		// @todo Not break descendant classes
		return array(
			'server','port', 'db', 'user', 'password'
		);
	}

	/**
	 * Executes a query with a result-set
	 *
	 * @param string $query
	 * @return DB_IStatement_1
	 */
	public function query($query)
	{
		$this->checkConnection();

		return DB_MSSQLStatementAdapter_1::fromQuery($query, $this->mssql_resource);
	}

	/**
	 * Prepares a query for subsequent execution
	 *
	 * @param string $query
	 * @return DB_IStatement_1
	 */
	public function prepare($query)
	{
		$this->checkConnection();

		return DB_MSSQLStatementAdapter_1::fromPrepare($this, $query);
	}

	/**
	 * Executes a resultset-less query
	 *
	 * @param string $query
	 * @return int
	 */
	public function exec($query)
	{
		$this->checkConnection();

		$statement = mssql_query($query, $this->mssql_resource);
		if($statement === FALSE)
			throw new DB_MSSQLAdapterException_1(mssql_get_last_message() . ": " . $query);
		$rows = mssql_num_rows($statement);
		mssql_free_result($statement);
		return $rows;
	}

	/**
	 * Returns the last auto-increment ID
	 *
	 * @return int
	 */
	public function lastInsertId()
	{
		$this->checkConnection();
		
		$query = 'select SCOPE_IDENTITY() AS last_insert_id';
		$query_result = mssql_query($query, $this->mssql_resource);
		if ($query_result === FALSE)
			throw new DB_MSSQLAdapterException_1(mssql_get_last_message() . ": " . $query);

		$query_result = mssql_fetch_object($query_result);
		if ($query_result === FALSE)
			throw new DB_MSSQLAdapterException_1("Unable to fetch due to error: " . mssql_get_last_message());

		$last_id =  $query_result->last_insert_id;  
		if ($query_result === FALSE)
			throw new DB_MSSQLAdapterException_1("Unable to get last insert ID due to error: " . mssql_get_last_message);

		mssql_free_result($query_result);
		return $last_id;
	}

	/**
	 * Begins a database transaction
	 * Overloaded to set $this->in_transaction
	 * @return bool
	 */
	public function beginTransaction()
	{
		$this->checkConnection();

		$result = mssql_query("BEGIN TRANSACTION", $this->mssql_resource); 
		
		if($result === FALSE)
		{
			throw new DB_MSSQLAdapterException_1("Unable to begin transaction due to error: " . mssql_get_last_message());
		}
		$this->in_transaction = TRUE;
		return TRUE;
		
	}

	/**
	 * Commits the current transaction
	 * Overloaded to set $this->in_transaction
	 *
	 * @return bool
	 */
	public function commit()
	{
		$this->checkConnection();

		$result = mssql_query("COMMIT", $this->mssql_resource); 
		
		if($result === FALSE)
		{
			throw new DB_MSSQLAdapterException_1("Unable to commit transaction due to error: " . mssql_get_last_message());
		}
		$this->in_transaction = FALSE;
		return TRUE;
	}

	/**
	 * Rolls back the current transaction
	 * Overloaded to set $this->in_transaction
	 *
	 * @return bool
	 */
	public function rollBack()
	{
		$this->checkConnection();

		$result = mssql_query("ROLLBACK", $this->mssql_resource); 
		
		if($result === FALSE)
		{
			throw new DB_MSSQLAdapterException_1("Unable to rollback transaction due to error: " . mssql_get_last_message());
		}
		$this->in_transaction = FALSE;
		return TRUE;
	}
	/**
	 * Quotes a string
	 *
	 * @param string $string
	 * @return string
	 */
	protected function mssql_escape($data) {
		if(is_numeric($data)) return $data;
	    
	    $unpacked = unpack('H*hex', $data);
	    return '0x' . $unpacked['hex'];
	}

	/**
	 * Quotes a string
	 *
	 * @param string $string
	 * @return string
	 */
	public function quote($string)
	{
		if (strtotime($string) !== FALSE)
		{
			$quoted_string = "'$string'";
		}
		else
		{
			$quoted_string = $this->mssql_escape($string);
		}
		return $quoted_string;
	}

	/**
	 * Quote a schema object
	 * NOTE: in the future this may have to be expanded for other
	 * database systems; unforuntately, PDO doesn't provide this
	 * in its driver interface. :(
	 *
	 * @param string $string
	 * @return string
	 */
	public function quoteObject($string)
	{
		return '['.$string.']';
	}

	/**
	 * This function is deprecated
	 *
	 * @param string $query
	 * @param array $prepare_args
	 * @return DB_MSSQLStatementAdapter_1
	 * @deprecated
	 * @see DB_Util_1::queryPrepared()
	 */
	public function queryPrepared($query, array $prepare_args)
	{
		$this->checkConnection();
		$statement = $this->prepare($query);
		if ($statement === FALSE)
		{
			throw new DB_MSSQLAdapterException_1(mssql_get_last_message() . ': Unable to prepare query: ' . $query);
		}

		$statement->execute($prepare_args);
		return $statement;
	}

	/**
	 * This function is deprecated
	 *
	 * @param unknown_type $query
	 * @param array $prepare_args
	 * @return unknown
	 * @deprecated
	 * @see DB_Util_1::execPrepared()
	 */
	public function execPrepared($query, array $prepare_args)
	{
		$this->checkConnection();
		$st = $this->queryPrepared($query, $prepare_args);
		return $st->rowCount();
	}

	/**
	 *
	 * @param string $function
	 * @param array $args
	 * @return DB_IStatement_1
	 */
	public function CallStoredProc($function, array $args)
	{
		$this->checkConnection();
		return DB_MSSQLStatementAdapter_1::fromProc($function, $args, $this->mssql_resource);
	}


	/**
	 * switches databases
	 *
	 * @param string $database_name
	 * @return void
	 */
	public function selectDatabase($database_name)
	{
		$this->checkConnection();
		$result = mssql_select_db($database_name, $this->mssql_resource);
		if ($result === FALSE)
		{
			throw new DB_MSSQLAdapterException_1(mssql_get_last_message() . ': Unable to select database: ' . $database_name);
		}
	}
	
	private function checkConnection()
	{
		if (empty($this->mssql_resource)) $this->connect();
	}
}

?>
