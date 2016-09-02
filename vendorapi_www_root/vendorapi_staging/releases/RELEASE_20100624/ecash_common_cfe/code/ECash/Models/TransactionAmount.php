<?php
	class ECash_Models_TransactionAmount extends ECash_Models_WritableModel
	{
		public function getColumns()
		{
			static $columns = array(
				'transaction_amount_id', 'transaction_id',
				'transaction_amount_type_id', 'amount',
				'application_id', 'num_reattempt', 'company_id',
				'date_modified', 'date_created'
			);
			return $columns;
		}
		public function getPrimaryKey()
		{
			return array('transaction_amount_id');
		}
		public function getAutoIncrement()
		{
			return 'transaction_amount_id';
		}
		public function getTableName()
		{
			return 'transaction_amount';
		}
		public function getColumnData()
		{
			$column_data = parent::getColumnData();
			$column_data['date_modified'] = date('Y-m-d H:i:s', $column_data['date_modified']);
			$column_data['date_created'] = date('Y-m-d H:i:s', $column_data['date_created']);
			return $column_data;
		}
		public function setColumnData($data)
		{
			$this->column_data = $data;
			$this->column_data['date_modified'] = strtotime($data['date_modified']);
			$this->column_data['date_created'] = strtotime($data['date_created']);
		}
	}
