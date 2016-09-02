<?php
/**
 * @package DB
 */

require_once 'libolution/Object.1.php';
require_once 'libolution/DB/IConnection.1.php';
require_once 'libolution/DB/Statement.1.php';

/**
 * Generic database interface class. Wraps PDO.
 * NOTE: in most cases, you should be type-hinting for DB_IConnection_1
 * @author John Hargrove <john.hargrove@sellingsource.com>
 */
class DB_Database_1 extends Object_1 implements DB_IConnection_1
{
	/**
	 * Backtrace info settings.
	 *
	 */
	const BT_INFO_SUFFIX = 1;
	const BT_INFO_PREFIX = 2;

	/**
	 * DSN of the current connection
	 * @var string
	 */
	protected $dsn = NULL;

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
	 * Driver options for the current connection
	 * @var array
	 */
	protected $driver_options = NULL;

	/**
	 * Contains underlying PDO object.  This should be the only reference
	 * to this object ever kept.
	 *
	 * @var PDO
	 */
	protected $pdo;

	/**
	 * Flag indicating whether or not to prefix the query with an indicator
	 * of what method made this query.  You'll want this disabled if you're
	 * hurting for CPU on the application side.
	 *
	 * @var bool
	 */
	private $backtrace_info_enabled = FALSE;

	/**
	 * number of items to pull from backtrace
	 *
	 * @var int
	 */
	private $backtrace_info_level = 3;

	/**
	 * Determines how the backtrace is applied to the query.
	 * Currently just at the end or the beginning.
	 *
	 * @var int
	 */
	private $backtrace_info_mode = self::BT_INFO_SUFFIX;

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
	public function __construct($dsn, $user = NULL, $password = NULL, array $driver_options = NULL)
	{
		$this->dsn = $dsn;
		$this->user = $user;
		$this->password = $password;
		$this->driver_options = $driver_options;
		$this->pdo = NULL;
	}

	/**
	 * @return string
	 */
	public function getUser()
	{
		return $this->user;
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
	public function getDSN()
	{
		return $this->dsn;
	}

	/**
	 * @return array
	 */
	public function getDriverOptions()
	{
		return $this->driver_options;
	}

	/**
	 * @return bool
	 */
	public function getBacktraceInfoEnabled()
	{
		return $this->backtrace_info_enabled;
	}

	/**
	 * @param bool $value
	 */
	public function setBacktraceInfoEnabled($value)
	{
		$this->backtrace_info_enabled = $value;
	}

	/**
	 * @return int
	 */
	public function getBacktraceInfoLevel()
	{
		return $this->backtrace_info_level;
	}

	/**
	 * @param int $value
	 * @return void
	 */
	public function setBacktraceInfoLevel($value)
	{
		$this->backtrace_info_level = $value;
	}

	/**
	 * @return int
	 */
	public function getBacktraceInfoMode()
	{
		return $this->backtrace_info_mode;
	}

	/**
	 * @param int $value
	 * @return void
	 */
	public function setBacktraceInfoMode($value)
	{
		$this->backtrace_info_mode = $value;
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
		return ($this->pdo !== NULL);
	}

	/**
	 * Connects to the configured database selected
	 * @return void
	 */
	public final function connect()
	{
		if ($this->pdo !== NULL)
		{
			$this->pdo = NULL;
		}

		$this->pdo = new PDO(
			$this->dsn,
			$this->user,
			$this->password,
			$this->driver_options
		);

		if (!$this->pdo instanceof PDO)
		{
			throw new Exception('Failed to create PDO object');
		}

		$this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$this->pdo->setAttribute(PDO::ATTR_STATEMENT_CLASS, array('DB_Statement_1', array()));
		//$this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
	}

	/**
	 * Disconnects from the database
	 * This will destroy the underlying PDO object.
	 * @return void
	 */
	public final function disconnect()
	{
		if ($this->pdo !== NULL)
		{
			$this->pdo = NULL;
		}
	}

	/**
	 * __call magic method.  This will be called anytime
	 * an undefined public method is called.  We use this to pass
	 * through to the underlying PDO object in almost all cases.
	 *
	 * @param string $method
	 * @param array $params
	 * @return mixed
	 */
	public final function __call($method, $params)
	{
		if ($this->pdo === NULL)
		{
			$this->connect();
		}

		if (method_exists($this->pdo, $method))
			return call_user_func_array(array($this->pdo, $method), $params);

		throw new BadMethodCallException("Attempt to call non-existent method, ".$method);
	}

	/**
	 * Called prior to serialization, this closes the PDO connection
	 * Note that there is no __wakeup; that's because the PDO connection
	 * will automatically be reinstated if necessary
	 * @return array
	 */
	public function __sleep()
	{
		$this->disconnect();

		// @todo Not break descendant classes
		return array(
			'dsn', 'user', 'password',
			'driver_options', 'pdo',
			'backtrace_info_enabled',
			'backtrace_info_level',
			'backtrace_info_mode',
		);
	}

	/**
	 * Executes a query with a result-set
	 *
	 * @param string $query
	 * @return DB_IStatement_1
	 */
	public function query($query, $fetch_style = PDO::FETCH_ASSOC)
	{
		if ($this->pdo === NULL) $this->connect();

		if ($this->backtrace_info_enabled)
		{
			$query = $this->addBacktraceInfo($query);
		}

		return $this->pdo->query($query, $fetch_style);
	}

	/**
	 * Prepares a query for subsequent execution
	 *
	 * @param string $query
	 * @return DB_IStatement_1
	 */
	public function prepare($query)
	{
		if ($this->pdo === NULL) $this->connect();

		if ($this->backtrace_info_enabled)
		{
			$query = $this->addBacktraceInfo($query);
		}

		return $this->pdo->prepare($query);
	}

	/**
	 * Executes a resultset-less query
	 *
	 * @param string $query
	 * @return int
	 */
	public function exec($query)
	{
		if ($this->pdo === NULL) $this->connect();

		if ($this->backtrace_info_enabled)
		{
			$query = $this->addBacktraceInfo($query);
		}

		return $this->pdo->exec($query);
	}

	/**
	 * Returns the last auto-increment ID
	 *
	 * @return int
	 */
	public function lastInsertId()
	{
		if ($this->pdo === NULL) $this->connect();
		return $this->pdo->lastInsertId();
	}

	/**
	 * Begins a database transaction
	 * Overloaded to set $this->in_transaction
	 * @return bool
	 */
	public function beginTransaction()
	{
		if ($this->pdo === NULL)
		{
			$this->connect();
		}

		if (!$this->in_transaction)
			$this->in_transaction = TRUE;

		return $this->pdo->beginTransaction();
	}

	/**
	 * Commits the current transaction
	 * Overloaded to set $this->in_transaction
	 *
	 * @return bool
	 */
	public function commit()
	{
		if ($this->pdo === NULL)
		{
			$this->connect();
		}

		if ($this->in_transaction)
			$this->in_transaction = FALSE;

		return $this->pdo->commit();
	}

	/**
	 * Rolls back the current transaction
	 * Overloaded to set $this->in_transaction
	 *
	 * @return bool
	 */
	public function rollBack()
	{
		if ($this->pdo === NULL)
		{
			$this->connect();
		}

		if ($this->in_transaction)
			$this->in_transaction = FALSE;

		return $this->pdo->rollBack();
	}

	/**
	 * Quotes a string
	 *
	 * @param string $string
	 * @return string
	 */
	public function quote($string)
	{
		if ($this->pdo === NULL)
		{
			$this->connect();
		}

		return $this->pdo->quote($string);
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
		return '`'.$string.'`';
	}

	/**
	 * This function is deprecated
	 *
	 * @param string $query
	 * @param array $prepare_args
	 * @return PDOStatement
	 * @deprecated
	 * @see DB_Util_1::queryPrepared()
	 */
	public function queryPrepared($query, array $prepare_args)
	{
		if ($this->pdo === NULL)
		{
			$this->connect();
		}

		$statement = $this->pdo->prepare($query);
		if ($statement === FALSE)
		{
			throw new PDOException('Unable to prepare query');
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
		$st = $this->queryPrepared($query, $prepare_args);
		return $st->rowCount();
	}

	/**
	 * This function is deprecated
	 *
	 * @param string $query
	 * @param array $prepare_args Arguments to pass to the prepared statement.
	 * @param int $column_number Column number to fetch.
	 * @return mixed
	 * @deprecated
	 * @see DB_Util_1::querySingleValue()
	 */
	public function querySingleValue($query, $prepare_args = NULL, $column_number = 0)
	{
		if ($this->pdo === NULL)
		{
			$this->connect();
		}

		// queryPrepare does it's own statement checking, so
		// we'll only do it for query()
		if ($prepare_args !== NULL)
		{
			$statement = $this->queryPrepared($query, $prepare_args);
		}
		elseif (($statement = $this->query($query)) === FALSE)
		{
			throw new PDOException('Unable to execute query');
		}

		$column = $statement->fetchColumn($column_number);
		$statement->closeCursor();

		return $column;
	}


	/**
	 * This function is deprecated
	 *
	 * @param string $query
	 * @param array $prepare_args Arguments to pass to the prepared statement.
	 * @param int $column_number Column number to fetch
	 * @return array
	 * @deprecated
	 * @see DB_Util_1::querySingleColumn()
	 */
	public function querySingleColumn($query, $prepare_args = NULL, $column_number = 0)
	{
		if ($this->pdo === NULL)
		{
			$this->connect();
		}

		if ($prepare_args !== NULL)
		{
			$statement = $this->queryPrepared($query, $prepare_args);
		}
		elseif (($statement = $this->query($query)) === FALSE)
		{
			throw new PDOException('Unable to execute query');
		}

		$column = $statement->fetchAll(PDO::FETCH_COLUMN, $column_number);
		$statement->closeCursor();

		return $column;
	}

	/**
	 * This function is deprecated
	 *
	 * @param string $query
	 * @param int $fetch_style
	 * @return mixed
	 * @deprecated
	 * @see DB_Util_1::querySingleRow()
	 */
	public function querySingleRow($query, $prepare_args = NULL, $fetch_mode = NULL)
	{
		if ($this->pdo === NULL)
		{
			$this->connect();
		}

		if ($prepare_args !== NULL)
		{
			$statement = $this->queryPrepared($query, $prepare_args);
		}
		elseif (($statement = $this->query($query)) === FALSE)
		{
			throw new PDOException('Unable to execute query');
		}

		if ($fetch_mode !== NULL)
		{
			$row = $statement->fetch($fetch_mode);
		}
		else
		{
			$row = $statement->fetch();
		}

		$statement->closeCursor();

		return $row;
	}

	/**
	 * switches databases
	 *
	 * @param string $database_name
	 * @return void
	 */
	public function selectDatabase($database_name)
	{
		if ($this->pdo === NULL)
		{
			$this->connect();
		}

		$this->pdo->exec("use {$database_name}");
	}

	/**
	 * Adds backtrace info to the query and returns a new query
	 *
	 * @param string $query
	 * @return string
	 */
	protected function addBacktraceInfo($query)
	{
		if ($this->backtrace_info_mode === self::BT_INFO_PREFIX)
		{
			$query = $this->getBacktraceInfo() . "\n" . $query;
		}
		else
		{
			$query .= "\n" . $this->getBacktraceInfo();
		}

		return $query;
	}

	/**
	 * inspects the backtrace until it finds the first non-libolution class
	 * and creates a prefix based on that. This shouldn't be considered a fast
	 * thing to do.
	 *
	 * @return string
	 */
	protected function getBacktraceInfo()
	{
		$backtrace = debug_backtrace();

		while (count($backtrace) && $backtrace[0]['class'] == 'DB_Database_1')
			array_shift($backtrace);

		$info = "/* backtrace info:\n";

		for ($i = 0; $i < $this->backtrace_info_level && $i < count($backtrace); $i++)
		{
			$item = $backtrace[$i];
			$info .= " $i {$item['file']} {$item['line']} {$item['class']}.{$item['function']}()\n";
		}

		$info .= '*/';
		return $info;
	}

	/**
	 * This function is deprecated
	 *
	 * @param array $where_args
	 * @param bool $named_params
	 * @return string
	 * @deprecated
	 * @see DB_Util_1::buildWhereClause()
	 */
	public static function buildWhereClause($where_args, $named_params = TRUE)
	{
		if (count($where_args) > 0)
		{
			if ($named_params)
			{
				$where = array();
				foreach ($where_args as $key => $value)
				{
					$where[] = "$key = :$key";
				}
				return ' where ' . implode(' and ', $where);
			}
			else
			{
				return ' where '.implode(' = ? AND ', array_keys($where_args)).' = ?';
			}
		}
		return '';
	}
}

?>
