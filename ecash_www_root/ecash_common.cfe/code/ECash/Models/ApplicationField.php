<?php 
	class ECash_Models_ApplicationField extends ECash_Models_WritableModel
	{
		public $Company;
		public $TableRow;
		public $ApplicationFieldAttribute;
		public $Agent;
		public function getColumns()
		{
			static $columns = array(
				'application_field_id', 'date_modified', 'date_created',
				'company_id', 'table_name', 'column_name', 'table_row_id',
				'application_field_attribute_id', 'agent_id'
			);
			return $columns;
		}
		public function getPrimaryKey()
		{
			return array('application_field_id');
		}
		public function getAutoIncrement()
		{
			return 'application_field_id';
		}
		public function getTableName()
		{
			return 'application_field';
		}
	}
