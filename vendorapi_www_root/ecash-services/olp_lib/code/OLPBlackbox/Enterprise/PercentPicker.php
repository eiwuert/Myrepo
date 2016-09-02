<?php

/**
 * Enterprise Percent Picker.  Will maintain a winning target across
 * multiple collections.  This is specifically for the DataX Price Points project
 * where the DataX call can 'soft fail' back to us.  They want to maintain the
 * same target on the successive calls, so if it first goes to UFC, it'll always
 * go to UFC in the other CLK collections.
 * 
 * One stipulation, though, is that if for some reason the winning target is not
 * available in a lower-tiered collection, we'll default back to picking from all
 * available targets, then use the winner from that choice in the successive calls.
 * 
 * @author Chris Barmonde <chris.barmonde@sellingsource.com>
 */
class OLPBlackbox_Enterprise_PercentPicker extends OLPBlackbox_PercentPicker
{
	/**
	 * Picks a target based on percentage weighting.  Additional check to ensure that the
	 * same target is picked across multiple collections.
	 *
	 * @param Blackbox_Data $data data that will can be used for further validation
	 * @param Blackbox_StateData $state_data state data for the ITarget using this picker object
	 * @param array $target_list array of Blackbox_ITargets to pick from
	 * @return Blackbox_IWinner|bool
	 */
	public function pickTarget(Blackbox_Data $data, Blackbox_IStateData $state_data, array $target_list)
	{
		$picked = $this->getPickedTargets();
		
		// If we've already picked a previous winner, we want to make sure that winner is
		// maintained through the rest of the collections that use this picker.
		if (!empty($picked))
		{
			// We find whoever the last picked winner was and use them to find who should be picked next.
			$picked_campaign = $picked[count($picked) - 1];
			$picked_target = $this->getBaseTarget($picked_campaign->getStateData()->campaign_name);

			foreach ($target_list as $target)
			{
				/* @var $target OLPBlackbox_Target */
				if ($picked_target == $this->getBaseTarget($target->getStateData()->campaign_name))
				{
					// When we do find a match on the target, we restrict the $target_list down to
					// only that target, so it's the only one who can be chosen.
					$target_list = array($target);
					break;
				}
			}
		}

		// The fail exception means to break processing with the picker.  As the enterprise pickers
		// are shared among collections, the current_target value needs to be nulled or caching
		// will pick up that target and run it again
		try
		{
			return parent::pickTarget($data, $state_data, $target_list);
		}
		catch (OLPBlackbox_FailException $e)
		{
			$this->current_target = NULL;
			throw $e;
		}
	}

	/**
	 * Get the base target name for a property short
	 *
	 * @param string $property_short
	 * @return string
	 */
	protected function getBaseTarget($property_short)
	{
		return EnterpriseData::resolveAlias($property_short);
	}
}

?>