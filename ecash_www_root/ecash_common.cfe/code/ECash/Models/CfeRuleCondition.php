<?php

	// Model of rule_condition
	
	class ECash_Models_CfeRuleCondition extends ECash_Models_WritableModel implements ECash_Models_IHasPermanentData 
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
