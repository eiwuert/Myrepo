<?php

	/**
	 * @package Ecash.Models
	 */
	class ECash_Models_FlagType extends ECash_Models_WritableModel
	{


		public function getColumns()
		{
			static $columns = array(
				'date_modified', 'date_created','active_status', 'flag_type_id', 'name', 'name_short'
			);
			return $columns;
		}
		public function getPrimaryKey()
		{
			return array('flag_type_id');
		}
		public function getAutoIncrement()
		{
			return 'flag_type_id';
		}
		public function getTableName()
		{
			return 'flag_type';
		}
	}
?>