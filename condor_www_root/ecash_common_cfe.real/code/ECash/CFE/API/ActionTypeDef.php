<?php

	// Model of rule_action
	
	class ECash_CFE_API_ActionTypeDef extends ECash_Models_WritableModel
	{

		/**
		 * fetches multiple rows by the conditions passed in the first parameter
		 *
		 * @param array $where_args
		 * @param array $override_dbs
		 * @return array of ECash_CFE_API_ActionTypeDef
		 */
		public function loadAllBy(array $where_args, array $override_dbs = NULL)
		{
			$retval = null;
			$query = "SELECT * FROM cfe_action_type" . self::buildWhere($where_args);


			if (($rs = $this->getDatabaseInstance(self::DB_INST_READ)->queryPrepared($query, $where_args)) !== FALSE)
			{
				$results = $rs->fetchAll();
				$retval = array();
				foreach($results as $result) {
					$temp = new self($this->getDatabaseInstance());
					$temp->fromDbRow($result);
					$retval[] = $temp;
				}
			}
			return $retval;
		}
		
		/**
		 * returns an array of the columns in this table
		 *
		 * @return array
		 */
		public function getColumns()
		{
			static $columns = array(
				'date_modified', 'date_created', 'cfe_action_type_id',
				'name'
			);
			return $columns;
		}

		/**
		 * returns an array of the primary key
		 *
		 * @return array
		 */
		public function getPrimaryKey()
		{
			return array('cfe_action_type_id');
		}

		/**
		 * returns the auto_increment field
		 *
		 * @return int
		 */
		public function getAutoIncrement()
		{
			return 'cfe_action_type_id';
		}

		/**
		 * returns the table name
		 *
		 * @return string
		 */
		public function getTableName()
		{
			return 'cfe_action_type';
		}
	}

?>
