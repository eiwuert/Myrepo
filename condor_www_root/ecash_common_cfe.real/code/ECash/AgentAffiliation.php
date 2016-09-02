<?php

class ECash_AgentAffiliation extends Object_1 
{
	/**
	 * @var ECash_Models_AgentAffiliation
	 */
	protected $model;
	
	/**
	 * @var ECash_Agent
	 */
	protected $agent;
	
	/**
	 * @var ECash_Application
	 */
	protected $application;
	
	/**
	 * Creates a new agent affiliation business object
	 *
	 * @param ECash_Models_AgentAffiliation $model
	 */
	public function __construct(ECash_Models_AgentAffiliation $model)
	{
		$this->model = $model;
	}

	/**
	 * Associates the affiliation to all of the given event schedule ids
	 *
	 * @param mixed $event_schedule_ids
	 */
	public function associateWithScheduledEvents($event_schedule_ids)
	{
		foreach ((array) $event_schedule_ids as $event_schedule_id)
		{
			$aaes = ECash::getFactory()->getModel('AgentAffiliationEventSchedule');
			$aaes->event_schedule_id = $event_schedule_id;
			$aaes->agent_affiliation_id = $this->model->agent_affiliation_id;
			$aaes->save();
		}
	}
	
	/**
	 * Returns the agent for this affiliation
	 *
	 * @return ECash_Agent
	 */
	public function getAgent()
	{
		if (empty($this->agent))
		{
			$this->agent = ECash_Agent::getByAgentId(
				ECash_Config::getMasterDbConnection(),
				$this->model->agent_id,
				$this->model->company_id
			);
		}

		return $this->agent;
	}

	/**
	 * @return ECash_Application
	 */
	public function getApplication()
	{
		if (empty($this->application))
		{
			$this->application = new ECash_Application(
				$this->model->application_id, 
				$this->model->company_id
			);
		}
		
		return $this->application;
	}

	public function getCompanyId()
	{
		return $this->model->company_id;
	}
	
	public function getDateCreated()
	{
		return $this->model->date_created;
	}

	public function getAgentId()
	{
		return $this->model->agent_id;
	}
	
	public function updateExpiration($new_expiration)
	{
		$this->model->date_expiration_actual = $new_expiration;
		$this->model->save();
	}

	/**
	 * Returns an array of objects containing information about all application affiliations sorted
	 * by the time created in descending order (most recent first).
	 *
	 * @param integer $application_id
	 * @param string $affiliation_area
	 * @param string $affiliation_type
	 * @return array
	 * @todo GET RID OF THIS!!!!! - rewrite as a data class
	 */
	static public function getApplicationAffiliations($application_id, $affiliation_area = '', $affiliation_type = '')
	{
		$affiliation_area_sql = (!empty($affiliation_area)) ? "AND aa.affiliation_area = '{$affiliation_area}'" : '';
		$affiliation_type_sql = (!empty($affiliation_type)) ? "AND aa.affiliation_type = '{$affiliation_type}'" : '';

		$query = "
			-- eCash3.5 File: " . __FILE__ . ", Method: " . __METHOD__ . ", Line: " . __LINE__ . "
			SELECT
				aa.agent_affiliation_id,
				aa.date_created,
				aa.date_expiration,
				aa.date_expiration_actual,
				aa.affiliation_area,
				aa.affiliation_type,
				aar.name AS reason,
				aa.agent_id,
				CONCAT(a.name_last, ', ', a.name_first) AS 'agent_name'
			FROM agent_affiliation AS aa
				LEFT JOIN agent AS a ON (a.agent_id = aa.agent_id)
				LEFT JOIN agent_affiliation_reason AS aar ON (aar.agent_affiliation_reason_id = aa.agent_affiliation_reason_id)
			WHERE aa.application_id = {$application_id}
				{$affiliation_area_sql}
				{$affiliation_type_sql}
			ORDER BY aa.date_created DESC
		";

		$affiliations = array();

		$result = self::masterMySQLi()->Query($query);
		while ($row = $result->Fetch_Object_Row())
		{
			$affiliations[$row->agent_affiliation_id] = $row;
		}

		return $affiliations;
	}
		/**
	 * Reports on all affiliations of the specified agent_id
	 *
	 * @param int $from_agent_id
	 * @param int $to_agent_id
	 * @return array of application ids
	 * 
	 * @todo rewrite using a data class
	 */
	static public function getAgentActiveAffiliations($from_agent_id)
	{
		$db = ECash_Config::getMasterDbConnection();

		$query = "
			SELECT
                application_id
            FROM
                agent_affiliation
            WHERE
                agent_id = {$db->quote($from_agent_id)} AND
                affiliation_status = 'active'
		";
		$result = $db->query($query);
		$assigned_application_ids = Array();
		while ($row = $result->fetch(PDO::FETCH_OBJ))
		{
			$assigned_application_ids[] = $row->application_id;
		}
		return $assigned_application_ids;
	}
	
		/**
	 * Copies all affiliations of $from_agent_id to $to_agent_id and expires 
	 * any active affiliations for $from_agent_id. The ids of the applications
	 * reassigned is returned.
	 *
	 * @param int $from_agent_id
	 * @param int $to_agent_id
	 * @return array of application ids
	 * 
	 * @todo rewrite to pull in affiliations and expire and create new
	 */
	static public function reassignApplications($from_agent_id, $to_agent_id)
	{
		self::check_for_agent_id($from_agent_id);
		self::check_for_agent_id($to_agent_id);
		$db = ECash_Config::getMasterDbConnection();

		
		$query = "
			INSERT INTO agent_affiliation
			(
				date_created,
				company_id,
				agent_id,
				application_id,
				date_expiration,
				date_expiration_actual,
				affiliation_area,
				affiliation_type,
				affiliation_status,
				agent_affiliation_reason_id
			)
			SELECT
				NOW(),
				company_id,
				{$db->quote($to_agent_id)},
				application_id,
				date_expiration,
				date_expiration_actual,
				affiliation_area,
				affiliation_type,
				affiliation_status,
				agent_affiliation_reason_id
			FROM
				agent_affiliation
			WHERE
				agent_id = {$db->quote($from_agent_id)} AND
				affiliation_status = 'active'
		";
		
		$rowcount = $db->query($query)->rowCount();
		
		self::expireAllAgentAffiliations($from_agent_id);

		return $rowcount;
	}
	
}
?>
