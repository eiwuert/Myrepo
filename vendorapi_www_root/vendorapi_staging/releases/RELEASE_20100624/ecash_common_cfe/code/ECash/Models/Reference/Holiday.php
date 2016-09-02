<?php
	class ECash_Models_Reference_Holiday extends DB_Models_ReferenceModel_1
	{
		public function getColumns()
		{
			static $columns = array(
				'date_modified', 'date_created', 'active_status',
				'holiday_id', 'holiday', 'name'
			);
			return $columns;
		}
		public function getPrimaryKey()
		{
			return array('holiday_id');
		}
		public function getAutoIncrement()
		{
			return 'holiday_id';
		}
		public function getTableName()
		{
			return 'holiday';
		}
		public function getColumnData()
		{
			$column_data = $this->column_data;
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
			return 'holiday_id';
		}
		public function getColumnName()
		{
			return 'holiday';
		}
	}
