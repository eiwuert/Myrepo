<?php

require_once 'WritableModel.php';

	/**
	 * @package Ecash.Models
	 */

	class ECash_Models_AgentAccessGroup extends ECash_Models_WritableModel
	{

		
		public function getColumns()
		{
			static $columns = array(
				'date_modified', 'date_created', 'active_status', 'company_id',
				 'agent_id', 'access_group_id' 
			);
			return $columns;
		}
		public function getPrimaryKey()
		{
			return array('agent_id','access_group_id');
		}
		public function getAutoIncrement()
		{
			return null;
		}
		public function getTableName()
		{
			return 'agent_access_group';
		}
	}
?>