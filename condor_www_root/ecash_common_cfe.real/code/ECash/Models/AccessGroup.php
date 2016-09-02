<?php

require_once 'WritableModel.php';

	/**
	 * @package Ecash.Models
	 */

	class ECash_Models_AccessGroup extends ECash_Models_WritableModel
	{		
		public function getColumns()
		{
			static $columns = array(
				'date_modified', 'date_created', 'active_status', 'company_id',
				 'system_id', 'access_group_id', 'name' 
			);
			return $columns;
		}
		public function getPrimaryKey()
		{
			return array('access_group_id');
		}
		public function getAutoIncrement()
		{
			return 'access_group_id';
		}
		public function getTableName()
		{
			return 'access_group';
		}
	}
?>