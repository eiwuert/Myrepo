<?php

	/**
	 * @package Ecash.Models
	 */

	class ECash_Models_Reference_System extends DB_Models_ReferenceModel_1
	{
	public function getColumns()
		{
	    static $columns = array(
			'date_modified', 'date_created', 'active_status', 'system_id',
			'name', 'name_short'
			);
			return $columns;
		}
		public function getPrimaryKey()
		{
			return array('system_id');
		}
		public function getAutoIncrement()
		{
			return 'system_id';
		}
		public function getTableName()
		{
			return 'system';
		}
		
		public function getColumnID()
		{
			return 'system_id';
		}

		public function getColumnName()
		{
			return 'name_short';
		}
	}
?>