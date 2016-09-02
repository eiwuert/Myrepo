<?php
/**
 * Email filter rule.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
class OLPBlackbox_Rule_Filter_Email extends OLPBlackbox_Rule_Filter
{
	/**
	 * Runs the Email filter.
	 *
	 * @param Blackbox_Data $data the data to run the filter on
	 * @param Blackbox_IStateData $state_data state data to run the filter on
	 * @param DateTime $date the date to use for the query
	 * @return bool
	 */
	protected function runFilter(Blackbox_Data $data, Blackbox_IStateData $state_data, DateTime $date)
	{
		$valid = FALSE;
		$email = mysql_real_escape_string($data->email_primary);
		$ssn_encrypted = mysql_real_escape_string($data->social_security_number_encrypted);
						
		$query = sprintf("
			SELECT
				a.application_id
			FROM
				personal_encrypted p USE INDEX (idx_email)
				INNER JOIN application a
					ON p.application_id = a.application_id
				INNER JOIN target t
					ON a.target_id = t.target_id
				INNER JOIN tier
					ON t.tier_id = tier.tier_id
			WHERE
				p.email = '%s'
				AND a.application_type = 'COMPLETED'
				AND a.created_date > '%s'
				AND tier.tier_number = %u
				AND p.social_security_number != '%s'",
			$email,
			$date->format('Y-m-d'),
			$state_data->tier_number,
			$ssn_encrypted
		);
		
		if (is_int($data->application_id))
		{
			$query .= " AND a.application_id != {$data->application_id}";
		}
		
		try
		{
			$result = $this->olp_db->Query($this->olp_db_name, $query);
			$valid = ($this->olp_db->Row_Count($result) == 0);
		}
		catch (Exception $e)
		{
			// TODO: Log an error
			echo $e->getMessage();
			$valid = TRUE;
		}

		return $valid;
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
		if (!is_null($data->email_primary)
			&& !is_null($data->social_security_number_encrypted))
		{
			return TRUE;
		}
		
		return FALSE;
	}
}

?>
