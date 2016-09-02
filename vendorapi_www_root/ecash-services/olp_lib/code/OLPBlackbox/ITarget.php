<?php
/**
 * Interface for OLP Blackbox Targets.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
interface OLPBlackbox_ITarget extends Blackbox_ITarget, OLPBlackbox_IRestorable
{
	/**
	 * Sets the target to be invalid.
	 *
	 * @return void
	 */
	public function setInvalid();
	
	/**
	 * Sets the rule or rules to run on pick target.
	 *
	 * @param Blackbox_IRule $rules the rule or rule collection to run
	 * @return void
	 */
	public function setPickTargetRules(Blackbox_IRule $rules);
}
?>
