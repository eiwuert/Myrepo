<?php

	/**
	 * @package Ecash.Models
	 */
	class ECash_Models_AgentAffiliationReason extends ECash_Models_WritableModel
	{

		public function getColumns()
		{
			static $columns = array(
				'date_modified', 'date_created', 'agent_affiliation_reason_id', 'name', 'name_short', 'sort'
			);
			return $columns;
		}
		public function getPrimaryKey()
		{
			return array('agent_affiliation_reason_id');
		}
		public function getAutoIncrement()
		{
			return 'agent_affiliation_reason_id';
		}
		public function getTableName()
		{
			return 'agent_affiliation_reason';
		}
	}
?>