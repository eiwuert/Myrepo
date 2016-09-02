<?php

	require_once 'WritableModel.php';

	/**
	 * @package Ecash.Models
	 */

	class ECash_Models_EventTransaction extends ECash_Models_WritableModel
	{
		public $Company;

		public function getColumns()
		{
			static $columns = array(
				'date_modified', 'date_created', 'active_status',
				'company_id', 'event_type_id', 'transaction_type_id', 'distribution_percentage',
				'distribution_amount', 'spawn_percentage', 'spawn_amount', 'spawn_max_num'
			);
			return $columns;
		}
		public function getPrimaryKey()
		{
			return array('event_type_id', 'transaction_type_id');
		}
		public function getAutoIncrement()
		{
			return NULL;
		}
		public function getTableName()
		{
			return 'event_transaction';
		}
	}
?>