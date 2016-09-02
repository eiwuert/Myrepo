<?php

	/**
	 * @package Ecash.Models
	 */
	class ECash_Models_EventTypeList extends ECash_Models_IterativeModel
	{
		public function getClassName()
		{
			return 'ECash_Models_EventType';
		}

		public function getTableName()
		{
			return 'event_type';
		}
	}
?>
