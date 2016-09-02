<?php

require_once 'WritableModel.php';

	/**
	 * @package Ecash.Models
	 */

	class ECash_Models_ApplicationAchProvider extends ECash_Models_WritableModel implements ECash_Models_IHasPermanentData 
	{
		public function getColumns()
		{
			static $columns = array(
				'date_modified', 'date_created', 'application_id', 'ach_provider_id' 
			);
			return $columns;
		}
		public function getPrimaryKey()
		{
			return array('application_id','ach_provider_id');
		}
		public function getAutoIncrement()
		{
			return null;
		}
		public function getTableName()
		{
			return 'application_ach_provider';
		}
	}
?>