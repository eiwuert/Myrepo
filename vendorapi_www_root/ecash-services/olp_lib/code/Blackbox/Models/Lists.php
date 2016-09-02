<?php 
	class Blackbox_Models_Lists extends Blackbox_Models_WriteableModel
	{
		public function getColumns()
		{
			static $columns = array(
				'list_id', 'name', 'field_name', 'date_created',
				'date_modified', 'description', 'loan_action',
				'active', 'deleted',
			);
			return $columns;
		}
		public function getPrimaryKey()
		{
			return array('list_id');
		}
		public function getAutoIncrement()
		{
			return 'list_id';
		}
		public function getTableName()
		{
			return 'lists';
		}
		public function getColumnData()
		{
			$column_data = parent::getColumnData();
			$column_data['date_created'] = date('Y-m-d H:i:s', $column_data['date_created']);
			$column_data['date_modified'] = date('Y-m-d H:i:s', $column_data['date_modified']);
			return $column_data;
		}		
		public function setColumnData($data)
		{
			$this->column_data = $data;
			$this->column_data['date_created'] = strtotime($data['date_created']);
			$this->column_data['date_modified'] = strtotime($data['date_modified']);
		}
	}
?>