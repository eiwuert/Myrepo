<?php

require_once 'WritableModel.php';

	/**
	 * @package Ecash.Models
	 */

	class ECash_Models_ApplicationStatusFlat extends ECash_Models_WritableModel
	{
		public $ApplicationStatus;

		
		public function getColumns()
		{
			static $columns = array(
				'application_status_id', 'level0', 'level0_name',
				'active_status', 'level1', 'level2', 'level3', 'level4',
				'level5'
			);
			return $columns;
		}
		public function getPrimaryKey()
		{
			return array();
		}
		public function getAutoIncrement()
		{
			return null;
		}
		public function getTableName()
		{
			return 'application_status_flat';
		}
	}
?>