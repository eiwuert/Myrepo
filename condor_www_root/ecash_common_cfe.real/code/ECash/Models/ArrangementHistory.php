<?php 
	class ECash_Models_ArrangementHistory extends ECash_Models_WritableModel
	{
		public function getColumns()
		{
			static $columns = array(
				'date_modified', 'date_created', 'company_id', 'application_id',
				'arrangement_history_id', 'date_payment', 'amount_payment_principal',
				'amount_payment_non_principal', 'agent_id',  'event_type_id',  'event_schedule_id',
			);
			return $columns;
		}
		public function getPrimaryKey()
		{
			return array('arrangement_history_id');
		}
		public function getAutoIncrement()
		{
			return 'arrangement_history_id';
		}
		public function getTableName()
		{
			return 'arrangement_history';
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
