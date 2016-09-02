<?php
/**
 * MICR filter rule.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
class OLPBlackbox_Rule_Filter_MICR extends OLPBlackbox_Rule_Filter
{
	/**
	 * Runs the MICR filter.
	 *
	 * @param Blackbox_Data $data the data to run the filter on
	 * @param Blackbox_IStateData $state_data the state data to run the filter on
	 * @param DateTime $date the date to use for the query
	 * @return bool
	 */
	protected function runFilter(Blackbox_Data $data, Blackbox_IStateData $state_data, DateTime $date)
	{
		$bank_acct_numbers = implode("','", $data->permutated_bank_account_encrypted);
		
		$query = sprintf("
			SELECT STRAIGHT_JOIN
				a.application_id
			FROM
				bank_info_encrypted b
				INNER JOIN personal_encrypted p
					ON b.application_id = p.application_id
				INNER JOIN application a
					ON b.application_id = a.application_id
				INNER JOIN target t
					ON a.target_id = t.target_id
				INNER JOIN tier
					ON t.tier_id = tier.tier_id
			WHERE
				b.routing_number = '%s'
				AND b.account_number IN ('%s')
				AND a.application_type = 'COMPLETED'
				AND a.created_date > '%s'
				AND tier.tier_number = %u
				AND p.social_security_number != '%s'",
			mysql_real_escape_string($data->bank_aba_encrypted),
			$bank_acct_numbers,
			$date->format('Y-m-d'),
			$state_data->tier_number,
			mysql_real_escape_string($data->social_security_number_encrypted)
		);
		
		if (is_int($data->application_id))
		{
			$query .= " AND a.application_id != {$data->application_id}";
		}
		
		try
		{
			$result = $this->olp_db->Query($this->olp_db_name, $query);
			
			//If we have rows, they've got a loan, so deny 'em
			return ($this->olp_db->Row_Count($result) == 0);
		}
		catch (Exception $e)
		{
			// TODO: Write a log entry
			echo $e->getMessage();
		}
		
		return TRUE;
	}
	
	/**
	 * Checks to see if we can run this rule.
	 * 
	 * @param Blackbox_Data $data the data this rule will use
	 * @param Blackbox_IStateData $state_data the state data this rule will use
	 * @return bool
	 */
	protected function canRun(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		if (!is_null($data->bank_aba_encrypted)
			&& !empty($data->permutated_bank_account_encrypted)
			&& !is_null($data->social_security_number_encrypted))
		{
			return TRUE;
		}
		
		return FALSE;
	}
	
}

?>
