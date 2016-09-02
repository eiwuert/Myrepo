<?php

	/**
	 * @package Ecash.Models
	 */
	class ECash_Models_HolidayList extends ECash_Models_IterativeModel
	{
		public function getClassName()
		{
			return 'ECash_Models_HolidayList';
		}

		public function createInstance(array $db_row)
		{
			$item = new ECash_Models_Holiday();
			$item->fromDbRow($db_row);
			return $item;
		}


	}
?>