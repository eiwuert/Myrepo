<?php
	/**
	 * Adapter for lib MySQLi_1
	 *
	 * @package DB
	 */

	/**
	 * Translation adapter to allow MySQLi_1 (old lib)
	 * This allows users to interact with the IConnection
	 * dependent classes in libolution.
	 *
	 * @author John Hargrove <john.hargrove@sellingsource.com>
	 */
	class DB_MySQLiAdapter_1 extends Object_1 implements DB_IConnection_1
	{
		/**
		 * @var MySQLi_1
		 */
		private $mysqli;

		/**
		 * @param MySQLi_1 $connection
		 */
		public function __construct(MySQLi_1 $connection)
		{
			$this->mysqli = $connection;
		}

		/**
		 * Returns the underlying MySQLi_1 instance
		 * @return MySQLi_1
		 */
		public function getMySQLi()
		{
			return $this->mysqli;
		}

		/**
		 * Begins a transaction
		 *
		 * @return bool
		 */
		public function beginTransaction()
		{
			return $this->mysqli->Start_Transaction();
		}

		/**
		 * Commits the current transaction
		 *
		 * @return bool
		 */
		public function commit()
		{
			return $this->mysqli->Commit();
		}

		/**
		 * Executes a query and discards the result.
		 *
		 * @param string $query
		 * @return void
		 */
		public function exec($query)
		{
			$this->mysqli->Query($query);
			return $this->mysqli->Affected_Row_Count();
		}

		/**
		 * Executes a query and returns the statement object.
		 *
		 * @param string $query
		 * @return DB_MySQLiStatementAdapter_1
		 */
		public function query($query)
		{
			return DB_MySQLiStatementAdapter_1::fromQuery($this->mysqli, $query);
		}

		/**
		 * Whether or not a transaction is active
		 *
		 * @return bool
		 */
		public function getInTransaction()
		{
			return $this->mysqli->In_Query();
		}

		/**
		 * returns the id of the last insert
		 *
		 * @return int
		 */
		public function lastInsertId()
		{
			return $this->mysqli->Insert_Id();
		}

		/**
		 * Creates a prepared statement using software emulation. This does not provide
		 * a performance advantage, and is only useful for compatibility with libolution products
		 * and prevention of sql injection attacks
		 *
		 * @param string $query
		 * @return DB_MySQLiStatementAdapter_1
		 */
		public function prepare($query)
		{
			return DB_MySQLiStatementAdapter_1::fromPrepare($this, $query);
		}

		/**
		 * Aborts and rolls back the current transaction
		 *
		 * @return mixed
		 */
		public function rollBack()
		{
			return $this->mysqli->Rollback();
		}

		/**
		 * Quotes a string
		 * @param string $string
		 * @return string
		 */
		public function quote($string)
		{
			return "'".$this->mysqli->Escape_String($string)."'";
		}

		/**
		 * Quotes a database object, like a table name
		 * @param string $string
		 * @return string
		 */
		public function quoteObject($string)
		{
			return '`'.$string.'`';
		}
	}

?>