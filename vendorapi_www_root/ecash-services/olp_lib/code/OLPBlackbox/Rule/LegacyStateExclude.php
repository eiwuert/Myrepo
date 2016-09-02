<?php

class OLPBlackbox_Rule_LegacyStateExclude extends OLPBlackbox_Rule_NotEqualsNoCase
{
	/**
	 * Assign the state value that should fail this rule.
	 *
	 * @param string $state The state that will fail this rule.
	 * 
	 * @return void
	 */
	public function __construct($state) 
	{
		if (!is_string($state) || strlen($state) != 2)
		{
			throw new InvalidArgumentException(sprintf(
				'%s does not appear to be a valid state',
				strval($state))
			);
		}
		
		$this->setupRule(
			array(self::PARAM_VALUE => $state, self::PARAM_FIELD => 'home_state')
		);
		
		$this->setEventName(sprintf('%s_LEAD', $state));
	}
	
	/**
	 * Override the onValid to NOT hit event log, as QA will complain if passes are logged.
	 *
	 * @param Blackbox_Data $data Information app we're processing.
	 * @param Blackbox_IStateData $state_data Information about the calling ITarget.
	 * 
	 * @return void
	 */
	protected function onValid(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		$this->setEventName('');
		parent::onValid($data, $state_data);
	}
	/**
	 * When this rule is determined to be not valid, we must also set a state_data flag.
	 *
	 * @param Blackbox_Data $data Information app we're processing.
	 * @param Blackbox_IStateData $data Information about the calling ITarget.
	 * 
	 * @return void
	 */
	protected function onInvalid(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		$state_data->global_rule_failure = 'LegacyStateExclude';
		parent::onInvalid($data, $state_data);
	}
}

?>
