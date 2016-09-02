<?php

/**
 * Decorates a rule and hits no-bill_rate if there is no stat
 * @author stephan soileau <stephan.soileau@sellingsource.com>
 *
 */
class OLPBlackbox_Rule_NoBillRateDecorator implements OLPBlackbox_ISellRule 
{
	/**
	 * @var Blackbox_IRule
	 */
	protected $rule;

	/**
	 * Whether or not the no_bill_rate stat has been hit
	 * during this run
	 *
	 * @var bool
	 */
	protected static $stat_hit = FALSE;
	
	/**
	 * Constructor
	 * 
	 * @param OLPBlackbox_Rule $rule
	 */
	public function __construct(OLPBlackbox_Rule $rule) 
	{
		$this->rule = $rule;
	}
	
	/**
	 * Checks if the rule is valid
	 * 
	 * @param Blackbox_Data $data
	 * @param Blackbox_IStateData $state_data
	 * @return bool
	 */
	public function isValid(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		$campaigns = implode('|', CompanyData::getCompanyProperties(CompanyData::COMPANY_CLK));
		if (($state_data->price_point === NULL || $state_data->price_point === FALSE)
			&& !self::$stat_hit
			&& empty(OLPBlackbox_Config::getInstance()->disable_no_bill_rate)
			&& !in_array(OLPBlackbox_Config::getInstance()->promo_id, array(99999, 10000))
			&& !preg_match("/^({$campaigns})(_ocs)?$/i", $state_data->campaign_name))
		{
			$this->rule->hitTargetStat('no_bill_rate', $data, $state_data);
			$this->setStatHit(TRUE);

			OLPBlackbox_Config::getInstance()->applog->Write(sprintf(
				"No Bill rate found for campaign '%s' on promo_id '%d'",
				$state_data->campaign_name,
				OLPBlackbox_Config::getInstance()->promo_id
			));
		}

		return $this->rule->isValid($data, $state_data);
	}

	/**
	 * Sets the status of the stat_hit variable
	 *
	 * @param bool $hit
	 * @return void
	 */
	public function setStatHit($hit = FALSE)
	{
		self::$stat_hit = $hit;
	}
	
	/**
	 * Magic function 
	 *
	 * @return string
	 */
	public function __toString()
	{
		return sprintf('[%s] %s', get_class($this), $this->rule->__toString());
	}
}
