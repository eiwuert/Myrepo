<?php

	/**
	 * @package Ecash.Models
	 */

	class ECash_Models_System extends ECash_Models_WritableModel
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
	}
?>
