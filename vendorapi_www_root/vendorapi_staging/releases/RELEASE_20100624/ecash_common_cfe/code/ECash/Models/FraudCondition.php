<?php

	class ECash_Models_FraudCondition extends ECash_Models_WritableModel
	{
		public $FraudRule;
		public $Prototype;

		public function getColumns()
		{
			static $columns = array(
				'fraud_condition_id', 'fraud_rule_id', 'field_name',
				'field_comparison', 'prototype_id', 'field_value'
			);
			return $columns;
		}
		public function getPrimaryKey()
		{
			return array('fraud_condition_id');
		}
		public function getAutoIncrement()
		{
			return 'fraud_condition_id';
		}
		public function getTableName()
		{
			return 'fraud_condition';
		}
	}

?>