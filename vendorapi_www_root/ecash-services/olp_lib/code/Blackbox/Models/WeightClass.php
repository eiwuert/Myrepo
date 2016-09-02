<?php 
	class Blackbox_Models_WeightClass extends Blackbox_Models_WriteableModel
	{
		public function getColumns()
		{
			static $columns = array(
				'weight_class_id', 'class'
			);
			return $columns;
		}
		public function getPrimaryKey()
		{
			return array('weight_class_id');
		}
		public function getAutoIncrement()
		{
			return 'weight_class_id';
		}
		public function getTableName()
		{
			return 'weight_class';
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