<?php

/**
 * This limit rule for collections will actually pull the individual limits for each of the members of the collection
 * to determine how many leads have been sold
 *
 * @author Chris Barmonde <chris.barmonde@sellingsource.com>
 */
class OLPBlackbox_Rule_TargetCollectionLimit extends OLPBlackbox_Rule
{
	/**
	 * Indicates whether the rule can be run
	 *
	 * @param Blackbox_Data $data
	 * @param Blackbox_IStateData $state
	 * @return bool
	 */
	public function canRun(Blackbox_Data $data, Blackbox_IStateData $state)
	{
		return TRUE;
	}

	/**
	 * Compares the limit to the current value
	 *
	 * @param Blackbox_Data $data
	 * @param Blackbox_IStateData $state
	 * @return bool
	 */
	public function runRule(Blackbox_Data $data, Blackbox_IStateData $state)
	{
		$valid = TRUE;
		$children = $state->children;

		if (!empty($children))
		{
			$stats_limits = new Stats_Limits($this->getConfig()->olp_db->getConnection()->getConnection());

			$stats = array();
			foreach ($children as $property_short)
			{
				$stats[] = OLPBlackbox_Factory_LimitCollection::getTargetStat($property_short);
			}

			$total_leads = $stats_limits->sum(
				$stats,
				NULL,
				NULL,
				NULL,
				Blackbox_Utils::getToday()
			);

			$limit = $this->getRuleValue();

			$valid = ($limit > $total_leads);
		}

		return $valid;
	}

	/**
	 * Allow applications to pass if there are errors
	 *
	 * @return bool
	 */
	protected function onError(Blackbox_Exception $e, Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		return TRUE;
	}
}

?>
