<?php 
	class ECash_Models_ApplicationTags extends ECash_Models_WritableModel
	{
		public function getColumns()
		{
			static $columns = array(
				'app_tag_id', 'tag_id', 'application_id', 'created_date'
			);
			return $columns;
		}
		public function getPrimaryKey()
		{
			return array('app_tag_id');
		}
		public function getAutoIncrement()
		{
			return 'app_tag_id';
		}
		public function getTableName()
		{
			return 'application_tags';
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
