<?php
/**
 * Drivers license filter rule.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
class OLPBlackbox_Rule_Filter_DriversLicense extends OLPBlackbox_Rule_Filter
{
	/**
	 * Runs the drivers license filter.
	 *
	 * @param Blackbox_Data $data the data to run the filter on
	 * @param Blackbox_IStateData $state_data the state data to run the filter on
	 * @param DateTime $date the date to use for the query
	 * @return bool
	 */
	protected function runFilter(Blackbox_Data $data, Blackbox_IStateData $state_data, DateTime $date)
	{
		$valid = TRUE;
		
		/**
		 * Don't run the query if they have any of these values. It can cause the query to take a long time to run.
		 */
		if (strcasecmp($data->state_id_number, 'none') != 0
			&& strcasecmp($data->state_id_number, 'n/a') != 0
			&& strcasecmp($data->state_id_number, 'na') != 0)
		{
			$license_number = mysql_real_escape_string($data->state_id_number);
			$ssn_encrypted = mysql_real_escape_string($data->social_security_number_encrypted);
			
			$query = sprintf("
				SELECT
					a.application_id,
					p.drivers_license_number,
					p.drivers_license_state
				FROM
					personal_encrypted p USE INDEX (idx_drivers)
					INNER JOIN application a
						ON p.application_id = a.application_id
					INNER JOIN target t
						ON a.target_id = t.target_id
					INNER JOIN tier
						ON t.tier_id = tier.tier_id
				WHERE
					p.drivers_license_number = '%s'
					AND a.application_type = 'COMPLETED'
					AND a.created_date > '%s'
					AND tier.tier_number = %u
					AND p.social_security_number != '%s'",
				$license_number,
				$date->format('Y-m-d'),
				$state_data->tier_number,
				$ssn_encrypted
			);

			if (!is_null($data->state_issued_id))
			{
				$license_state = mysql_real_escape_string($data->state_issued_id);
				$query .= " AND p.drivers_license_state = '{$license_state}'";
			}
			
			if (is_int($data->application_id))
			{
				$query .= " AND a.application_id != {$data->application_id}";
			}
			
			try
			{
				$result = $this->olp_db->Query($this->olp_db_name, $query);
				
				//If we have rows, they've got a loan, so deny 'em
				$valid = ($this->olp_db->Row_Count($result) == 0);
			}
			catch (Exception $e)
			{
				// TODO: Write a log entry
				echo $e->getMessage();
			}
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
		if (!is_null($data->state_id_number)
			&& !is_null($data->social_security_number_encrypted))
		{
			return TRUE;
		}
		
		return FALSE;
	}
}

?>
