<?php
	class ECash_Models_Reference_ApplicationFieldAttribute extends DB_Models_ReferenceModel_1
	{
		public function getColumns()
		{
			static $columns = array(
				'application_field_attribute_id', 'field_name',
				'field_description'
			);
			return $columns;
		}
		public function getPrimaryKey()
		{
			return array('application_field_attribute_id');
		}
		public function getAutoIncrement()
		{
			return 'application_field_attribute_id';
		}
		public function getTableName()
		{
			return 'application_field_attribute';
		}
		public function getColumnId()
		{
			return 'application_field_attribute_id';
		}
		public function getColumnName()
		{
			return 'field_name';
		}
	}
