<?php

	/**
	 * @package Ecash.Models
	 */
	class ECash_Models_ApiPayment extends ECash_Models_WritableModel
	{

		public function getColumns()
		{
			static $columns = array(
				'date_modified', 'date_created','api_payment_id', 'company_id',
				'application_id', 'event_type_id', 'amount', 'date_event', 'active_status',
			);
			return $columns;
		}
		public function getPrimaryKey()
		{
			return array('api_payment_id');
		}
		public function getAutoIncrement()
		{
			return 'api_payment_id';
		}
		public function getTableName()
		{
			return 'api_payment';
		}
	}
?>
