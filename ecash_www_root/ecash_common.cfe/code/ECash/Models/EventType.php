<?php

	require_once 'WritableModel.php';

	/**
	 * @package Ecash.Models
	 */

	class ECash_Models_EventType extends ECash_Models_WritableModel implements ECash_Models_IHasPermanentData 
	{
		public $Company;

		public function getColumns()
		{
			static $columns = array(
				'date_modified', 'date_created', 'active_status',
				'company_id', 'event_type_id', 'name', 'name_short'
			);
			return $columns;
		}
		public function getPrimaryKey()
		{
			return array('event_type_id');
		}
		public function getAutoIncrement()
		{
			return 'event_type_id';
		}
		public function getTableName()
		{
			return 'event_type';
		}
	}
?>