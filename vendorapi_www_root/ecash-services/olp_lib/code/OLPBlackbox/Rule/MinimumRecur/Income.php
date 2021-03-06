<?php
/**
 * Income recur check.
 *
 * This rule checks to see that a certain user has not changed their income more than a given number
 * of time, in less than a given number of days. For example, a target could be setup with this
 * rule so that if a user changes their income more than 2 times in the last 30 days, we'll deny
 * them.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
class OLPBlackbox_Rule_MinimumRecur_Income extends OLPBlackbox_Rule_MinimumRecur
{
	/**
	 * The number of times income can change for this rule.
	 *
	 * @var int
	 */
	protected $income_changes = 0;
	
	/**
	 * Runs the income recur rule.
	 *
	 * Normally we would just run the runRecurRule(), but in this case, we have two rule values
	 * and the runRecurRule() function doesn't cover more than just the date.
	 *
	 * @param Blackbox_Data $data the data used to use
	 * @param Blackbox_IStateData $state_data state data to use
	 * @return bool
	 */
	protected function runRule(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		// If the income changes is our default value or we somehow got here when the database
		// tells us the income_changes is 0, then we just want to pass this rule.
		if ($this->income_changes <= 0)
		{
			$this->result = TRUE;
			return $this->result;
		}
		
		// Setup the db
		$this->olp_db = $this->getDbInstance();
		$this->olp_db_name = $this->getDbName();
		
		$date = $this->getQueryDate($this->getRuleValue());
		
		$campaigns = $this->getCompanyCampaignIdsByCampaign($state_data->campaign_name);
		
		$count = 0;
		$query_date = $date->format('Y-m-d');
		
		$query = sprintf("
			SELECT
				COUNT(DISTINCT i.monthly_net) AS count
			FROM
				personal_encrypted pe
				INNER JOIN income i
					ON pe.application_id = i.application_id
				INNER JOIN application a
					ON i.application_id = a.application_id
			WHERE
				pe.social_security_number = '%s'
				AND pe.modified_date >= '%s'
				AND i.monthly_net != '%s'
				AND a.target_id IN (%s)",
			$data->social_security_number_encrypted,
			$query_date,
			$data->income_monthly_net,
			implode(',', $campaigns)
		);
		
		try
		{
			$result = $this->olp_db->Query($this->olp_db_name, $query);
			if (($row = $this->olp_db->Fetch_Object_Row($result)))
			{
				$count = $row->count;
			}
		}
		catch (Exception $e)
		{
			$this->getConfig()->applog->Write(
				sprintf("%s:%s - SSN filter query failed", __CLASS__, __METHOD__)
			);
		}
		
		$this->result = ($count < $this->income_changes);
		
		return $this->result;
	}
	
	/**
	 * Runs the recur check.
	 *
	 * The function must exist since it's abstract. It throws an exception in the odd
	 * case that someone does make a call to it.
	 *
	 * @param string $data the data the check will use
	 * @param DateTime $date the date the check will use
	 * @param array $properties the targets we're checking
	 * @return bool
	 */
	protected function runRecurCheck($data, DateTime $date, $properties)
	{
		throw new Blackbox_Exception('invalid function called, function should not have been called');
	}
	
	/**
	 * Checks to see if we have enough information to run this rule.
	 *
	 * @param Blackbox_Data $data the data to do validation against
	 * @param Blackbox_IStateData $state_data the state data to do validation against
	 * @return bool
	 */
	protected function canRun(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		if (!empty($data->social_security_number_encrypted) && !empty($data->income_monthly_net))
		{
			return TRUE;
		}
		
		return FALSE;
	}
	
	public function setupRule($params)
	{
		// We subtract one from the income changes because we want to allow
		// the interface to say 4 or more changes, when our query will actually be looking
		// for a count of 3 or more.
		$income_changes = $params[Blackbox_StandardRule::PARAM_VALUE]['changes'];
		$this->income_changes = $income_changes - 1;
		
		// Set VALUE to days
		$params[self::PARAM_VALUE] = $params[Blackbox_StandardRule::PARAM_VALUE]['days'];
		parent::setupRule($params);
	}
}
?>
