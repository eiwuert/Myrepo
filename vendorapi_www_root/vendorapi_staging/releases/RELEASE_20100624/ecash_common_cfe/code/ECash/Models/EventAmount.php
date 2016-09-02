<?php

	/**
	 * @package Ecash.Models
	 */
	class ECash_Models_EventAmount extends ECash_Models_WritableModel
	{

		public function getColumns()
		{
			static $columns = array(
				'event_amount_id', 'event_schedule_id', 'transaction_register_id', 'event_amount_type_id',
				'amount', 'application_id', 'num_reattempt', 'company_id', 'date_modified', 'date_created',

			);
			return $columns;
		}
		public function getPrimaryKey()
		{
			return array('event_amount_id');
		}
		public function getAutoIncrement()
		{
			return 'event_amount_id';
		}
		public function getTableName()
		{
			return 'event_amount';
		}
	}
?>
