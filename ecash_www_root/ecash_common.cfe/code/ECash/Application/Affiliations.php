<?php

/**
 * The application affiliation business object.
 * 
 * This class provides th functions to perform application based actions on 
 * agent affiliations.
 *
 * @package Application
 * @author  Mike Lively <mike.lively@sellingsource.com>
 */
class ECash_Application_Affiliations extends ECash_Application_Component
{
	/**
	 * Expire all affiliations of the given type for this application in the 
     * given area.
	 *
	 * @param string $area
	 * @param string $type
	 * @return int The number of affiliations expired
	 */
	public function expire($area, $type)
	{
		$affiliation = ECash::getFactory()->getModel('AgentAffiliation', $this->db);
		return $affiliation->expireAffiliations(array(
			'application_id' => $this->application->getId(),
			'affiliation_area' => $area,
			'affiliation_type' => $type,
		));
	}
	
	/**
	 * Expire all affiliations for this application.
	 *
	 * @return int The number of affiliations expired
	 */
	public function expireAll()
	{
		$affiliation = ECash::getFactory()->getModel('AgentAffiliation', $this->db);
		return $affiliation->expireAffiliations(array(
			'application_id' => $this->application->getId()
		));
	}
	
	
	/**
	 * Adds a new affiliation for this app.
	 *
	 * The expiration date should be given as a mysql time stamp. If NULL is 
	 * given then the affiliation will not automatically expire.
	 *
	 * @param ECash_Agent $agent The agent owning the affiliation.
	 * @param string $area The area of the affiliation.
	 * @param string $type The type of the affiliation.
	 * @param string $date_expiration The date that the affiliation will expire.
	 * @return ECash_AgentAffiliation
	 */
	public function add(ECash_Agent $agent, $area, $type, $date_expiration = NULL)
	{
		$affiliation = ECash::getFactory()->getModel('AgentAffiliation', $this->db);
		$affiliation->company_id = $this->application->getCompanyId();
		$affiliation->application_id = $this->application->getId();
		$affiliation->affiliation_area = $area;
		$affiliation->affiliation_type = $type;
		$affiliation->agent_id = $agent->AgentId;
		$affiliation->date_expiration_actual = $date_expiration;
		$affiliation->affiliation_status = 'active';
		$affiliation->date_created = date('Y-m-d H:i:s');
		
		$affiliation->save();
		
		return new ECash_AgentAffiliation($affiliation);
	}
	
	/**
	 * Returns the current affiliation for the area and type.
	 *
	 * @param string $area
	 * @param string $type
	 * @return ECash_AgentAffiliation
	 */
	public function getCurrentAffiliation($area, $type)
	{
		$affiliation = ECash::getFactory()->getModel('AgentAffiliation', $this->db);
		
		$has_affiliation = $affiliation->loadActiveAffiliation($this->application->getId(), $area, $type);
		
		return $has_affiliation 
			? new ECash_AgentAffiliation($affiliation)
			: NULL;
	}
}
?>
