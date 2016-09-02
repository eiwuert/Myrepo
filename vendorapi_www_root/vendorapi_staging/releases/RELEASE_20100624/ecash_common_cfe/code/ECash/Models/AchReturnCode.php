<?php
	/**
	 * @package Ecash.Models
	 */
	class ECash_Models_AchReturnCode extends ECash_Models_WritableModel implements ECash_Models_IHasPermanentData 
	{
		public function getColumns()
		{
			static $columns = array(
				'date_modified', 'date_created','ach_return_code_id', 'name',
				'name_short', 'is_fatal', 'active_status',
			);
			return $columns;
		}
		public function getPrimaryKey()
		{
			return array('ach_return_code_id');
		}
		public function getAutoIncrement()
		{
			return 'ach_return_code_id';
		}
		public function getTableName()
		{
			return 'ach_return_code';
		}
	}
?>
