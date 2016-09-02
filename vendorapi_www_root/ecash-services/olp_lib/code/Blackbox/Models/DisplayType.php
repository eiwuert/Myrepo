<?php 
	class Blackbox_Models_DisplayType extends Blackbox_Models_WriteableModel
	{
		public function getColumns()
		{
			static $columns = array(
				'display_type_id', 'name'
			);
			return $columns;
		}
		public function getPrimaryKey()
		{
			return array('display_type_id');
		}
		public function getAutoIncrement()
		{
			return 'display_type_id';
		}
		public function getTableName()
		{
			return 'display_type';
		}
		public function getColumnData()
		{
			$column_data = parent::getColumnData();
			
			return $column_data;
		}		
		public function setColumnData($data)
		{
			$this->column_data = $data;
			
		}
	}
?>