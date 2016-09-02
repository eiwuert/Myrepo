<?php

	/**
	 * @package Ecash.Models
	 */
	class ECash_Models_DebtCompanyEventSchedule extends ECash_Models_WritableModel
	{

		public function getColumns()
		{
			static $columns = array(
				'company_id', 'event_schedule_id'
			);
			return $columns;
		}
		public function getPrimaryKey()
		{
			return array('event_schedule_id');
		}
		public function getAutoIncrement()
		{
			return 'event_schedule_id';
		}
		public function getTableName()
		{
			return 'debt_company_event_schedule';
		}
	}
?>
