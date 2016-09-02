<?php 
	class Blackbox_Models_RuleRevision extends Blackbox_Models_WriteableModel
	{
		public function getColumns()
		{
			static $columns = array(
				'rule_id', 'rule_revision_id', 'date_modified',
				'date_created', 'active'
			);
			return $columns;
		}
		public function getPrimaryKey()
		{
			return array('rule_id', 'rule_revision_id');
		}
		public function getAutoIncrement()
		{
			return 'rule_revision_id';
		}
		public function getTableName()
		{
			return 'rule_revision';
		}
		public function getColumnData()
		{
			$column_data = parent::getColumnData();
			$column_data['date_modified'] = date('Y-m-d H:i:s', $column_data['date_modified']);
			$column_data['date_created'] = date('Y-m-d H:i:s', $column_data['date_created']);
			return $column_data;
		}		
		public function setColumnData($data)
		{
			$this->column_data = $data;
			$this->column_data['date_modified'] = strtotime($data['date_modified']);
			$this->column_data['date_created'] = strtotime($data['date_created']);
		}
	}
?>