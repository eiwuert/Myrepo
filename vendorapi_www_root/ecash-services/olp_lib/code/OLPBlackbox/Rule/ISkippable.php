<?php

/**
 * Indicates that a rule can have it's skippable status altered.
 * 
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 */
interface OLPBlackbox_Rule_ISkippable extends Blackbox_IRule
{
	/**
	 * Change the skippable status of this rule.
	 * @param bool $skippable
	 * @return void
	 */
	public function setSkippable($skippable = TRUE);
}

?>