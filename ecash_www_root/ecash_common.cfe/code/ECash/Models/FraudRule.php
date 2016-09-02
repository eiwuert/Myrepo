<?php

	class ECash_Models_FraudRule extends ECash_Models_WritableModel
	{
		public $ModifiedAgent;
		public $CreatedAgent;

		public function getColumns()
		{
			static $columns = array(
				'date_modified', 'date_created', 'modified_agent_id',
				'created_agent_id', 'fraud_rule_id', 'active', 'exp_date',
				'rule_type', 'confirmed', 'name', 'comments'
			);
			return $columns;
		}

		public function getColumnData()
		{
			$modified = $this->column_data;
			//mysql timestamps
			$modified['date_created'] = date("Y-m-d H:i:s", $modified['date_created']);

			return $modified;
		}
	
		public function setColumnData($column_data)
		{
			//mysql timestamps
			$column_data['date_created'] = strtotime( $column_data['date_created']);
			$this->column_data = $column_data;
		}
	
		public function getPrimaryKey()
		{
			return array('fraud_rule_id');
		}
		public function getAutoIncrement()
		{
			return 'fraud_rule_id';
		}
		public function getTableName()
		{
			return 'fraud_rule';
		}
	}

?>