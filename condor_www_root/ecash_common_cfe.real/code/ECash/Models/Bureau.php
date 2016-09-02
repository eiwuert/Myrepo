<?php

require_once 'WritableModel.php';

	/**
	 * @package Ecash.Models
	 */

	class ECash_Models_Bureau extends ECash_Models_WritableModel
	{

		public function getColumns()
		{
			static $columns = array(
				'date_created', 'date_modified', 'active_status',
				'bureau_id', 'name', 'name_short', 'url_live', 'port_live',
				'url_test', 'port_test'
			);
			return $columns;
		}
		public function getPrimaryKey()
		{
			return array('bureau_id');
		}
		public function getAutoIncrement()
		{
			return 'bureau_id';
		}
		public function getTableName()
		{
			return 'bureau';
		}
	}
?>