<?php

	require_once 'WritableModel.php';

	/**
	 * @package Ecash.Models
	 */

	class ECash_Models_RuleSetComponentParmValue extends ECash_Models_WritableModel
	{
		public $LoanType;

		public function getColumns()
		{
			static $columns = array(
				'date_modified', 'date_created', 'agent_id',
				'rule_set_id', 'rule_component_id', 'rule_component_parm_id', 'parm_value'
			);
			return $columns;
		}
		public function getPrimaryKey()
		{
			return array('rule_set_id','rule_component_id','rule_component_parm_id');
		}
		
		public function getTableName()
		{
			return 'rule_set_component_parm_value';
		}
		
		public function getAutoIncrement()
		{
			return null;
		}
	}
?>