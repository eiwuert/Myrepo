<?php
	/**
	 * @package DB
	 */

	/**
	 * A statement interface
	 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
	 */
	interface DB_IStatement_1 extends Traversable
	{
		/**
		 * Executes the statement with the given arguments
		 * @param array $args
		 * @return bool
		 */
		public function execute(array $args = NULL);

		/**
		 * Fetches a single row
		 * @param int $fetch_mode
		 * @return mixed
		 */
		public function fetch($fetch_mode = self::FETCH_ASSOC);

		/**
		 * Fetches all rows
		 * @param int $fetch_mode
		 * @return array
		 */
		public function fetchAll($fetch_mode = self::FETCH_ASSOC);

		/**
		 * Returns the number of affected rows
		 * @return int
		 */
		public function rowCount();

		const FETCH_ROW = 3;
		const FETCH_ASSOC = 2;
		const FETCH_OBJ = 5;
		const FETCH_BOTH = 4;
	}

?>
