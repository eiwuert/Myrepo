<?php

	/**
	 * @package Ecash.Models
	 */

	class ECash_Models_Reference_Company extends ECash_Models_Reference_Model
	{
		public $Property;
		public function getColumns()
		{
			static $columns = array(
				'date_modified', 'date_created', 'active_status',
				'company_id', 'name', 'name_short', 'co_entity_type',
				'ecash_process_type', 'property_id'
			);
			return $columns;
		}
		public function getPrimaryKey()
		{
			return array('company_id');
		}
		public function getAutoIncrement()
		{
			return 'company_id';
		}
		public function getTableName()
		{
			return 'company';
		}
		public function getColumnID()
		{
			return 'company_id';
		}

		public function getColumnName()
		{
			return 'name_short';
		}
	}
?>
