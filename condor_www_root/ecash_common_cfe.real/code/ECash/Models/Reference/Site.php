<?php

	/**
	 * @package Ecash.Models
	 */

	class ECash_Models_Reference_Site extends ECash_Models_Reference_Model
	{
		public function getColumns()
		{
			static $columns = array(
				'date_modified', 'date_created', 'active_status', 'site_id',
				'name', 'license_key'
			);
			return $columns;
		}
		public function getPrimaryKey()
		{
			return array('site_id');
		}
		public function getAutoIncrement()
		{
			return 'site_id';
		}
		public function getTableName()
		{
			return 'site';
		}
		
		public function getColumnID()
		{
			return 'site_id';
		}

		public function getColumnName()
		{
			return 'license_key';
		}
	}
?>
