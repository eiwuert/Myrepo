<?php

	/**
	 * Libolution interface for database connections
	 * Among other things, this allows the creation of adapters for
	 * older database libraries.
	 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
	 */
	interface DB_IConnection_1
	{
		/**
		 * Runs a query
		 * @param string $query
		 * @return DB_IStatement_1
		 */
		public function query($query);

		/**
		 * Executes a result-less statement
		 *
		 * @param unknown_type $query
		 * @return int
		 */
		public function exec($query);

		/**
		 * Prepares a query
		 * @param string $query
		 * @return DB_IStatement_1
		 */
		public function prepare($query);

		/**
		 * Begins a transaction
		 * @return void
		 */
		public function beginTransaction();

		/**
		 * Indicates whether the connection is in a transaction
		 * @return bool
		 */
		public function getInTransaction();

		/**
		 * Commits the current transaction
		 * @return void
		 */
		public function commit();

		/**
		 * Rollsback the current transaction
		 * @return void
		 */
		public function rollBack();

		/**
		 * Returns the last auto-increment ID
		 * @return int
		 */
		public function lastInsertId();

		/**
		 * Quotes a string according to the connection's requirements
		 *
		 * @param string $string
		 * @return string
		 */
		public function quote($string);

		/**
		 * Quotes a database object (table name, etc.)
		 *
		 * @param string $string
		 */
		public function quoteObject($string);
	}

?>