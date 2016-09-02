<?php
/**
 * Interface for the Picker classes.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
interface OLPBlackbox_IPicker extends OLPBlackbox_IRestorable
{
	/**
	 * Picks a target from the available targets.
	 *
	 * @param Blackbox_Data $data data that will can be used for further validation
	 * @param Blackbox_IStateData $state_data data for the ITarget running using this picker
	 * @param array $targets an array of Blackbox_ITargets to pick from
	 * @return Blackbox_ITarget
	 */
	public function pickTarget(Blackbox_Data $data, Blackbox_IStateData $state_data, array $targets);
}
?>
