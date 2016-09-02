<?php 
	/**
	 * GeneralLog_Models_GeneralLogBase is the base data model for 
	 * the general log
	 * 
	 * @author Adam Englander <adam.englander@sellingsource.com>
	 */
	class GeneralLog_Models_GeneralLogBase extends DB_Models_WritableModel_1
	{
		/**
		 * Get columns
		 *
		 * @return array
		 */
		public function getColumns()
		{
			static $columns = array(
				'general_log_id', 'date_created', 'general_log_type_id',
				'application_id', 'session_id', 'detail'
			);
			return $columns;
		}
		
		/**
		 * Get labels for columns
		 *
		 * @return array
		 */
		public function getColumnLabels()
		{
			static $labels = array(
				'general_log_id' => 'ID', 'date_created' => 'Created', 'general_log_type_id' => 'Log Type ID',
				'application_id' => 'App ID', 'session_id' => 'Session ID', 'detail' => 'Detail'
			);
			return $labels;
		}
		
		/**
		 * Get primary key coulumns for table
		 *
		 * @return array
		 */
		public function getPrimaryKey()
		{
			return array('general_log_id');
		}

		/**
		 * Get auto increment
		 *
		 * @return string
		 */
		public function getAutoIncrement()
		{
			return 'general_log_id';
		}
		
		/**
		 * Get table name
		 *
		 * @return string
		 */
		public function getTableName()
		{
			return 'general_log';
		}
		
		/**
		 * Get data in associative array
		 *
		 * @param boolean $labels Get labels instead of names
		 * @return array
		 */
		public function getColumnData($labels = FALSE)
		{
			$column_data = parent::getColumnData();
			$column_data['date_created'] = date('Y-m-d H:i:s', $column_data['date_created']);
			if ($labels)
			{
				$new_column_data = array();
				$label_names = self::getColumnLabels();
				
				foreach ($column_data as $name => $value)
				{
					$column_name = (isset($label_names[$name])?$label_names[$name]:$name);
					$new_column_data[$column_name] = $value;
				}
			}
			else
			{
				$new_column_data = $column_data;
			}
			return $new_column_data;
		}		
		
		/**
		 * Set column data
		 *
		 * @param array $data
		 * @return void
		 */
		public function setColumnData($data)
		{
			$this->column_data = $data;
			$this->column_data['date_created'] = strtotime($data['date_created']);
		}

		/**
		 * selects from the model's table based on the where args adn, optionaly, statements
		 *
		 * @param array $where_args
		 * @param array $where_statements OPTIONAL
		 * @return integer
		 */
		public function countBy(array $where_args, array $where_statements = NULL)
		{
			$query = "
				SELECT count(*) as count
				FROM " . $this->getTableName() . "
				" . self::buildWhereStatements($where_statements) . "
				LIMIT 1
			";

			$row = DB_Util_1::querySingleRow($this->getDatabaseInstance(), $query, $where_args);
			return $row['count'];
		}
		
		/**
		 * Build and return where potion of SQL query
		 *
		 * @param array $where_args
		 * @param bool $named_params
		 * @param array $where_statements OPTIONAL
		 * @return string
		 */
		protected static function buildWhereStatements(array $where_statements = NULL)
		{
			$where = 'WHERE '.implode(' AND ',$where_statements);
			return $where;
		}
				
	}
?>