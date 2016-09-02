<?php

	require_once 'WritableModel.php';

	/**
	 * @package Ecash.Models
	 */

	class ECash_Models_EventAmountType extends ECash_Models_WritableModel implements ECash_Models_IHasPermanentData 
	{
		public $Company;

		public function getColumns()
		{
			static $columns = array(
				'date_modified', 'date_created', 'active_status',
				'event_type_id', 'name', 'name_short', 'description'
			);
			return $columns;
		}
		public function getPrimaryKey()
		{
			return array('event_amount_type_id');
		}
		public function getAutoIncrement()
		{
			return 'event_amount_type_id';
		}
		public function getTableName()
		{
			return 'event_amount_type';
		}
	}
?>