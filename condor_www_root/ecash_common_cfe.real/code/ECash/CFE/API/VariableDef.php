<?php

	class ECash_CFE_API_VariableDef extends ECash_Models_WritableModel
	{
		const TYPE_BOOL 	= 'bool';
		const TYPE_NUMBER 	= 'number';
		const TYPE_STRING 	= 'string';
		const TYPE_DATE 	= 'date';
		const TYPE_VARIABLE = 'var';
		
		static $Types = array(
			"TYPE_BOOL" => self::TYPE_BOOL,
			"TYPE_NUMBER" => self::TYPE_NUMBER,
			"TYPE_STRING" => self::TYPE_STRING,
			"TYPE_DATE" => self::TYPE_DATE,
			"TYPE_VARIABLE" => self::TYPE_VARIABLE,			
		);
		
		public function __construct($name = null, $type = null, $db)
		{
			parent::__construct($db);
			$this->name = $name;
			$this->type = $type;
		}


		public function loadAllBy(array $where_args, array $override_dbs = NULL, $order_by = 'name asc')
		{
			$retval = null;
			$query = "SELECT * FROM cfe_variable" . self::buildWhere($where_args) . ' order by ' . $order_by;


			if (($rs = $this->getDatabaseInstance(self::DB_INST_READ)->queryPrepared($query, $where_args)) !== FALSE)
			{
				$results = $rs->fetchAll();
				$retval = array();
				foreach($results as $result) {
					$temp = new self(null, null, $this->getDatabaseInstance());
					$temp->fromDbRow($result);
					$retval[] = $temp;
				}
			}
			return $retval;
		}

		/**
		 * getColumns - returns an array of the columns on this table
		 *
		 * @return array
		 */
		public function getColumns()
		{
			static $columns = array(
				'date_modified', 'date_created', 'cfe_variable_id', 'name','name_short', 'type'
			);
			return $columns;
		}

		/**
		 * returns the primary key in an array
		 *
		 * @return array
		 */
		public function getPrimaryKey()
		{
			return array('cfe_variable_id');
		}

		/**
		 * gets the auto_increment field from the database
		 *
		 * @return string
		 */
		public function getAutoIncrement()
		{
			return 'cfe_variable_id';
		}

		/**
		 * gets the table name
		 *
		 * @return string
		 */
		public function getTableName()
		{
			return 'cfe_variable';
		}
	}

?>
