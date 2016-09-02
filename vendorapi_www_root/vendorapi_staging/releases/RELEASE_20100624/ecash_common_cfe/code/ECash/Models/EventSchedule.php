<?php

	/**
	 * @package Ecash.Models
	 */
	class ECash_Models_EventSchedule extends ECash_Models_WritableModel
	{

		public function getColumns()
		{
			static $columns = array(
				'date_modified','date_created','company_id','application_id','event_schedule_id','event_type_id','origin_id','origin_group_id','configuration_trace_data','amount_principal','amount_non_principal','event_status','date_event','date_effective','context','source_id','is_shifted'
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
			return 'event_schedule';
		}
	}
?>