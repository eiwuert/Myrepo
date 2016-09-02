<?php

	/**
	 * @package Ecash.Models
	 */
	class ECash_Models_FollowUpType extends ECash_Models_WritableModel implements ECash_Models_IHasPermanentData 
	{

		public function getColumns()
		{
			static $columns = array(
				'date_modified', 'date_created', 'follow_up_type_id', 'name', 'name_short', 'active_status'
			);
			return $columns;
		}
		public function getPrimaryKey()
		{
			return array('follow_up_type_id');
		}
		public function getAutoIncrement()
		{
			return 'follow_up_type_id';
		}
		public function getTableName()
		{
			return 'follow_up_type';
		}
	}
?>