<?php
	/**
	 * @package DB.Models
	 */

	/**
	 * Represents a writable database row
	 *
	 * @author John Hargrove <john.hargrove@sellingsource.com>
	 */
	abstract class DB_Models_WritableModel_1 extends DB_Models_DatabaseModel_1 implements DB_Models_IWritableModel_1
	{
		/**
		 * @var DB_IConnection_1
		 */
		protected $db;

		/**
		 * @var array
		 */
		protected $column_data = array();

		/**
		 * @var array
		 */
		protected $altered_columns = array();

		/**
		 * @var bool
		 */
		protected $is_stored = FALSE;

		/**
		 * @var bool
		 */
		protected $is_deleted = FALSE;

		/**
		 * @var int
		 */
		protected $affected_row_count;

		/**
		 * @var bool
		 */
		private $is_readonly = FALSE;

		/**
		 * default ctor
		 *
		 */
		public function __construct(DB_IConnection_1 $db = NULL)
		{
			if ($db) $this->db = $db;

			foreach ($this->getColumns() as $column_name)
			{
				$this->column_data[$column_name] = NULL;
			}
			$this->is_stored = FALSE;
		}

		/**
		 * Returns the active database connection
		 *
		 * @param int $db_inst
		 * @return DB_IConnection_1
		 */
		public function getDatabaseInstance($db_inst = DB_Models_DatabaseModel_1::DB_INST_WRITE)
		{
			if (!$this->db)
			{
				throw new Exception('No database instance');
			}
			return $this->db;
		}

		/**
		 * Whether or not this row has been stored.
		 *
		 * @return bool
		 */
		public function isStored()
		{
			return $this->is_stored;
		}

		/**
		 * Whether this row has been altered.
		 *
		 * @return bool
		 */
		public function isAltered()
		{
			return (count($this->altered_columns) !== 0) || $this->is_deleted;
		}
		
		/**
		 * Creates a new ready to be inserted model.
		 * 
		 * Any delete flags will be removed, all data will be marked as altered
		 * and if there is an auto increment column it will be set to NULL. Any
		 * call to save on the resultant object will result in the the model 
		 * being reinserted with a new id.
		 *
		 * @return DB_Models_WritableModel_1
		 */
		public function copy()
		{
			$new_model = clone $this;
			
			$auto_increment = $this->getAutoIncrement();
			if (!empty($auto_increment) && (!empty($new_model->column_data[$auto_increment])))
			{
				$new_model->column_data[$auto_increment] = NULL;
			}
			
			$new_model->is_stored = FALSE;
			$new_model->altered_columns = $new_model->column_data;
			$new_model->is_deleted = FALSE;
			
			return $new_model;
		}

		/**
		 * Writes data to the database
		 * @return int
		 */
		public function save()
		{
			$this->affected_row_count = NULL;

			if ($this->is_deleted)
			{
				return $this->delete();
			}
			else if ($this->is_stored)
			{
				return $this->update();
			}
			else
			{
				return $this->insert();
			}
		}

		/**
		 * Indicates whether the given data contains a complete primary key
		 *
		 * If $require_auto_inc is FALSE, then the auto increment column is not
		 * required to be present (eg. for inserting).
		 *
		 * @param array $column_data
		 * @param bool $require_auto_inc
		 * @return bool
		 */
		protected function hasCompleteKey(array $column_data, $require_auto_inc = FALSE)
		{
			$auto_inc = $this->getAutoIncrement();

			foreach ($this->getPrimaryKey() as $key)
			{
				if (($key != $auto_inc || $require_auto_inc)
					&& $column_data[$key] === NULL)
				{
					return FALSE;
				}
			}

			return TRUE;
		}

		/**
		 * Returns TRUE if we have enough data to successfully insert.
		 *
		 * @param array $column_data
		 * @return bool
		 */
		protected function canInsert(array $column_data = NULL)
		{
			// allow this to be passed as NULL for compatibility
			if ($column_data === NULL)
			{
				$column_data = $this->getColumnData();
			}
			return $this->hasCompleteKey($column_data);
		}

		/**
		 * @var int
		 */
		protected $insert_mode = self::INSERT_STANDARD;

		/**
		 * Override the default insert method. Normally, the type of insert performed is
		 * INSERT.
		 *
		 * DB_Models_WritableModel_1::INSERT_STANDARD : INSERT
		 * DB_Models_WritableModel_1::INSERT_DELAYED : INSERT DELAYED
		 * DB_Models_WritableModel_1::INSERT_IGNORE : INSERT IGNORE
		 *
		 * @param int $mode
		 * @return void
		 */
		public function setInsertMode($mode = self::INSERT_STANDARD)
		{
			$this->insert_mode = $mode;
		}

		/**
		 * Inserts current row data into the database.
		 *
		 * NOTE: Currently you have the possibility of getting incorrect
		 * auto increment IDs if you have addition unique indexes and use
		 * any of the non-vanilla insert modes. Because there is no
		 * straight-forward, catch-all approach to solving the potential
		 * issues, the onus is on you to handle them as best suits
		 * your application. Here are some suggestions:
		 *
		 *   - INSERT ... ON DUPLICATE KEY UPDATE: if insert() returns an
		 *      affected row count of 0 or 2(?!), you should run a loadBy
		 *      on your unique index to fetch the proper ID
		 *   - INSERT DELAYED: you'll never get an auto increment ID, so
		 *      don't use this if you need to get the ID back immediately
		 *   - INSERT IGNORE: if insert() returns an affected row count
		 *      of 0, loadBy on the unique index to get the ID
		 *
		 * @throws Exception
		 * @return void
		 */
		public function insert()
		{
			$column_data = $this->getColumnData();

			if ($this->is_readonly)
			{
				throw new DB_Models_ReadOnlyException();
			}
			elseif (!$this->canInsert($column_data))
			{
				throw new Exception("Insufficient data supplied for primary key on {$this->getTableName()}.");
			}

			$modified = array_intersect_key($column_data, $this->altered_columns);
			$auto_increment = $this->getAutoIncrement();

			$db = $this->getDatabaseInstance(self::DB_INST_WRITE);

			if ($this->insert_mode === self::INSERT_STANDARD || $this->insert_mode === self::INSERT_ON_DUPLICATE_KEY_UPDATE)
			{
				$query = "INSERT ";
			}
			else if ($this->insert_mode === self::INSERT_DELAYED)
			{
				$query = "INSERT DELAYED ";
			}
			else if ($this->insert_mode === self::INSERT_IGNORE)
			{
				$query = "INSERT IGNORE ";
			}
			else
			{
				throw new Exception("Invalid insert mode specified.");
			}

			$columns = $this->quoteFields(array_keys($modified), $db);
			$query .= "
				INTO " . $db->quoteObject($this->getTableName())."
				(".implode(", ", $columns).")
				VALUES (?".str_repeat(', ?', count($modified) - 1).")
			";

			if ($this->insert_mode === self::INSERT_ON_DUPLICATE_KEY_UPDATE)
			{
				$query .= " ON DUPLICATE KEY UPDATE
				";
				$update_values = array();
				foreach ($columns as $column)
				{
					$update_values[] = "{$column} = VALUES({$column})";
				}
				$query .= implode(",\n", $update_values);
			}

			$st = $db->prepare($query);
			$st->execute(array_values($modified));

			if ($auto_increment !== NULL)
			{
				$id = $db->lastInsertId();
				$this->{$auto_increment} = $id;
			}

			$this->setDataSynched();
			return $this->affected_row_count = $st->rowCount();
		}

		/**
		 * Updates this row in the database using the primary key currently stored.
		 * @todo You can't change a value that's part of the primary key!
		 * @throws Exception
		 * @return void
		 */
		public function update()
		{
			if ($this->is_readonly)
			{
				throw new DB_Models_ReadOnlyException();
			}

			if (count($this->altered_columns))
			{
				$column_data = $this->getColumnData();
				$modified = array_intersect_key($column_data, $this->altered_columns);
				$pk = array_intersect_key($column_data, array_flip($this->getPrimaryKey()));

				$db = $this->getDatabaseInstance(self::DB_INST_WRITE);

				$query = "
					UPDATE " . $db->quoteObject($this->getTableName()) . "
					SET ".implode(" = ?, ", $this->quoteFields(array_keys($modified), $db))." = ?
					WHERE ".implode(" = ? AND ", $this->quoteFields(array_keys($pk), $db))." = ?
				";

				$st = $db->prepare($query);
				$st->execute(
					array_merge(
						array_values($modified),
						array_values($pk)
					)
				);

				$this->setDataSynched();
				return $this->affected_row_count = $st->rowCount();
			}
		}

		/**
		 * Deletes the corresponding row in the database
		 * @throws Exception if the primary key isn't completely set
		 * @return void
		 */
		public function delete()
		{
			if ($this->is_readonly)
			{
				throw new DB_Models_ReadOnlyException();
			}

			$key = $this->getPrimaryKey();
			$pk = array_intersect_key($this->getColumnData(), array_flip($key));

			if (!$this->hasCompleteKey($pk, TRUE))
			{
				throw new Exception("Attempting to perform a delete on an object with no primary key.");
			}

			$db = $this->getDatabaseInstance(self::DB_INST_WRITE);

			$query = '
				DELETE FROM ' . $db->quoteObject($this->getTableName()) . '
				WHERE
					'.implode(' = ? AND ', $this->quoteFields(array_keys($pk), $db)).' = ?
			';
			$st = $db->prepare($query);
			$st->execute(array_values($pk));

			//a little different than setDataSynched() (next save will insert)
			$this->altered_columns = array();
			$this->is_stored = FALSE;
			$this->is_deleted = FALSE;

			return $this->affected_row_count = $st->rowCount();
		}

		/**
		 * Magic setter.
		 *
		 * @param string $name
		 * @param mixed $value
		 * @return void
		 */
		public function __set($name, $value)
		{
			if ($this->is_readonly)
			{
				throw new DB_Models_ReadOnlyException();
			}
			elseif (!in_array($name, $this->getColumns()))
			{
				throw new Exception("'$name' is not a valid column for table '".$this->getTableName()."'.");
			}

			if ($this->column_data[$name] !== $value)
			{
				$this->column_data[$name] = $value;
				$this->altered_columns[$name] = $name;
			}
		}

		/**
		 * Magic getter!
		 *
		 * @param string $name
		 * @return mixed
		 */
		public function __get($name)
		{
			if (!isset($this->column_data[$name]))				
			{
				if(!in_array($name, $this->getColumns()))
				{
					throw new Exception("'$name' is not a valid column for table '".$this->getTableName()."'.");
				}
				else
				{
					return NULL;
				}
			}
			return $this->column_data[$name];
		}

		/**
		 * Magic issetter!
		 *
		 * @param string $name
		 * @return bool
		 */
		public function __isset($name)
		{
			return isset($this->column_data[$name]);
		}

		/**
		 * Unsets a column value
		 *
		 * @param string $name
		 * @return void
		 */
		public function __unset($name)
		{
			if (isset($this->{$name}))
			{
				$this->{$name} = NULL;
			}
		}

		/**
		 * Takes an associative array (presumably from a database select)
		 * and loads the current object with the data.
		 *
		 * @throws Exception
		 * @param array $db_row Associative data array
		 * @param string $column_prefix prefix (if any) to the column names in the array
		 * @return void
		 */
		public function fromDbRow(array $db_row, $column_prefix = '')
		{
			if ($this->is_readonly)
			{
				throw new DB_Models_ReadOnlyException();
			}

			$column_data = array();
			foreach ($this->getColumns() as $column_name)
			{
				$column_data[$column_name] = isset($db_row[$column_prefix.$column_name]) ? $db_row[$column_prefix.$column_name] : NULL;
			}
			$this->setColumnData($column_data);
			$this->setDataSynched();
		}

		/**
		 * Helper to quote an array of field names
		 *
		 * @param array $fields
		 */
		protected function quoteFields(array $fields, DB_IConnection_1 $db = NULL)
		{
			if (!$db) $db = $this->getDatabaseInstance();
			return array_map(array($db, 'quoteObject'), $fields);
		}

		/**
		 * Gets column data ready for the database
		 * NOTE: DO NOT USE.
		 *
		 * @internal
		 * @return array
		 */
		public function getColumnData()
		{
			return $this->column_data;
		}

		/**
		 * Returns current changes to the model
		 * @return array
		 */
		public function getAlteredColumnData()
		{
			$data = $this->getColumnData();
			$altered = array();

			foreach ($this->altered_columns as $col)
			{
				$altered[$col] = $data[$col];
			}

			return $altered;
		}

		/**
		 * Internal method to set data from the database
		 * NOTE: do not use.
		 * @internal
		 * @param array $data
		 * @return void
		 */
		protected function setColumnData($data)
		{
			$this->column_data = $data;
		}

		/**
		 * internal method used to reset the altered columns state
		 * this should be called any time we're sure our data is
		 * identical to what is in the database.
		 *
		 * @internal
		 * @return void
		 */
		public function setDataSynched()
		{
			$this->altered_columns = array();
			$this->is_stored = TRUE;
			$this->is_deleted = FALSE;
		}

		/**
		 * Loads a row by the primary key
		 *
		 * Takes a variable number of arguments... Note that the
		 * of arguments to this function MUST be in the order
		 * returned by getPrimaryKey()
		 *
		 * @param mixed $key
		 * @return bool
		 */
		public function loadByKey($key)
		{
			$key = func_get_args();
			$where = array_combine($this->getPrimaryKey(), $key);

			return $this->loadBy($where);
		}

		/**
		 * selects from the model's table based on the where args
		 *
		 * @param array $where_args
		 * @return bool
		 */
		public function loadBy(array $where_args)
		{
			$db = $this->getDatabaseInstance();

			$query = "
				SELECT *
				FROM " . $db->quoteObject($this->getTableName()) . "
				" . self::buildWhere($where_args, TRUE, $db) . "
				LIMIT 1
			";
			return $this->loadPrepared($db, $query, $where_args);
		}

		/**
		 * Finds all rows matching the given conditions
		 *
		 * @param array $where_args
		 * @return DB_Models_IterativeModel_1
		 */
		public function loadAllBy(array $where_args = array())
		{
			$db = $this->getDatabaseInstance();

			$query = "
				SELECT *
				FROM ".$db->quoteObject($this->getTableName())."
				".self::buildWhere($where_args, TRUE, $db);

			$st = DB_Util_1::queryPrepared(
				$db,
				$query,
				$where_args
			);

			return $this->factoryIterativeModel($st, $db);
		}

		/**
		 * gets the affected row count from the last save().  Should
		 * be NULL if save() was called but nothing changed on the
		 * model
		 *
		 * @return int
		 */
		public function getAffectedRowCount()
		{
			return $this->affected_row_count;
		}

		/**
		 * Sets the readonly state of the model.
		 *
		 * @param boolean $state
		 * @return void
		 */
		public function setReadOnly($state=FALSE)
		{
			$this->is_readonly = $state;
		}

		/**
		 * Gets the readonly state of the model.
		 * @return bool
		 */
		public function getReadOnly()
		{
			return $this->is_readonly;
		}

		/**
		 * Flag this model to be deleted when save() is called
		 *
		 * @param bool $delete
		 * @return void
		 */
		public function setDeleted($delete)
		{
			$this->is_deleted = $delete;
		}

		/**
		 * Gets whether or not this model is scheduled for deletion
		 * @return bool
		 */
		public function getDeleted()
		{
			return $this->is_deleted;
		}

		/**
		 * Executes a "load by" with a prepared query
		 * @param string $query
		 * @param array $args
		 * @return bool
		 */
		protected function loadPrepared(DB_IConnection_1 $db, $query, array $args)
		{
			if ($this->is_readonly)
			{
				throw new DB_Models_ReadOnlyException();
			}

			if (($row = DB_Util_1::querySingleRow($db, $query, $args)) !== FALSE)
			{
				$this->fromDbRow($row);
				return TRUE;
			}
			return FALSE;
		}

		/**
		 * Factories the default iterator for this model
		 *
		 * @param DB_IStatement_1 $st
		 * @return DB_Models_IterativeModel_1
		 */
		protected function factoryIterativeModel(DB_IStatement_1 $st, DB_IConnection_1 $db = NULL)
		{
			if (!$db) $db = $this->getDatabaseInstance();
			return new DB_Models_DefaultIterativeModel_1($db, $st, clone $this);
		}

		/**
		 * Dangerous function written to remove the Vendor API State Object Persistor hack
		 *
		 * @param array $data
		 * @return void
		 */
		public function setModelData(array $data)
		{
			$this->setColumnData($data);
		}
	}

?>
