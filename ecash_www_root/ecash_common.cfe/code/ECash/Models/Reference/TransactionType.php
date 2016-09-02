<?php
	class ECash_Models_Reference_TransactionType extends DB_Models_ReferenceModel_1
	{
		const STATUS_COMPLETE = 'complete';
		const STATUS_FAILED = 'failed';

		const CLEARING_ACH = 'ach';
		const CLEARING_QUICKCHECK = 'quickcheck';
		const CLEARING_EXTERNAL = 'external';
		const CLEARING_ACCRUED_CHARGE = 'accrued charge'; //no underscore (_) here
		const CLEARING_ADJUSTMENT = 'adjustment';

		const PERIOD_TYPE_BUSINESS = 'business';
		const PERIOD_TYPE_CALENDAR = 'calendar';

		public function getColumns()
		{
			static $columns = array(
				'date_modified', 'date_created', 'active_status',
				'company_id', 'transaction_type_id', 'name_short', 'name',
				'clearing_type', 'pending_period', 'end_status', 'period_type', 
			);
			return $columns;
		}
		public function getPrimaryKey()
		{
			return array('transaction_type_id');
		}
		public function getAutoIncrement()
		{
			return 'transaction_type_id';
		}
		public function getTableName()
		{
			return 'transaction_type';
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
		public function getColumnId()
		{
			return 'transaction_type_id';
		}
		public function getColumnName()
		{
			return 'name_short';
		}
	}
