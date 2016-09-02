<?php

/**
 * @package Ecash.Models
 */
class ECash_Models_AgentList extends ECash_Models_IterativeModel
{
	public function getClassName()
	{
		return 'ECash_Models_Agent';
	}

	public function getTableName()
	{
		return 'agent';
	}

	/**
	 * Returns the appropriate agent list for the current agent.
	 * 
	 * This method is overloaded by Impact for their Cross Company admin flag
	 *
	 * @param int $company_id
	 * @param ECash_Models_Agent $agent
	 * @param int $system_id
	 * @return ECash_Models_AgentList
	 */
	public function getAgentList($company_id, ECash_Models_Agent $agent, $system_id)
	{
		return $this->sortedGetBy("login", "ASC", array('system_id' => $system_id))->toList();
	}

	/**
	 * Retrieves an ECash_Models_AgentList sorted using the supplied arguments
	 *
	 * @param string $sort_col - Column to sort by
	 * @param string $sort_dir - Direction to sort in
	 * @param array $where_args
	 * @param array $override_dbs
	 * @return ECash_Models_AgentList
	 */
	public function sortedGetBy($sort_col, $sort_dir, array $where_args, array $override_dbs = NULL)
	{
		$query = "
			select 
				* 
			from 
				agent 
			" . self::buildWhere($where_args) . "
			order by {$sort_col} {$sort_dir}";

		$list = new ECash_Models_AgentList($this->getDatabaseInstance());
//		$list->setOverrideDatabases($override_dbs);
		$list->statement = $list->getDatabaseInstance()->queryPrepared($query, $where_args);
		return $list;
	}


}

?>
