<?php
	/**
	 * @package DB
	 */

	require_once 'libolution/DB/Database.1.php';

	/**
	 * A DB_Database_1 decorator that allows access of multiple schemas over the same connection.
	 * NOTE: you should not make any assumptions about the state of the underlying connection if
	 * you attempt to use it outside of the DB_MultiplexDatabase_1 wrappers.
	 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
	 */
	class DB_MultiplexDatabase_1 implements DB_IConnection_1
	{
		/**
		 * @var DB_Database_1
		 */
		protected $db;

		/**
		 * @var string
		 */
		protected $schema;

		/**
		 * @param DB_Database_1 $db
		 * @param string $schema
		 */
		public function __construct(DB_Database_1 $db, $schema)
		{
			$this->db = $db;
			$this->schema = $schema;
		}

		/**
		 * Potentially dangerous
		 *
		 * @return DB_Database_1
		 */
		public function getDatabase()
		{
			$this->db->selectDatabase($this->schema);
			return $this->db;
		}

		/**
		 * Executes a query and returns the result
		 *
		 * @param string $query
		 * @return DB_IStatement_1
		 */
		public function query($query)
		{
			$this->db->selectDatabase($this->schema);
			return $this->db->query($query);
		}

		/**
		 * Executes a query and indicates whether it succeeded
		 *
		 * @param string $query
		 * @return bool
		 */
		public function exec($query)
		{
			$this->db->selectDatabase($this->schema);
			return $this->db->exec($query);
		}

		/**
		 * Prepares a query for execution
		 *
		 * @param string $query
		 * @return DB_IStatement_1
		 */
		public function prepare($query)
		{
			$this->db->selectDatabase($this->schema);
			return $this->db->prepare($query);
		}

		/**
		 * Indicates whether the connection is in a transaction
		 *
		 * @return bool
		 */
		public function getInTransaction()
		{
			return $this->db->getInTransaction();
		}

		/**
		 * Attempts to begin a database transaction
		 *
		 * @return bool
		 */
		public function beginTransaction()
		{
			return $this->db->beginTransaction();
		}

		/**
		 * Attempts to commit the pending transaction
		 *
		 * @return bool
		 */
		public function commit()
		{
			return $this->db->commit();
		}

		/**
		 * Attempts to rollback the pending transaction
		 *
		 * @return bool
		 */
		public function rollBack()
		{
			return $this->db->rollBack();
		}

		/**
		 * Gets the last insert ID
		 *
		 * @return int
		 */
		public function lastInsertId()
		{
			return $this->db->lastInsertId();
		}

		/**
		 * Quotes a string
		 *
		 * @param string $string
		 * @return string
		 */
		public function quote($string)
		{
			return $this->db->quote($string);
		}
		
		/**
		 * Quotes a schema object
		 *
		 * @param string $string
		 * @return string
		 */
		public function quoteObject($string)
		{
			return $this->db->quoteObject($string);
		}
	}

?>
