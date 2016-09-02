<?php

/**
 * Interface signifying that an object can accept {@see Blackbox_IRule} objects.
 * 
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 * @package Blackbox
 */
interface Blackbox_IRuleCollection 
{
	/**
	 * Add a rule to the collection.
	 * @param Blackbox_IRule $rule The rule to add to the collection.
	 * @return void
	 */
	public function addRule(Blackbox_IRule $rule);
}

?>
