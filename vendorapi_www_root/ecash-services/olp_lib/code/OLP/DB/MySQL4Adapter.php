<?php
require_once('mysql.4.php');

/**
 * A DB_IConnection_1 -> MySQL_4 adapter
 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
 */
class OLP_DB_MySQL4Adapter extends MySQL_4
{
	/**
	 * Does a slight hack to instantiate with an existing connection
	 *
	 * @param DB_Database_1 $db
	 * @return MySQL4Adapter
	 */
	public static function fromConnection(DB_Database_1 $db)
	{
		$adapter = new self(NULL, NULL, NULL);
		$adapter->db = $db;
		return $adapter;
	}

	/**
	 * @var DB_IDatabaseConfig_1
	 */
	protected $config;

	/**
	 * @var DB_Database_1
	 */
	protected $db;

	/**
	 * @var string
	 */
	protected $schema;
	
	/**
	 * The original schema we were on before calling the query.
	 * 
	 * We're assuming by default the original schema is olp, this really is just here so that $schema and
	 * $original_schema aren't the same thing initially.
	 *
	 * @var string
	 */
	protected $original_schema = 'olp';

	/**
	 * @var int
	 */
	protected $affected_rows = 0;

	/**
	 * Constructor
	 *
	 * @param string $host
	 * @param string $user
	 * @param string $password
	 * @param bool $add_trace
	 * @return void
	 */
	public function __construct($host, $user, $password, $add_trace = FALSE)
	{
		$port = 3306;
		
		$m = array();
		if (preg_match('/^(.+?):(\d+)$/', $host, $m))
		{
			$host = $m[1];
			$port = (int)$m[2];
		}
		
		$this->config = new DB_MySQLConfig_1($host, $user, $password, NULL, $port);
	}

	/**
	 * Closes the connection at destruction
	 *
	 * @return void
	 */
	public function __destruct()
	{
	}

	/**
	 * Connects
	 * PConnect is not supported; the flag does nothing
	 *
	 * @param bool $use_pconnect
	 * @return void
	 */
	public function Connect($use_pconnect = FALSE)
	{
		if (!$this->db)
		{
			$this->db = $this->config->getConnection();
		}
		return TRUE;
	}

	/**
	 * Closes the connection
	 *
	 * Don't _actually_ close it, because other code might have
	 * a reference to the real connection.
	 *
	 * @return void
	 */
	public function Close_Connection()
	{
	}

	/**
	 * Currently just returns TRUE.
	 *
	 * @return bool
	 */
	public function Ping()
	{
		return $this->db->ping(TRUE);
	}

	/**
	 * Indicates whether the connection is currently active
	 *
	 * @return bool
	 */
	public function Is_Connected()
	{
		return ($this->db !== NULL);
	}
	
	/**
	 * Selects a database
	 * If the database has already been selected, it won't be selected unless
	 * $force is provided and TRUE
	 *
	 * @param string $db
	 * @param bool $force
	 * @return void
	 */
	public function Select($db, $force = FALSE)
	{
		if ($force || ($db !== $this->schema))
		{
			$this->schema = $db;
			$this->db->query('USE '.$this->schema);
		}
	}
	
	/**
	 * Executes a query and returns a "result"
	 *
	 * @param string $schema
	 * @param string $query
	 * @return int
	 */
	public function Query($schema, $query)
	{
		// If we specify a different schema, switch the database, but store the current one
		if ($schema !== NULL)
		{
			if ($this->schema !== $this->original_schema)
			{
				$result = $this->db->query('SELECT DATABASE()');
				$this->original_schema = $result->fetchColumn(0);
			}
			$this->Select($schema);
		}
		
		try
		{
			$st = $this->db->Query($query);
			$this->affected_rows = $st->rowCount();
		}
		catch (PDOException $e)
		{
			// OLP has multiple locations where it's catching a MySQL_Exception
			throw new MySQL_Exception($e->getMessage());
		}
		
		// Check if we switched schemas and switch back
		if ($this->schema !== $this->original_schema)
		{
			$this->Select($this->original_schema);
		}
		
		return $st;
	}

	/**
	 * Frees a "result"
	 *
	 * @param int $st
	 * @return bool
	 */
	public function Free_Result($st)
	{
		return TRUE;
	}
	
	/**
	 * Gets the number of rows affected by the last query
	 *
	 * @return int
	 */
	public function Affected_Row_Count()
	{
		return $this->affected_rows;
	}
	
	/**
	 * Gets the last insert ID
	 *
	 * @return int
	 */
	public function Insert_Id()
	{
		return $this->db->lastInsertId();
	}

	/**
	 * Gets the number of rows in a "result"
	 *
	 * @param int $st
	 * @return int
	 */
	public function Row_Count($st)
	{
		if (!$st instanceof DB_IStatement_1)
		{
			throw new InvalidArgumentException('Statement must be an instance of DB_IStatement_1');
		}
		return $st->rowCount();
	}

	/**
	 * Fetches a row as an anonymous object
	 *
	 * @param int $st
	 * @return object
	 */
	public function Fetch_Object_Row($st)
	{
		if (!$st instanceof DB_IStatement_1)
		{
			throw new InvalidArgumentException('Statement must be an instance of DB_IStatement_1');
		}
		return $st->fetch(PDO::FETCH_OBJ);
	}

	/**
	 * Fetches a row indexed by column names
	 *
	 * @param int $st
	 * @return array
	 */
	public function Fetch_Array_Row($st)
	{
		if (!$st instanceof DB_IStatement_1)
		{
			throw new InvalidArgumentException('Statement must be an instance of DB_IStatement_1');
		}
		return $st->fetch(PDO::FETCH_ASSOC);
	}

	/**
	 * Fetches a row indexed by numbers
	 *
	 * @param int $st
	 * @return array
	 */
	public function Fetch_Row($st)
	{
		if (!$st instanceof DB_IStatement_1)
		{
			throw new InvalidArgumentException('Statement must be an instance of DB_IStatement_1');
		}
		return $st->fetch(PDO::FETCH_NUM);
	}

	/**
	 * Fetches a single column from the result
	 *
	 * @param int $st
	 * @param int $column
	 * @return mixed
	 */
	public function Fetch_Column($st, $column)
	{
		return $st->fetchColumn((int)$column);
	}

	/**
	 * Gets the underlying DB_IConnection_1 instance
	 *
	 * @return DB_IConnection_1
	 */
	public function getConnection()
	{
		return $this->db;
	}

	/**
	 * Escapes the given string and returns it
	 *
	 * @param string $string the string to be escaped
	 * @return string
	 */
	public function escape($string)
	{
		// This is rather hackish, and I don't like it, but rather than having to modify all existing queries
		// and remove their quotes, this is the easist solution for now.
		// As we move to full PDO style queries, this shouldn't be needed anymore
		return trim($this->db->quote($string), "'");
	}
}
