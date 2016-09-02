<?php 
	class Blackbox_Models_RuleRelation extends Blackbox_Models_WriteableModel
	{
		public function getColumns()
		{
			static $columns = array(
				'rule_id', 'rule_revision_id', 'child_id'
			);
			return $columns;
		}
		public function getPrimaryKey()
		{
			return array('rule_id', 'rule_revision_id', 'child_id');
		}
		public function getAutoIncrement()
		{
			return null;
		}
		public function getTableName()
		{
			return 'rule_relation';
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