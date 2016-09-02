<?php

/**
 * A simple limit rule
 *
 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
 */
class OLPBlackbox_Rule_Limit extends OLPBlackbox_Rule
{
	/**
	 * @var Stat_Limits
	 */
	protected $stat_limits;

	/**
	 * @param Stat_Limits $stat_limits
	 * @return void
	 */
	public function setStatLimits(Stat_Limits $stat_limits)
	{
		$this->stat_limits = $stat_limits;
	}

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
		$stat_name = $this->getLimitStat();

		if ($stat_name)
		{
			// get the current value
			$state->current_leads = $this->stat_limits->Fetch(
				$stat_name,
				NULL,
				NULL,
				NULL,
				Blackbox_Utils::getToday()
			);

			$limit = $this->getRuleValue();

			return ($limit > $state->current_leads);
		}
		return TRUE;
	}

	/**
	 * Gets the stat that the limit is on
	 *
	 * @return string
	 */
	protected function getLimitStat()
	{
		if (!isset($this->params[self::PARAM_FIELD]))
		{
			throw new Blackbox_Exception('Missing limit stat name');
		}
		return $this->params[self::PARAM_FIELD];
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
