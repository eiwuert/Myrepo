<?php 
	class Blackbox_Models_RuleCollectionClass extends Blackbox_Models_WriteableModel
	{
		public function getColumns()
		{
			static $columns = array(
				'rule_collection_class_id', 'class'
			);
			return $columns;
		}
		public function getPrimaryKey()
		{
			return array('rule_collection_class_id');
		}
		public function getAutoIncrement()
		{
			return 'rule_collection_class_id';
		}
		public function getTableName()
		{
			return 'rule_collection_class';
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