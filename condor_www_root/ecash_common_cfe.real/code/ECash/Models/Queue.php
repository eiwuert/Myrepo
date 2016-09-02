<?php

	/**
	 * @package Ecash.Models
	 */
	class ECash_Models_Queue extends ECash_Models_WritableModel
	{
		public function getColumns()
		{
			static $columns = array(
				'date_modified', 'date_created','queue_id', 'company_id', 'queue_group_id', 'escalate_queue_id',
				'section_id', 'name_short', 'name', 'display_name', 'sort_order', 'control_class',
				'is_system_queue'
			);
			return $columns;
		}
		public function getPrimaryKey()
		{
			return array('queue_id');
		}
		public function getAutoIncrement()
		{
			return 'queue_id';
		}
		public function getTableName()
		{
			return 'n_queue';
		}
	}
?>