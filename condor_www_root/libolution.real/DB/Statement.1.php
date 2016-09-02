<?php

	/**
	 * @package DB
	 */

	require_once 'libolution/DB/IStatement.1.php';

	/**
	 * A statement class that implements DB_IStatement_1
	 * NOTE: in most cases, you should be type-hinting for DB_IStatement_1
	 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
	 */
	class DB_Statement_1 extends PDOStatement implements DB_IStatement_1
	{
		/**
		 * This needs to be present (and non-public) to make PDO happy
		 */
		private function __construct()
		{
		}

		/**
		 * This has to be present to make PHP happy
		 * Apparently, the way internal classes declare optional parameters
		 * is fundamentally different from user-classes; although the signature is,
		 * in practice, the same, the internal definition of PDOStatement::execute()
		 * does not fulfill the requirements of the DB_IStatement_1 interface
		 *
		 * @param array $args
		 * @return bool
		 */
		public function execute(array $args = NULL)
		{
			// apparently, PDO counts the number of arguments to indicate missing
			// optional parameters, rather than relying on a default value
			$result = ($args !== NULL)
				? parent::execute($args)
				: parent::execute();

			if ($result === FALSE)
			{
				throw new PDOException('Could not execute statement');
			}
			return $result;
		}

		/**
		 * Fetches a single row
		 *
		 * @param int $fetch_mode
		 * @return mixed
		 */
		public function fetch($fetch_mode = DB_IStatement_1::FETCH_ASSOC)
		{
			return parent::fetch($fetch_mode);
		}

		/**
		 * Fetches all rows
		 *
		 * @param int $fetch_mode
		 * @param mixed $a
		 * @return mixed
		 */
		public function fetchAll($fetch_mode = DB_IStatement_1::FETCH_ASSOC, $a = NULL)
		{
			return parent::fetchAll($fetch_mode);
		}
	}

?>
