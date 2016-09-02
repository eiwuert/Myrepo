<?php

	require_once 'WritableModel.php';

	/**
	 * @package Ecash.Models
	 */

	class ECash_Models_RuleComponent extends ECash_Models_WritableModel implements ECash_Models_IHasPermanentData 
	{
		public $LoanType;

		
		public function getColumns()
		{
			static $columns = array(
				'date_modified', 'date_created', 'active_status',
				'rule_component_id', 'name', 'name_short', 'grandfathering_enabled'
			);
			return $columns;
		}
		public function getPrimaryKey()
		{
			return array('rule_component_id');
		}
		
		public function getTableName()
		{
			return 'rule_component';
		}
		
		public function getAutoIncrement()
		{
			return 'rule_component_id';
		}
	}
?>