<?php 
	class Blackbox_Models_TargetDataType extends Blackbox_Models_WriteableModel
	{
		public function getColumns()
		{
			static $columns = array(
				'target_data_type_id', 'name'
			);
			return $columns;
		}
		public function getPrimaryKey()
		{
			return array('target_data_type_id');
		}
		public function getAutoIncrement()
		{
			return 'target_data_type_id';
		}
		public function getTableName()
		{
			return 'target_data_type';
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