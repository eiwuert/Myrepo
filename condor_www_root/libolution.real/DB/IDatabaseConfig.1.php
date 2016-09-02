<?php

	/**
	 * @package DB
	 */

	/**
	 * Database configuration interface
	 * @author John Hargrove <john.hargrove@sellingsource.com>
	 */
	interface DB_IDatabaseConfig_1
	{
		/**
		 * prototype: return a PDO connection
		 * @return DB_IConnection_1
		 */
		public function getConnection();
	}

?>
