<?php
	class Blackbox_Models_RuleDefinition extends Blackbox_Models_WriteableModel
	{
		public function getColumns()
		{
			static $columns = array(
				'rule_definition_id', 'display_type_id', 'rule_class_id',
				'name', 'name_short', 'description', 'event', 'stat', 'field',
				'default_rule', 'active'
			);
			return $columns;
		}
		public function getPrimaryKey()
		{
			return array('rule_definition_id');
		}
		public function getAutoIncrement()
		{
			return 'rule_definition_id';
		}
		public function getTableName()
		{
			return 'rule_definition';
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