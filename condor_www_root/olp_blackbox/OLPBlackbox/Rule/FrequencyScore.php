<?php
/**
 * The Frequency Score rule.
 * 
 * Rule needs to be setup to use the email address.
 * 
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
class OLPBlackbox_Rule_FrequencyScore extends OLPBlackbox_Rule
{
	/**
	 * Event for this rule
	 *
	 * @var string
	 */
	protected $event_name = OLPBlackbox_Config::EVENT_FREQUENCY_SCORE;
	
	/**
	 * Runs the frequency score rule.
	 *
	 * @param Blackbox_Data $data the data we run the rule on
	 * @param Blackbox_IStateData $state_data state data passed to us
	 * @return bool
	 */
	protected function runRule(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		$valid = FALSE;
		
		$freq_obj = $this->getFrequencyScoreInstance();

		if ($freq_obj->testLimits($this->getRuleValue(), $this->getDataValue($data)))
		{
			$freq_obj->addPost($this->getDataValue($data));
		
			$valid = TRUE;
		}
		
		return $valid;
	}
	
	/**
	 * Returns the OLP database instance.
	 *
	 * @return MySQL_4
	 */
	protected function getDbInstance()
	{
		return $this->getConfig()->olp_db;
	}
	
	/**
	 * Returns the instance of the Accept_Ratio_Singleton class (the Frequency Score class).
	 *
	 * @return Accept_Ratio_Singleton
	 */
	protected function getFrequencyScoreInstance()
	{
		return Accept_Ratio_Singleton::getInstance($this->getDbInstance());
	}
}
?>