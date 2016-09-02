<?php

	require_once 'WritableModel.php';

	/**
	 * @package Ecash.Models
	 */

	class ECash_Models_RuleSetComponent extends ECash_Models_WritableModel
	{
		public $LoanType;

		
		public function getColumns()
		{
			static $columns = array(
				'date_modified', 'date_created', 'active_status',
				'rule_set_id', 'rule_component_id', 'sequence_no'
			);
			return $columns;
		}
		public function getPrimaryKey()
		{
			return array('rule_set_id','rule_component_id');
		}
		
		public function getTableName()
		{
			return 'rule_set_component';
		}
		
		public function getAutoIncrement()
		{
			return null;
		}
	}
?>