<?php
/**
 * Direct deposit mimimum recur check.
 * 
 * This rule checks for any direct deposit changes within X number of days. For example, if a
 * customer says they have direct deposit now, but X days ago they submitted an application
 * saying they didn't, this rule will deny them. This rule does not check for the inverse. If
 * they said they did have direct deposit and later change to say they don't, we don't care.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
class OLPBlackbox_Rule_MinimumRecur_DirectDeposit extends OLPBlackbox_Rule_MinimumRecur
{
	/**
	 * Runs the direct deposit recur check.
	 *
	 * @param string $data the data the check will use
	 * @param DateTime $date the date the check will use
	 * @param array $properties the targets we're checking (not used in this function)
	 * @return int
	 */
	protected function runRecurCheck($data, DateTime $date, $properties)
	{
		// The check auto passes if they don't have direct deposit on the current app
		if ($data->income_direct_deposit === FALSE || strcasecmp($data->income_direct_deposit, 'FALSE') == 0)
		{
			return 0;
		}
		$count = 0;
		$query_date = $date->format('Y-m-d');
		$ssn = $this->getDataValue($data);
		
		$query = sprintf("
			SELECT
				COUNT(*) AS count
			FROM
				personal_encrypted AS pe
				INNER JOIN bank_info_encrypted bie
					ON bie.application_id = pe.application_id
			WHERE
				pe.social_security_number = '%s'
				AND pe.application_id != %u
				AND pe.modified_date > '%s'
				AND bie.direct_deposit != 'TRUE'",
			$ssn,
			$data->application_id,
			$query_date
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
				sprintf("%s:%s - direct deposit filter query failed", __CLASS__, __METHOD__)
			);
		}
		
		return $count;
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
		if (parent::canRun($data, $state_data))
		{
			return !is_null($data->income_direct_deposit);
		}
		
		return FALSE;
	}
}
?>
