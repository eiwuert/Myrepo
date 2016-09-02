<?php

	class ECash_Models_FraudProposition extends ECash_Models_WritableModel
	{
		public $FraudRule;
		public $Agent;

		public function getColumns()
		{
			static $columns = array(
				'date_modified', 'date_created', 'fraud_proposition_id',
				'fraud_rule_id', 'agent_id', 'question', 'description',
				'quantify', 'file_name', 'file_size', 'file_type',
				'attachment'
			);
			return $columns;
		}

		public function getColumnData()
		{
			$modified = $this->column_data;
			//mysql timestamps
			$modified['date_created'] = date("Y-m-d H:i:s", $modified['date_created']);
			$modified['date_modified'] = date("Y-m-d H:i:s", $modified['date_modified']);

			return $modified;
		}
	
		public function setColumnData($column_data)
		{
			//mysql timestamps
			$column_data['date_created'] = strtotime( $column_data['date_created']);
			$column_data['date_modified'] = strtotime( $column_data['date_modified']);
			$this->column_data = $column_data;
		}

		public function getPrimaryKey()
		{
			return array('fraud_proposition_id');
		}
		public function getAutoIncrement()
		{
			return 'fraud_proposition_id';
		}
		public function getTableName()
		{
			return 'fraud_proposition';
		}
	}
?>