<?php
	/**
	 * @package DB.Util
	 */

	/**
	 * Utility class to cache information_schema's column and table info, to avoid
	 * multiple trips to the server when they aren't needed.
	 *
	 * @author John Hargrove <john.hargrove@sellingsource.com>
	 *
	 */
	class DB_Util_SchemaCache_1 extends Object_1
	{
		/**
		 * @var DB_IConnection_1
		 */
		protected $database = NULL;

		/**
		 * @var int
		 */
		protected $ttl = 0;

		/**
		 * @var array
		 */
		protected $tables = array();

		/**
		 * @var int
		 */
		protected $tables_last_update = 0;

		/**
		 * @var array
		 */
		protected $columns = array();

		/**
		 * @var int
		 */
		protected $columns_last_update = 0;

		/**
		 * @param DB_IConnection_1 $db Database connection to use
		 * @param int $ttl Cache TTL, in seconds. Default: 5 minutes
		 */
		public function __construct(DB_IConnection_1 $db, $ttl = 300)
		{
			$this->database = $db;
			$this->ttl = $ttl;
		}

		/**
		 * Checks if the given database exists
		 *
		 * @param string $database
		 * @return bool
		 */
		public function databaseExists($database)
		{
			$this->prepareTableCache();
			return isset($this->tables[$database]);
		}

		/**
		 * Checks if the given table exists in the given database
		 *
		 * @param string $database
		 * @param string $table
		 * @return bool
		 */
		public function tableExists($database, $table)
		{
			$this->prepareTableCache();
			return isset($this->tables[$database][$table]);
		}

		/**
		 * Returns an array of the table names in the given database
		 *
		 * @param string $database
		 * @return array
		 */
		public function getTableNames($database)
		{
			$this->prepareTableCache();

			if (!isset($this->tables[$database]))
			{
				throw new Exception('Database does not exist');
			}

			return $this->tables[$database];
		}

		/**
		 * Returns an array of all table names in the given database matching the given regular expression
		 *
		 * @param string $database
		 * @param string $pattern
		 * @return array
		 */
		public function getTableNamesMatching($database, $pattern)
		{
			$this->prepareTableCache();

			if (!isset($this->tables[$database]))
			{
				throw new Exception('Database does not exist');
			}

			return preg_grep($pattern, $this->tables[$database]);
		}

		/**
		 * Checks if the given column exists in the given table in the given .. database.
		 *
		 * @param string $database
		 * @param string $table
		 * @param string $column
		 * @return bool
		 */
		public function columnExists($database, $table, $column)
		{
			$this->prepareColumnCache();
			return isset($this->columns[$database][$table][$column]);
		}

		/**
		 * Returns all column names for the given table
		 *
		 * @param string $database
		 * @param string $table
		 * @return array
		 */
		public function getColumnNames($database, $table)
		{
			$this->prepareColumnCache();

			if (!isset($this->columns[$database][$table]))
			{
				throw new Exception('Database or table does not exist.');
			}

			return array_keys($this->columns[$database][$table]);
		}

		/**
		 * internal function to make sure the cache is up to date
		 * @return void
		 */
		protected function prepareTableCache()
		{
			if ($this->ttl !== NULL
				&& ($this->tables_last_update + $this->ttl) < time())
			{
				$this->tables = array();
				$stmt = $this->database->query("select * from information_schema.TABLES");
				while (($row = $stmt->fetch(PDO::FETCH_OBJ)) !== FALSE)
				{
					if (!isset($this->tables[$row->TABLE_SCHEMA]))
					{
						$this->tables[$row->TABLE_SCHEMA] = array();
					}
					$this->tables[$row->TABLE_SCHEMA][$row->TABLE_NAME] = $row->TABLE_NAME;
				}
				$this->tables_last_update = time();
			}
		}

		/**
		 * internal function to make sure the cache is up to date
		 * @return void
		 */
		protected function prepareColumnCache()
		{
			if ($this->ttl !== NULL
				&& ($this->columns_last_update + $this->ttl) < time())
			{
				$this->columns = array();
				$stmt = $this->database->query("select * from information_schema.COLUMNS");
				while (($row = $stmt->fetch(PDO::FETCH_OBJ)) !== FALSE)
				{
					if (!isset($this->columns[$row->TABLE_SCHEMA]))
					{
						$this->columns[$row->TABLE_SCHEMA] = array();
					}
					if (!isset($this->columns[$row->TABLE_SCHEMA][$row->TABLE_NAME]))
					{
						$this->columns[$row->TABLE_SCHEMA][$row->TABLE_NAME] = array();
					}
					$this->columns[$row->TABLE_SCHEMA][$row->TABLE_NAME][$row->COLUMN_NAME] = $row->COLUMN_NAME;
				}
				$this->columns_last_update = time();
			}
		}
	}
?>