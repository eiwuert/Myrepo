<?php 
	class ECash_Models_DoNotLoanFlag extends ECash_Models_ObservableWritableModel
	{
		public function getColumns()
		{
			static $columns = array(
				'dnl_flag_id', 'ssn', 'company_id', 'category_id',
				'other_reason', 'explanation', 'active_status', 'agent_id',
				'date_modified', 'date_created'
			);
			return $columns;
		}
		public function getPrimaryKey()
		{
			return array('dnl_flag_id');
		}
		public function getAutoIncrement()
		{
			return 'dnl_flag_id';
		}
		public function getTableName()
		{
			return 'do_not_loan_flag';
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
