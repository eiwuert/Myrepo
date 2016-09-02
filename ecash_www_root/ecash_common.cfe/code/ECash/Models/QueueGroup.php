<?php

	/**
	 * @package Ecash.Models
	 */
	class ECash_Models_QueueGroup extends ECash_Models_WritableModel implements ECash_Models_IHasPermanentData 
	{
		public function getColumns()
		{
			static $columns = array(
				'queue_group_id', 'company_id', 'name_short', 'name'
			);
			return $columns;
		}
		public function getPrimaryKey()
		{
			return array('queue_group_id');
		}
		public function getAutoIncrement()
		{
			return 'queue_group_id';
		}
		public function getTableName()
		{
			return 'n_queue_group';
		}
	}
?>