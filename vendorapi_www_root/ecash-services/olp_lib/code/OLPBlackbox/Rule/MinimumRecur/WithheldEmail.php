<?php
/**
 * Withheld Targets Email minimum recur check.
 *
 * This rule checks for any Email's that have been sent to the given target for the past X days,
 * where X is the rule value. It checks for both winning applications and for vendors who were
 * posted the lead (whether they accepted it or not).
 *
 * @author Rob Voss <Rob.Voss@sellingsource.com>
 */
class OLPBlackbox_Rule_MinimumRecur_WithheldEmail extends OLPBlackbox_Rule_MinimumRecur
{
	/**
	 * Overridden to check if the data value is empty beyondthe null check
	 * as searching against an empty string was causing full table scan searches.
	 *
	 * @param Blackbox_Data $data the data the rule is running against
	 * @param Blackbox_IStateData $state_data information about the state of the Blackbox_ITarget which desires to run the rule.
	 *
	 * @return bool
	 */
	protected function canRun(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		$data_value = $this->getDataValue($data);
		return (!empty($data_value));
	}
	
	/**
	 * Checks memcache for a total value we've already checked today and returns that count or
	 * FALSE if it's not found.
	 *
	 * @param Blackbox_Data $data
	 * @param Blackbox_IStateData $state_data
	 * @return int
	 */
	protected function checkCache(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		$key_data = $this->getDataValue($data);
		$query_date = $this->getQueryDate($this->getRuleValue())->format('Y-m-d');
		
		$this->cache_key = 'WithheldEmailMinRecur:' . md5($key_data . ':' . $query_date . ':' . $state_data->campaign_name);
		
		$total = $this->getConfig()->memcache->get($this->cache_key);
		
		return (int)$total;
	}
	
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
		$queries = array();
		
		// | delimited list of properties, creates an OR search to find any of them
		$property_list = implode("|", $properties);
		
		$query = sprintf("
			SELECT
				p.application_id
			FROM
				personal_encrypted p USE INDEX (idx_email)
				INNER JOIN blackbox_post bp
					ON p.application_id = bp.application_id
				INNER JOIN olp_blackbox.target t
					ON bp.winner = t.property_short
				JOIN olp_blackbox.rule_revision rev ON rev.rule_id = t.rule_id AND rev.active
				JOIN olp_blackbox.rule_relation rr ON rr.rule_id = t.rule_id AND rev.rule_revision_id = rr.rule_revision_id
				JOIN olp_blackbox.rule r ON r.rule_id = rr.child_id
				JOIN olp_blackbox.rule_definition def on def.rule_definition_id = r.rule_definition_id
					AND def.name_short = 'withheld_targets'
			WHERE
				bp.success = 'FALSE'
				AND r.rule_value RLIKE '[[:<:]]%s[[:>:]]'
				AND t.active = 1
				AND p.email = '%s'
				AND bp.date_modified >= '%s'
				AND bp.type = 'POST'
			LIMIT 1",
			$property_list,
			$this->getDataValue($data),		// Email
			$date->format('Y-m-d')
		);

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
				sprintf("%s:%s - WithheldEmail query failed: %s", __CLASS__, __METHOD__, $e->getMessage())
			);
		}

		return $count;
	}
}
?>
