<?php

	/**
	 * @package Ecash.Models
	 */

	class ECash_Models_AgentFlagType extends ECash_Models_WritableModel
	{
		public function getColumns()
		{
			static $columns = array(
				'date_modified', 'date_created', 'active_status', 'agent_flag_type_id',
				'name', 'name_short'
			);
			return $columns;
		}
		public function getPrimaryKey()
		{
			return array('agent_flag_type_id');
		}
		public function getAutoIncrement()
		{
			return 'agent_flag_type_id';
		}
		public function getTableName()
		{
			return 'agent_flag_type';
		}
	}
?>
