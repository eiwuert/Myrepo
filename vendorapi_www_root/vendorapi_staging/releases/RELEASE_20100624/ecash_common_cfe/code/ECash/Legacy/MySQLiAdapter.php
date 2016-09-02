<?php

/**
 * Adapts DB_IConnection_1 to MySQLi_1
 *
 * Don't use unless necessary!
 *
 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
 */
class ECash_Legacy_MySQLiAdapter extends MySQLi_1
{
	/**
	 * @var DB_IConnection_1
	 */
	protected $db;

	/**
	 * @var string
	 */
	protected $schema;

	protected $commit_mode = FALSE;

	public function __construct(DB_IConnection_1 $db, $schema = NULL)
	{
		$this->db = $db;
		$this->schema = $schema;
	}

	public function __destruct()
	{
	}

	public function Get_Thread_Id()
	{
		return DB_Util_1::querySingleValue($this->db, "SELECT CONNECTION_ID()");
	}

	public function Connect()
	{
		return TRUE;
	}

	public function Close()
	{
		return TRUE;
	}

	/**
	 * @brief Wraps the Query() method and always returns the full
	 * result set as a nested array instead of result resources
	 *
	 * @param $query Passed through to Query()
	 *
	 * @param $cache_seconds INTEGER - How many seconds a cached
	 * query should be considered valid
	 *
	 * @param $database Passed through to Query()
	 *
	 * @return array(row_number => array(column_name => value))
	 */
	public function Cache_Query($query, $cache_seconds, $database = NULL)
	{
		return $this->Query($query, $database);
	}

	public function Auto_Commit($mode = TRUE)
	{
		$q = "SET AUTOCOMMIT = ".($mode ? '1' : '0');
		$this->db->exec($q);
		return TRUE;
	}

	/**
	* Returns a boolean indicating whether or not the database change worked
	*
	* @param string $db		The database to change to
	*
	* @access public
	* @return boolean
	*/
	public function Change_Database($db)
	{
		$this->database = $db;
		$this->selectDatabase($db);
		return TRUE;
	}

	protected function selectDatabase($db)
	{
		$this->db->exec('SELECT '.$db);
	}

	/**
	* Returns a boolean indicating whether or not the database is currently
	* in a query transaction.
	*
	* @access public
	* @return boolean
	*/
	public function In_Query()
	{
		return $this->db->getInTransaction();
	}

	/**
	* Queries the connected database. A result set (array) is only returned
	* if you specify to return one.
	*
	* @param string $query		query to send to run
	* @param string $database	optional database to run the query on
	*
	* @example   Returning a result
	*            $db     = new MySQLi_1(...);
	*            $result = $db->Query("SELECT * FROM table");
	*            $row    = $result->Fetch_Object_Row();
	*
	* @access public
	* @throws MySQL_Exception
	* @return MySQLi_Result_1	The MySQLi result object for the query
	*
	*/
	public function Query($query, $database = NULL)
	{
		try
		{
			if ($database) $this->selectDatabase($database);

			$st = $this->db->query($query);
			$this->affected_rows = $st->rowCount();
		}
		catch(Exception $e)
		{
			// if we changed databases, change back
			if ($database) $this->selectDatabase($this->schema);
			throw $e;
		}

		if ($database) $this->selectDatabase($this->schema);
		return new ECash_Legacy_MySQLiResultAdapter($st);;
	}

	/**
	* Gets affected row count for last MySQL operation
	*
	* @access public
	* @return integer
	*/
	public function Affected_Row_Count()
	{
		return $this->affected_rows;
	}

	/**
	* Gets the last insert id
	*
	* @access public
	* @return integer
	*/
	public function Insert_Id()
	{
		return $this->db->lastInsertId();
	}


	/**
	* Begin a MySQL query transaction.
	*
	* @access public
	* @throws MySQL_Exception
	* @return boolean
	*/
	public function Start_Transaction ()
	{
		$this->db->beginTransaction();
	}

	/**
	* Commit the previously-started query transaction and return any
	* data MySQL spits out.
	*
	* @access public
	* @throws MySQL_Exception
	* @return boolean
	*/
	public function Commit()
	{
		$this->db->commit();
	}

	/**
	* Roll back the previously-started query transaction so that no
	* changes are made and return any data MySQL spits out.
	*
	* @access public
	* @return boolean
	*/
	public function Rollback ()
	{
		$this->db->rollBack();
	}

	/**
	* Prepares a query
	*
	* @access public
	* @throws MySQL_Exception
	* @return mysqli_stmt
	*/
	public function Prepare($query)
	{
		// @todo adapt this?
		$st = $this->db->prepare($query);
		return $st;
	}

	public function Get_Link ()
	{
		throw new BadMethodCallException('Adapter cannot return MySQLi instance');
	}

	public function Escape_String ($string)
	{
		return $this->db->quote($string);
	}

	private function Reset ()
	{
		if ($this->db->getInTransaction())
		{
			$this->db->rollBack();
		}
	}

	public function Ping()
	{
		// not available in PDO
		$this->db->exec('SELECT 1');
		return TRUE;
	}

	public function Reset_Connection()
	{
		// do nothing...
	}

	public function Get_Error()
	{
		return '';
	}

	public function Get_Errno()
	{
		return 0;
	}

	/**
	 * Accessor for commit mode
	 *
	 * Added for Jason Schmidt by Justin Foell
	 */
	public function Get_Commit_Mode()
	{
		$mode = DB_Util_1::querySingleValue($this->db, 'SELECT @@autocommit');
		return ($mode === 1);
	}
}

?>