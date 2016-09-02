<?php
/**
 * Basically a recur rule the is spread across multiple campaigns
 * @author stephan soileau <stephan.soileau@sellingsource.com>
 */
class OLPBlackbox_Rule_MultiCampaignRecur extends OLPBlackbox_Rule
{
	/**
	 *
	 * @var OLP_Factory
	 */
	protected $factory;
	/**
	 *
	 * @param OLP_Factory $factory 
	 */
	public function __construct(OLP_Factory $factory)
	{
		$this->factory = $factory;
		parent::__construct();
	}
	
	/**
	 * Run the mimimum recur rule.
	 *
	 * @param Blackbox_Data $data the data used to use
	 * @param Blackbox_IStateData $state_data state data to use
	 * @return bool
	 */
	protected function runRule(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		$post_model = $this->factory->getModel('BlackboxPost');
		//function loadAllByWinnerBetweenDate($winner, $start_date, $end_date, array $where_args = array())
		$post_models = $post_model->loadAllByWinnerBetweenDate(
			$this->getCampaignsToCheck(),
			$this->getDateToCheckFrom(),
			NULL,
			$this->getMatchArguments($data)
		);
		return count($post_models) < $this->getThreshold();
	}

	/**
	 * Returns all the appropriate match arguments
	 * as an array
	 * @param Blackbox_Data $data
	 * @return array
	 */
	protected function getMatchArguments(Blackbox_Data $data)
	{
		return array(
			$this->getMatchColumn() => $this->getDataValue($data)
		);
	}

	/**
	 * Returns a formatted date string with the correct time based
	 * on the number_of_days that this rule should be looking at
	 * @return string
	 */
	protected function getDateToCheckFrom()
	{
		$days_to_look = $this->params[Blackbox_StandardRule::PARAM_VALUE]['number_of_days'];
		if (is_numeric($days_to_look))
		{
			return date('Y-m-d H:i:s', ($this->getTime() - (86400 * $days_to_look)));
		}
		else
		{
			throw new InvalidArgumentException("Number of days setup incorrectly in ". __CLASS__);
		}
	}

	/**
	 * Returns a unix timestamp.
	 * @return timestamp
	 */
	protected function getTime()
	{
		return time();
	}

	/**
	 * Returns an array of all the winners we
	 * need to look at in blackbox post
	 */
	protected function getCampaignsToCheck()
	{
		$targets = $this->params[Blackbox_StandardRule::PARAM_VALUE]['targets'];
		if (empty($targets))
		{
			throw new InvalidArgumentException("No targets configured in ".__CLASS__);
		}
		if (!is_array($targets))
		{
			$targets = array_map('trim', explode(',', $targets));
		}
		return $targets;
	}

	/**
	 * Override setupRule so that when we do the setup, we can set the field
	 * from the values array since it's configured in bbadmin
	 * @param array $params
	 * @return void
	 */
	public function setupRule($params)
	{
		parent::setupRule($params);
		switch ($this->params[Blackbox_StandardRule::PARAM_VALUE]['field'])
		{
			case 'ssn':
				$this->params[Blackbox_StandardRule::PARAM_FIELD] = 'social_security_number_encrypted';
				break;
			case 'email':
				$this->params[Blackbox_StandardRule::PARAM_FIELD] = 'email_primary';
				break;
		}
	}

	/**
	 * Maps a field name to a column name really
	 * @return string
	 */
	protected function getMatchColumn()
	{
		switch ($this->params[Blackbox_StandardRule::PARAM_VALUE]['field'])
		{
			case 'ssn':
				return'social_security_number';
				break;
			case 'email':
				return 'email';
				break;
		}
		throw new InvalidArgumentException("Invalid field set for rule in ".__CLASS__);
	}

	/**
	 * Returns the threshold for this rule.
	 * @return integer
	 */
	protected function getThreshold()
	{
		$threshold = $this->params[Blackbox_StandardRule::PARAM_VALUE]['threshold'];
		if (is_numeric($threshold))
		{
			return $threshold;
		}
		throw new InvalidArgumentException("Invalid threshold set for rule in ".__CLASS__);
	}
	
}
