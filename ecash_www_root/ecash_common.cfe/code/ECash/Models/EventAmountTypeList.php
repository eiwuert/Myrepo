<?php

	/**
	 * @package Ecash.Models
	 */
	class ECash_Models_EventAmountTypeList extends ECash_Models_IterativeModel
	{
		public function getClassName()
		{
			return 'ECash_Models_EventAmountType';
		}

		public function getTableName()
		{
			return 'event_amount_type';
		}
	}
?>
