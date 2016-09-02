<?php

	/**
	 * @package Ecash.Models
	 */

	class ECash_Models_AgentFlag extends ECash_Models_WritableModel
	{
		public function getColumns()
		{
			static $columns = array(
				'date_created', 'agent_id', 'agent_flag_type_id', 'agent_flag_id'
			);
			return $columns;
		}
		public function getPrimaryKey()
		{
			return array('agent_flag_id');
		}
		public function getAutoIncrement()
		{
			return 'agent_flag_id';
		}
		public function getTableName()
		{
			return 'agent_flag';
		}
	}
?>
