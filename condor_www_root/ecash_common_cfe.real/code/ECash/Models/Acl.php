<?php

require_once 'WritableModel.php';

	/**
	 * @package Ecash.Models
	 */

	class ECash_Models_Acl extends ECash_Models_WritableModel
	{

		
		public function getColumns()
		{
			static $columns = array(
				'date_modified', 'date_created', 'active_status', 'company_id',
				 'access_group_id', 'section_id', 'acl_mask', 'read_only' 
			);
			return $columns;
		}
		public function getPrimaryKey()
		{
			return array('access_group_id','section_id');
		}
		public function getAutoIncrement()
		{
			return null;
		}
		public function getTableName()
		{
			return 'acl';
		}
	}
?>