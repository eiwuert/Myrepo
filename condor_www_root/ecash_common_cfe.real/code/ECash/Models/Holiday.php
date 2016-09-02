<?php

	/**
	 * @package Ecash.Models
	 */
	class ECash_Models_Holiday extends ECash_Models_WritableModel
	{


		public function getColumns()
		{
			static $columns = array(
				'date_modified','date_created','active_status','holiday_id','holiday','name'
			);
			return $columns;
		}
		public function getPrimaryKey()
		{
			return array('holiday_id');
		}
		public function getAutoIncrement()
		{
			return 'holiday_id';
		}
		public function getTableName()
		{
			return 'holiday';
		}
	}
?>