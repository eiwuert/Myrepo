<?php

	abstract class ECash_Models_IterativeModel extends DB_Models_IterativeModel_1 implements ECash_Models_IIterativeModel
	{
		/**
		 * @var DB_IConnection_1
		 */
		protected $db;

		/**
		 * @var array
		 */
		protected $order_by = array();

		/**
		 * The limit amount in the sql statement. 0 = No limit
		 * 
		 * @var integer
		 */
		protected $limit = 0;

		public function __construct(DB_IConnection_1 $db)
		{
			$this->db = $db;
		}

		/**
		 * Creates an instance of the class based off of the current database row 
		 * in the iterator.
		 *
		 * @param array $db_row
		 * @return ECash_DB_Models_WritableModel
		 */
		protected function createInstance(array $db_row)
		{
			$model_name = $this->getClassName();
			$model = new $model_name($this->getDatabaseInstance());
			$model->fromDbRow($db_row);

			return $model;
		}
		
		/**
		 * Returns the row that the cursor is currently pointing at.
		 *
		 * @return PDO row
		 */
		protected function current_row()
		{
			if ($this->statement === null)
				throw new Exception("No rows available!");

			return $this->current === false ? null : $this->current;
		}

		/**
		 * Fetches the next item in the database, and updates the cursor
		 *
		 * @return DB_Models_ModelBase
		 */
		protected function next_row()
		{
			if ($this->statement === null)
				throw new Exception("No rows available!");

			$this->current = $this->statement->fetch(PDO::FETCH_ASSOC, PDO::FETCH_ORI_NEXT);

			return $this->current === false ? null : $this->current;
		}

		public static function buildWhereClause($where_args, $named_params = TRUE, DB_IConnection_1 $db = NULL, $include_where = true)
		{
			if (count($where_args) > 0)
			{
				if ($named_params)
				{
					$where = array();
					foreach ($where_args as $key => $value)
					{
						$col = ($db ? $db->quoteObject($key) : $key);
						$where[] = "$col = :$key";
					}
					return ($include_where ? ' where ' : ' and ') . implode(' and ', $where);
				}
				else
				{
					$fields = array_keys($where_args);
					if ($db) array_map(array($db, 'quoteObject'), $fields);
					return ($include_where ? ' where ' : ' and ') . implode(' = ? and ', $fields).' = ?';
				}
			}
			return '';
		}
			
		/**
		 * selects from the model's table based on the where args
		 *
		 * @param array $where_args
		 * @return bool
		 */
		public function loadBy(array $where_args = array())
		{
			$query = 'SELECT * FROM ' . $this->getTableName() . ' ' . self::buildWhere($where_args);

			if (!empty($this->order_by))
			{
				$first = TRUE;
				$query .= ' ORDER BY';

				foreach ($this->order_by as $field => $direction)
				{
					if (!$first)
					{
						$query .= ',';
					}

					$query .= ' ' . $field . ' ' . $direction;

					$first = FALSE;
				}
			}

			/* add the limit to the sql statement */
			if (!empty($this->limit))
			{
				$query .= " LIMIT {$this->limit}";
			}

			$this->statement = DB_Util_1::queryPrepared($this->getDatabaseInstance(), $query, $where_args);
		}

		/**
		 * this loadBy uses the ECash DB Util file to accept a different operator
		 *	in the where clause.
		 *
		 * @example of the call:
		 *  $my_model = ECash::getFactory()->getModel("Mine");
		 *  $my_model->loadByModified(array(
		 *		'date_created' => array('>' => '2009-09-21'
		 *  ));
		 * 
		 * @param array $where_args
		 * @return bool
		 */
		public function loadByModified(array $where_args = array())
		{
			$query = 'SELECT * FROM ' . $this->getTableName() . ' ' . ECash_DB_Util::buildWhereClause($where_args);

			if (!empty($this->order_by))
			{
				$first = TRUE;
				$query .= ' ORDER BY';

				foreach ($this->order_by as $field => $direction)
				{
					if (!$first)
					{
						$query .= ',';
					}

					$query .= ' ' . $field . ' ' . $direction;

					$first = FALSE;
				}
			}

			/* add the limit to the sql statement */
			if (!empty($this->limit))
			{
				$query .= " LIMIT {$this->limit}";
			}

			$this->statement = ECash_DB_Util::queryPrepared($this->getDatabaseInstance(), $query, $where_args);
		}

		/**
		 * Sets the LIMIT of the SQL statement
		 * 
		 * @param integer|string|NULL $limit
		 */
		public function setLimit($limit = NULL)
		{
			$this->limit = $limit;
		}

		/**
		 * The ORDER BY of the SQL statement and the optional LIMIT
		 *
		 * @param array $order_by
		 * @param integer $limit
		 */
		public function orderBy(array $order_by)
		{
			$this->order_by = $order_by;
		}
	
		public function setDatabaseInstance(DB_IConnection_1 $db)
		{
			$this->db = $db;
		}

		public function getDatabaseInstance($db_inst = DB_Models_DatabaseModel_1::DB_INST_WRITE)
		{
			return $this->db;
		}

		
	}

?>
