<?php
/**
 * This class provides the functionality for working with agent affiliations
 * from the agent's perspective.
 *
 * @package Agent
 * @author  Mike Lively <mike.lively@sellingsource.com>
 */
class ECash_Agent_Affiliations extends ECash_Agent_Component
{
	/**
	 * Reassigns the agent's active affiliations to a new agent.
	 *
	 * Returns the number of affiliations reassigned.
	 * 
	 * @param ECash_Agent $to_agent
	 * @return int
	 */
	public function reassign(ECash_Agent $to_agent)
	{
		$affiliation = ECash::getFactory()->getModel('AgentAffiliation', $this->db);
		return $affiliation->reassign($this->agent_id, $to_agent->AgentId);
	}
	
	public function getAllActive($area, $type)
	{
		$affiliations = ECash::getFactory()->getModel('AgentAffiliationList', $this->db);
		
		/* @var $affiliations ECash_Models_AgentAffiliationList */
		$affiliations->loadByAgentId($this->agent_id, $area, $type);
		
		$affiliation_bos = array();
		foreach ($affiliations as $affiliation)
		{
			/* @var $affiliation ECash_Models_AgentAffiliation */
			$affiliation_bos[] = new ECash_AgentAffiliation($affiliation);
		}
		
		return $affiliation_bos;
	}
}
?>
