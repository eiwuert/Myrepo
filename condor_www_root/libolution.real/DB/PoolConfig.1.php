<?php

	/**
	 * @package DB
	 */

	/**
	 * A DatabaseConfig that pulls its connections from the DatabaseConfigPool
	 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
	 */
	class DB_PoolConfig_1 implements DB_IDatabaseConfig_1
	{
		/**
		 * @var string
		 */
		protected $alias;

		/**
		 * @param string $alias Config pool alias
		 */
		public function __construct($alias)
		{
			$this->alias = $alias;
		}

		/**
		 * Instantiates a database connection
		 *
		 * @return DB_IConnection_1
		 */
		public function getConnection()
		{
			return DB_DatabaseConfigPool_1::getConnection($this->alias);
		}
	}

?>
