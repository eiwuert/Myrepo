<?php

	/**
	 * @package Ecash.Models
	 */
	class ECash_Models_EventScheduleList extends ECash_Models_IterativeModel
	{
		public function getClassName()
		{
			return 'ECash_Models_EventScheduleList';
		}

		public function createInstance(array $db_row)
		{
			$item = new ECash_Models_EventSchedule();
			$item->fromDbRow($db_row);
			return $item;
		}

	}
?>