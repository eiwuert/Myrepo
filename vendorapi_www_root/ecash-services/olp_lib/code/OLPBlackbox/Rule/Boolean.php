<?php

/**
 * Simple rule which just returns whatever value you set on it as the result of 
 * it's runRule().
 *
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 */
class OLPBlackbox_Rule_Boolean extends OLPBlackbox_Rule
{
	/**
	 * @param bool $value Whether this rule should return TRUE or FALSE.
	 * @return void
	 */
	function __construct($value = TRUE)
	{
		$this->setRuleValue((bool)$value);
		
		parent::__construct();
	}

	/**
	 * Set the value this rule should return (TRUE/FALSE).
	 * @param bool $value
	 * @return void
	 */
	public function setRuleValue($value)
	{
		parent::setRuleValue((bool)$value);
	}

	/**
	 * There's nothing which affects if this can run. It can always run.
	 * @param Blackbox_Data $data The data used to validate the rule. 
	 * @param Blackbox_IStateData $state_data the target state data 
 	 * @return bool TRUE
	 */
	public function canRun(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		return TRUE;
	}
	
	/**
	 * Return the rule value set on this object.
	 * 
	 * @param Blackbox_Data $data The data used to validate the rule. 
	 * @param Blackbox_IStateData $state_data the target state data 
	 * @return bool 
	 * @see Blackbox_Rule::runRule()
	 */
	protected function runRule(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		return $this->getRuleValue();
	}
}

?>