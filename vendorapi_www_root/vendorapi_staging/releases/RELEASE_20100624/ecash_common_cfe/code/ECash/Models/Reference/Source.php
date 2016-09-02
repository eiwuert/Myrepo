<?php

	/**
	 * @package Ecash.Models
	 */

	class ECash_Models_Reference_Source extends DB_Models_ReferenceModel_1
	{
		public function getColumns()
		{
			static $columns = array(
				'date_modified', 'date_created', 'source_ref_id', 'name',
				'name_short'
			);
			return $columns;
		}
		public function getPrimaryKey()
		{
			return array('source_ref_id');
		}
		public function getAutoIncrement()
		{
			return 'source_ref_id';
		}
		public function getTableName()
		{
			return 'source';
		}
		
		public function getColumnID()
		{
			return 'source_ref_id';
		}

		public function getColumnName()
		{
			return 'name_short';
		}
	}
?>