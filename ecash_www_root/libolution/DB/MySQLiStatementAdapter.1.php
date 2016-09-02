<?php
	/**
	 * Adapter for MySQLi_1 results
	 *
	 * @package DB
	 */

	/**
	 * Adapter class for mysqli statements to work with libolution classes
	 *
	 * @author John Hargrove <john.hargrove@sellingsource.com>
	 */
	class DB_MySQLiStatementAdapter_1 extends Object_1 implements IteratorAggregate, DB_IStatement_1
	{
		/**
		 * @var MySQLi_Result_1
		 */
		private $result = NULL;

		/**
		 * @var MySQLi_1
		 */
		private $mysqli = NULL;

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
		 * @param MySQLi_1 $mysqli
		 * @param string $query
		 * @return DB_MySQLiStatementAdapter_1
		 */
		public static function fromQuery(MySQLi_1 $mysqli, $query)
		{
			$adapter = new DB_MySQLiStatementAdapter_1($mysqli);
			$adapter->runQuery($query);
			return $adapter;
		}

		/**
		 * @param DB_MySQL4Adapter_1 $mysqli
		 * @param string $query
		 * @return DB_MySQLiStatementAdapter_1
		 */
		public static function fromPrepare(DB_MySQLiAdapter_1 $mysqli, $query)
		{
			$adapter = new DB_MySQLiStatementAdapter_1($mysqli->getMySQLi());
			$adapter->prepared_statement = new DB_EmulatedPrepare_1($mysqli, $query);
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
				throw new DB_MySQLiAdapterException_1("No statement was prepared.");
			}

			if ($this->result !== NULL)
			{
				$this->result->Close();
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
				throw new DB_MySQLiAdapterException_1("Result not stored.");
			}
			switch ($fetch_mode)
			{
				case DB_IStatement_1::FETCH_ASSOC:
					$row = $this->result->Fetch_Array_Row(MYSQLI_ASSOC);
					break;
				case DB_IStatement_1::FETCH_OBJ:
					$row = $this->result->Fetch_Object_Row();
					break;
				case DB_IStatement_1::FETCH_ROW:
					$row = $this->result->Fetch_Row();
					break;
				case DB_IStatement_1::FETCH_BOTH:
					$row = $this->result->Fetch_Array_Row(MYSQLI_BOTH);
					break;
				default:
					throw new DB_MySQLiAdapterException_1("Unsupported fetch type.");
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
				throw new DB_MySQLiAdapterException_1("Result not stored.");
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
				throw new DB_MySQLiAdapterException_1("Result not stored.");
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
		 * @param MySQLi_1 $mysqli
		 */
		private function __construct(MySQLi_1 $mysqli)
		{
			$this->mysqli = $mysqli;
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
			$this->result = $this->mysqli->Query($query);
			$this->last_affected_row_count = $this->mysqli->Affected_Row_Count();
		}
	}

?>
