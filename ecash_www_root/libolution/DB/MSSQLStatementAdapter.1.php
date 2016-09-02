<?php
	/**
	 * Adapter for MSSQL results
	 *
	 * @package DB
	 */

	/**
	 * Adapter class for MSSQL statements to work with libolution classes
	 *
	 * @author Richard Bunce <richard.bunce@sellingsource.com>
	 */
	class DB_MSSQLStatementAdapter_1 extends Object_1 implements IteratorAggregate, DB_IStatement_1
	{
		/**
		 * @var MySQLi_Result_1
		 */
		private $result = NULL;

		/**
		 * @var MySQLi_1
		 */
		private $mssql_resource = NULL;

		/**
		 * @var string
		 */
		private $query = NULL;

		/**
		 * @var DB_EmulatedPrepare_1
		 */
		private $prepared_statement = NULL;

		/**
		 * @var int
		 */
		private $last_affected_row_count = 0;

		/**
		 * @param string $query
		 * @param resource $resource
		 * @return DB_MSSQLStatementAdapter_1
		 */
		public static function fromQuery($query, $resource)
		{
			$adapter = new DB_MSSQLStatementAdapter_1($resource);
			$adapter->runQuery($query);
			return $adapter;
		}

		/**
		 * @param string $function
		 * @param array $args
		 * @param resource $resource
		 * @return DB_MSSQLStatementAdapter_1
		 */
		public static function fromProc($function, $args, $resource)
		{
			$adapter = new DB_MSSQLStatementAdapter_1($resource);
			$adapter->runProc($function, $args);
			return $adapter;
		}
		/**
		 * Executes a Stored Proc
		 *
		 * @param string $function
		 * @param array $args
		 * @return void
		 */
		private function runProc($function, $args)
		{
			$stmt = mssql_init($function, $this->mssql_resource); 
	
			foreach($args as $name => $value_array)
			{	
				mssql_bind($stmt, '@' . $name, &$value_array[0], $value_array[1]); 
			}
			$this->result = mssql_execute($stmt);
			$this->last_affected_row_count = mssql_rows_affected($this->mssql_resource);
		}
		/**
		 * @param DB_MSSQLAdapter_1 $mysqli
		 * @param string $query
		 * @return DB_MSSQLStatementAdapter_1
		 */
		public static function fromPrepare(DB_MSSQLAdapter_1 $mssql_adapter, $query)
		{
			$adapter = new DB_MSSQLStatementAdapter_1($mssql_adapter->getResource());
			$adapter->prepared_statement = new DB_EmulatedPrepare_1($mssql_adapter, $query);
			return $adapter;
		}

		/**
		 * executes the statement
		 *
		 * @param array $args
		 * @return void
		 */
		public function execute(array $args = NULL)
		{
			if ($this->prepared_statement === NULL)
			{
				throw new DB_MSSQLAdapterException_1("No statement was prepared.");
			}

			if ($this->result !== NULL)
			{
				mssql_free_result($this->result);
			}

			$query = $this->prepared_statement->getQuery($args);
			$this->runQuery($query);
		}

		/**
		 * Returns a single row from the executed query
		 *
		 * @param int $fetch_mode
		 * @return mixed
		 */
		public function fetch($fetch_mode = DB_IStatement_1::FETCH_ASSOC)
		{
			if ($this->result === NULL)
			{
				throw new DB_MSSQLAdapterException_1("Result not stored.");
			}
			switch ($fetch_mode)
			{
				case DB_IStatement_1::FETCH_ASSOC:
					$row = mssql_fetch_assoc($this->result);
					break;
				case DB_IStatement_1::FETCH_OBJ:
					$row = mssql_fetch_object($this->result);
					break;
				case DB_IStatement_1::FETCH_ROW:
					$row = mssql_fetch_row($this->result);
					break;
				case DB_IStatement_1::FETCH_BOTH:
					$row = mssql_fetch_array($this->result);
					break;
				default:
					throw new DB_MSSQLAdapterException_1("Unsupported fetch type.");
			}
			return ($row === NULL) ? FALSE : $row;
		}

		/**
		 * Returns all remaining rows from the executed query
		 *
		 * @param int $fetch_mode
		 * @return array
		 */
		public function fetchAll($fetch_mode = DB_IStatement_1::FETCH_ASSOC)
		{
			if ($this->result === NULL)
			{
				throw new DB_MSSQLAdapterException_1("Result not stored.");
			}

			$rows = array();

			while (($row = $this->fetch($fetch_mode)) !== FALSE)
			{
				$rows[] = $row;
			}

			return $rows;
		}

		/**
		 * returns the number of rows returned from the last execute, or the rows affected by
		 * the last execute
		 *
		 * @return int
		 */
		public function rowCount()
		{
			if ($this->result === NULL)
			{
				throw new DB_MSSQLAdapterException_1("Result not stored.");
			}

			return $this->last_affected_row_count;
		}

		/**
		 * Gets an external iterator
		 *
		 * @return Iterator
		 */
		public function getIterator()
		{
			return new DB_StatementIterator_1($this);
		}

		/**
		 * @param resource $resource
		 */
		private function __construct($resource)
		{
			$this->mssql_resource = $resource;
		}

		/**
		 * Executes a query, stores the result, and stores the amount of rows
		 * affected or returned by the query
		 *
		 * @param string $query
		 * @return void
		 */
		private function runQuery($query)
		{
			$this->result = mssql_query($query, $this->mssql_resource);
			if($this->result === FALSE)
			{
				throw new DB_MSSQLAdapterException_1(mssql_get_last_message() . ": Unable to run query: " . $query);
			}
			$this->last_affected_row_count = mssql_rows_affected($this->mssql_resource);
		}
	}

?>
