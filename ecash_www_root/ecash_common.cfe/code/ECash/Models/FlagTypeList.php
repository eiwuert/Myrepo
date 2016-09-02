<?php

	/**
	 * @package Ecash.Models
	 */
	class ECash_Models_FlagTypeList extends ECash_Models_IterativeModel
	{
		public function getClassName()
		{
			return 'ECash_Models_FlagType';
		}
		public function getTableName()
		{
			return 'flag_type';
		}
	}

?>
