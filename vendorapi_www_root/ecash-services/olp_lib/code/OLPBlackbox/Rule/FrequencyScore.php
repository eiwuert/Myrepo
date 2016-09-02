<?php
/**
 * The Frequency Score rule.
 * 
 * Rule needs to be setup to use the email address.
 * 
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
class OLPBlackbox_Rule_FrequencyScore extends OLPBlackbox_Rule implements OLPBlackbox_IPickTargetRule
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
		
		$limits = $this->getRuleValue();
		$email = $this->getDataValue($data);

		if (isset($limits['min_freq']) && isset($limits['max_freq']) && $limits['max_freq'] > 0)
		{
			$freq_obj = $this->getFrequencyScoreInstance();
			$freq_score = $freq_obj->getRejectsByHistory($email, '24 hours');
			
			if ($freq_score >= $limits['min_freq'] && $freq_score <= $limits['max_freq'])
			{
				$valid = TRUE;
			}
		}
		else
		{
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
	 * Returns the PDO OLP database instance.
	 *
	 * @return DB_IConnection_1
	 */
	protected function getPDOInstance()
	{
		return $this->getDbInstance()->getConnection()->getConnection();
	}
	
	/**
	 * Returns the instance of the Accept_Ratio_Singleton class (the Frequency Score class).
	 *
	 * @return OLP_FrequencyScore
	 */
	protected function getFrequencyScoreInstance()
	{
		$freq_object = new OLP_FrequencyScore(
			$this->getPDOInstance(),
			$this->getConfig()->memcache
		);
		
		return $freq_object;
	}
}
?>
