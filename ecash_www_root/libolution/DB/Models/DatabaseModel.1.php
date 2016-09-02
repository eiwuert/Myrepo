<?php
	/**
	 * @package DB.Models
	 */

	/**
	 * Abstract base class for all database model types
	 *
	 * @author John Hargrove <john.hargrove@sellingsource.com>
	 */
	abstract class DB_Models_DatabaseModel_1 extends Object_1
	{
		const DB_INST_READ = 1;
		const DB_INST_WRITE = 2;

		/**
		 * Override this method with one that returns an instance to the application's
		 * database connection.
		 *
		 * @param int $db_inst a constant referencing a DB instance alias
		 * @return DB_Database_1
		 */
		public abstract function getDatabaseInstance($db_inst = DB_Models_DatabaseModel_1::DB_INST_WRITE);

		/**
		 * @see DB_Database_1		 *
		 * @param array $where_args
		 * @param bool $named_params
		 * @return string
		 */
		protected static function buildWhere($where_args, $named_params = TRUE, DB_IConnection_1 $db = NULL)
		{
			return DB_Util_1::buildWhereClause(
				$where_args,
				$named_params,
				$db
			);
		}
	}
?>
