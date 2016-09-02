<?php

	// Model of rule_condition
	
	class ECash_CFE_API_ConditionDef extends ECash_Models_CfeRuleCondition
	{
		const OP_EQUALS 	= 'equals';
		const OP_NOTEQUALS 	= 'notequals';
		const OP_GREATER 	= 'greater';
		const OP_LESS 		= 'less';
		
		const TYPE_VARIABLE	= 0;
		const TYPE_SCALAR	= 1;
		
		public $Operator;
		public $Operand1;
		public $Operand2;
		public $SequenceNo;
		

			
		/**
		 * fetches all rows matching the conditions passed in the first parameter
		 *
		 * @param array $where_args
		 * @param array $override_dbs
		 * @return array of CFE_API_ConditionDef
		 */
		public function loadAllBy(array $where_args, array $override_dbs = NULL)
		{
			$retval = null;
			
			$query = "SELECT * FROM cfe_rule_condition" . self::buildWhere($where_args);

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
				'date_modified', 'date_created', 'cfe_rule_condition_id',
				'cfe_rule_id', 'operator', 'operand1', 'operand1_type', 'operand2', 'operand2_type', 'sequence_no'
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
			return array('cfe_rule_condition_id');
		}

		/**
		 * returns the auto_increment field
		 *
		 * @return int
		 */
		public function getAutoIncrement()
		{
			return 'cfe_rule_condition_id';
		}

		/**
		 * returns the table name
		 *
		 * @return string
		 */
		public function getTableName()
		{
			return 'cfe_rule_condition';
		}
	}

?>
