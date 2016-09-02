<?php 
	class ECash_Models_ApplicationTagDetails extends ECash_Models_WritableModel
	{
		public function getColumns()
		{
			static $columns = array(
				'tag_id', 'tag_name', 'name', 'description', 'active_status', 'created_date'
			);
			return $columns;
		}
		public function getPrimaryKey()
		{
			return array('tag_id');
		}
		public function getAutoIncrement()
		{
			return 'tag_id';
		}
		public function getTableName()
		{
			return 'application_tag_details';
		}
		public function getColumnData()
		{
			$column_data = parent::getColumnData();
			$column_data['created_date'] = date('Y-m-d H:i:s', $column_data['created_date']);
			return $column_data;
		}		
		public function setColumnData($data)
		{
			$this->column_data = $data;
			$this->column_data['created_date'] = strtotime($data['created_date']);
		}
	}
