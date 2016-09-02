<?php

	/**
	 * @package Ecash.Models
	 */
	class ECash_Models_SreportDataList extends ECash_Models_IterativeModel
	{
		public function getClassName()
		{
			return 'ECash_Models_SreportData';
		}

		public function getTableName()
		{
			return 'sreport_data';
		}
	}
?>
