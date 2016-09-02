<?php 
	class Blackbox_Models_ListRevisionValues extends Blackbox_Models_WriteableModel
	{
		public function getColumns()
		{
			static $columns = array(
				'list_id', 'revision_id', 'value_id'
			);
			return $columns;
		}
		public function getPrimaryKey()
		{
			return array('list_id', 'revision_id', 'value_id');
		}
		public function getAutoIncrement()
		{
			return null;
		}
		public function getTableName()
		{
			return 'list_revision_values';
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