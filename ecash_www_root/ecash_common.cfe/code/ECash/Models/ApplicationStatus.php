<?php

require_once 'WritableModel.php';

	/**
	 * @package Ecash.Models
	 */

	class ECash_Models_ApplicationStatus extends ECash_Models_WritableModel implements ECash_Models_IHasPermanentData 
	{
		public $ApplicationStatus;
		
		public function getColumns()
		{
			static $columns = array(
				'date_modified', 'date_created', 'active_status', 'application_status_id', 'name',
				'name_short', 'application_status_parent_id', 'level'
			);
			return $columns;
		}
		public function getPrimaryKey()
		{
			return array('application_status_id');
		}
		public function getAutoIncrement()
		{
			return 'application_status_id';
		}
		public function getTableName()
		{
			return 'application_status';
		}
	}
?>