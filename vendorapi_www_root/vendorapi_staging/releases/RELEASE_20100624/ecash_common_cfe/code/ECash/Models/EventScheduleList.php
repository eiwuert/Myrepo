<?php

	/**
	 * @package Ecash.Models
	 */
	class ECash_Models_EventScheduleList extends ECash_Models_IterativeModel
	{
		public function getClassName()
		{
			return 'ECash_Models_EventSchedule';
		}

		public function getTableName()
		{
			return 'event_schedule';
		}

	}
?>