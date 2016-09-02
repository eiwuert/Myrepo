<?php

class ECash_Models_AgentAffiliationList extends ECash_Models_IterativeModel
{
	public function getClassName()
	{
		return 'ECash_Models_AgentAffiliation';
	}
	
	public function getTableName()
	{
		return 'agent_affiliation';
	}
	
	public function loadByAgentId($agent_id, $area, $type)
	{
		$where_args = array(
			'agent_id' => $agent_id,
			'affiliation_area' => $area,
			'affiliation_type' => $type
		);
		$where_clause = DB_Util_1::buildWhereClause($where_args);
		$query = "
			SELECT *
			FROM agent_affiliation
			{$where_clause}
		";
			
		$this->statement = DB_Util_1::queryPrepared(
			$this->getDatabaseInstance(),
			$query,
			$where_args
		);
	}
}

?>