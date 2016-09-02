<?php

	class ECash_Models_FraudApplication extends ECash_Models_WritableModel
	{
		public $FraudRule;
		public $Application;

		public function getColumns()
		{
			static $columns = array(
				'fraud_rule_id', 'application_id'
			);
			return $columns;
		}
		public function getPrimaryKey()
		{
			return array('fraud_rule_id', 'application_id');
		}
		public function getAutoIncrement()
		{
			return null;
		}
		public function getTableName()
		{
			return 'fraud_application';
		}
	}

?>