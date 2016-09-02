<?php

	/**
	 * @package Ecash.Models
	 */
	class ECash_Models_HolidayList extends ECash_Models_IterativeModel
	{
		public function getClassName()
		{
			return 'ECash_Models_Holiday';
		}

		public function getTableName()
		{
			return 'holiday';
		}

		public function getActive()
		{
			$query = '-- /* SQL LOCATED IN file=' . __FILE__ . ' line=' . __LINE__ . ' method=' . __METHOD__ . " */
							SELECT  holiday
							FROM    holiday
							WHERE   active_status = 'active'";

			$this->statement = $this->getDatabaseInstance()->queryPrepared($query, array());
		
		}
	}
?>
