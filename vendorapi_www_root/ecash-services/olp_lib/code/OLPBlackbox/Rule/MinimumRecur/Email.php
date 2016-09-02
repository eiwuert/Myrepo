<?php
/**
 * Email mimimum recur check.
 *
 * This rule checks for any email addresses that have been sent to the given target for the past X days,
 * where X is the rule value. It checks for both winning applications and for vendors who were
 * posted the lead (whether they accepted it or not).
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
class OLPBlackbox_Rule_MinimumRecur_Email extends OLPBlackbox_Rule_MinimumRecur
{
	/**
	 * Runs the email recur check.
	 *
	 * @param string $data the data the check will use
	 * @param DateTime $date the date the check will use
	 * @param array $properties the targets we're checking
	 * @return int
	 */
	protected function runRecurCheck($data, DateTime $date, $properties)
	{
		$count = 0;
		$query_date = $date->format('Y-m-d');
		
		$data = $this->getDataValue($data);
		
		$property_list = implode("','", $properties);
		
		// Enterprise companies should check for winning applications
		// Non enterprise companies should check for posted applications (whether accepted or not)
		if ($this->isEnterprise($properties))
		{
			$query = sprintf("
				SELECT
					pe.application_id
				FROM
					application AS a USE INDEX (PRIMARY)
					INNER JOIN personal_encrypted AS pe USE INDEX (idx_email)
						ON pe.application_id = a.application_id
					INNER JOIN olp_blackbox.target AS t
						ON t.target_id = a.target_id
					JOIN olp_blackbox.blackbox_type as bt using(blackbox_type_id)
				WHERE
					pe.modified_date        >= '%s'
					AND bt.name 			= 'CAMPAIGN'
					AND pe.email            = '%s'
					AND t.property_short    IN ('%s')
					AND a.application_type != 'disagreed'
					AND a.application_type != 'confirmed_disagreed'
				LIMIT 1",
				$query_date,
				$data,
				$property_list
			);
		}
		else
		{
			$query = sprintf("
				SELECT
					pe.application_id
				FROM
					blackbox_post AS bp USE INDEX (idx_app_winner_type)
					INNER JOIN personal_encrypted AS pe USE INDEX (idx_email)
						ON pe.application_id = bp.application_id
				WHERE
					bp.date_created >= '%s'
					AND pe.email     = '%s'
					AND bp.winner    IN ('%s')
					AND bp.type      = 'POST'
				LIMIT 1",
				$query_date,
				$data,
				$property_list
			);
		}
		
		try
		{
			$result = $this->olp_db->Query($this->olp_db_name, $query);
			if ($this->olp_db->Row_Count($result) > 0)
			{
				$count = 1;
			}
		}
		catch (Exception $e)
		{
			$this->getConfig()->applog->Write(
				sprintf("%s:%s - email filter query failed", __CLASS__, __METHOD__)
			);
		}
		
		return $count;
	}
}
?>
