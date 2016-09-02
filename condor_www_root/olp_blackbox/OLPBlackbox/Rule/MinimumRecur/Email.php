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
	 * @param string $name_short the name of the target we're checking
	 * @return int
	 */
	protected function runRecurCheck($data, DateTime $date, $name_short)
	{
		$count = 0;
		$queries = array();
		$query_date = $date->format('Y-m-d');
		
		$data = $this->getDataValue($data);
		
		$queries[] = sprintf("
			SELECT
				COUNT(*) AS count
			FROM
				application AS a USE INDEX (PRIMARY)
				INNER JOIN personal_encrypted AS pe USE INDEX (idx_email)
					ON pe.application_id = a.application_id
				INNER JOIN target AS t
					ON t.target_id = a.target_id
			WHERE
				a.modified_date        >= '%s'
				AND pe.email            = '%s'
				AND t.property_short    = '%s'
				AND a.application_type != 'disagreed'
				AND a.application_type != 'confirmed_disagreed'",
			$query_date,
			$data,
			$name_short
		);
		
		$queries[] = sprintf("
			SELECT
				COUNT(*) AS count
			FROM
				blackbox_post AS bp
				INNER JOIN personal_encrypted AS pe USE INDEX (idx_email)
					ON pe.application_id = bp.application_id
			WHERE
				bp.date_created >= '%s'
				AND pe.email     = '%s'
				AND bp.winner    = '%s'",
			$query_date,
			$data,
			$name_short
		);
		
		foreach ($queries as $query)
		{
			try
			{
				$result = $this->olp_db->Query($this->olp_db_name, $query);
				if (($row = $this->olp_db->Fetch_Object_Row($result)))
				{
					$count += $row->count;
				}
			}
			catch (Exception $e)
			{
				$this->getConfig()->applog->Write(
					sprintf("%s:%s - email filter query failed", __CLASS__, __METHOD__)
				);
			}
		}
		
		return $count;
	}
}
?>
