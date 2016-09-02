<?php 
	class Blackbox_Models_RuleModeType extends Blackbox_Models_WriteableModel
	{
		public function getColumns()
		{
			static $columns = array(
				'rule_mode_type_id', 'name'
			);
			return $columns;
		}
		public function getPrimaryKey()
		{
			return array('rule_mode_type_id');
		}
		public function getAutoIncrement()
		{
			return 'rule_mode_type_id';
		}
		public function getTableName()
		{
			return 'rule_mode_type';
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