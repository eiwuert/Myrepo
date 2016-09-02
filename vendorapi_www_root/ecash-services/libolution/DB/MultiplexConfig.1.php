<?php

	/**
	 * @package DB
	 */

	/**
	 * A configuration that uses an existing connection, but accesses a separate schema
	 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
	 */
	class DB_MultiplexConfig_1 extends DB_PoolConfig_1
	{
		/**
		 * @var string
		 */
		protected $schema;

		/**
		 * @param string $alias Config pool alias
		 * @param string $schema
		 */
		public function __construct($alias, $schema)
		{
			parent::__construct($alias);
			$this->schema = $schema;
		}

		/**
		 * Gets a connection
		 *
		 * @return DB_IConnection_1
		 */
		public function getConnection()
		{
			$db = parent::getConnection();

			return new DB_MultiplexDatabase_1($db, $this->schema);
		}
	}

?>