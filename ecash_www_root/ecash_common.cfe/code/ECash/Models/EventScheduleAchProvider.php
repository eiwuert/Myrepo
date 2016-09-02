<?php

require_once 'WritableModel.php';

	/**
	 * @package Ecash.Models
	 */

	class ECash_Models_EventScheduleAchProvider extends ECash_Models_WritableModel implements ECash_Models_IHasPermanentData 
	{
		public function getColumns()
		{
			static $columns = array(
			'date_modified', 'date_created', 'event_schedule_ach_provider_id', 'event_schedule_id', 'application_id', 'date_event', 'ach_provider_id', 'active_status', 'agent_id'
			);
			return $columns;
		}
		public function getPrimaryKey()
		{
			return array('event_schedule_ach_provider_id');
		}
		public function getAutoIncrement()
		{
			return 'event_schedule_ach_provider_id';
		}
		public function getTableName()
		{
			return 'event_schedule_ach_provider';
		}
	}
?>
