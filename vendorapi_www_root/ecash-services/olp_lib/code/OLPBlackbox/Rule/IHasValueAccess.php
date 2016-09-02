<?php

/**
 * Interface which indicates that a rule can have it's main value set/got.
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 */
interface OLPBlackbox_Rule_IHasValueAccess extends Blackbox_IRule
{
	/**
	 * Get the rule's value.
	 * @return mixed
	 */
	public function getRuleValue();
	
	/**
	 * Set the rule's value.
	 * @param mixed $value
	 * @return void
	 */
	public function setRuleValue($value);
}

?>