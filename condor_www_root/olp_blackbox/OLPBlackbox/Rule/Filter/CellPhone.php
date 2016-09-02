<?php
/**
 * Cell phone filter rule.
 * 
 * Setup this rule to use the phone_cell field.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
class OLPBlackbox_Rule_Filter_CellPhone extends OLPBlackbox_Rule_Filter
{
	/**
	 * Runs the cell phone filter.
	 *
	 * @param Blackbox_Data $data the data to run the filter on
	 * @param Blackbox_IStateData $state_data the state data to run the filter on
	 * @param DateTime $date the date to use for the query
	 * @return bool
	 */
	protected function runFilter(Blackbox_Data $data, Blackbox_IStateData $state_data, DateTime $date)
	{
		$query = sprintf("
			SELECT
				p.application_id
			FROM
				personal_encrypted p USE INDEX (idx_cell_phone)
				INNER JOIN campaign_info ci
					ON p.application_id = ci.application_id
			WHERE
				p.cell_phone = '%s'
				AND ci.license_key = '%s'
				AND ci.created_date > '%s'",
			mysql_real_escape_string($this->getDataValue($data)),
			$this->getLicenseKey(),
			$date->format('Y-m-d')
		);
		
		if (is_int($data->application_id))
		{
			$query .= " AND p.application_id != {$data->application_id}";
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
	 * Returns the license key from the site config.
	 *
	 * @return string
	 */
	protected function getLicenseKey()
	{
		return $this->getConfig()->site_config->license;
	}
}

?>
