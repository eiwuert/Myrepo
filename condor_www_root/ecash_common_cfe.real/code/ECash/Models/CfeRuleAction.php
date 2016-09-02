<?php

	// Model of rule_action
	
	class ECash_Models_CfeRuleAction extends ECash_Models_WritableModel
	{
		
		
		/**
		 * returns an array of the columns in this table
		 *
		 * @return array
		 */
		public function getColumns()
		{
			static $columns = array(
				'date_modified', 'date_created', 'cfe_rule_action_id',
				'cfe_rule_id', 'cfe_action_id', 'params', 'sequence_no', 'rule_action_type'
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
			return array('cfe_rule_action_id');
		}

		/**
		 * returns the auto_increment field
		 *
		 * @return int
		 */
		public function getAutoIncrement()
		{
			return 'cfe_rule_action_id';
		}

		/**
		 * returns the table name
		 *
		 * @return string
		 */
		public function getTableName()
		{
			return 'cfe_rule_action';
		}
		
	}

?>