<?php 
	class ECash_Models_Action extends ECash_Models_WritableModel
	{
		public function getColumns()
		{
			static $columns = array(
				'date_modified', 'date_created', 'action_id', 'name',
				'name_short'
			);
			return $columns;
		}
		public function getPrimaryKey()
		{
			return array('action_id');
		}
		public function getAutoIncrement()
		{
			return 'action_id';
		}
		public function getTableName()
		{
			return 'action';
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
