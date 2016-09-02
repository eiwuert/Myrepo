<?php

/**
 * Decides if the application being processed is a react for the vetting system.
 * 
 * Created for gforge 9922.
 * 
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 */
class OLPBlackbox_Vetting_Rule_IsReact extends OLPBlackbox_Rule
{
	/**
	 * Determine if the application is a potential react for a company.
	 *
	 * @param Blackbox_Data $data Application being processed.
	 * @param Blackbox_IStateData $state_data Info about calling ITarget.
	 * 
	 * @return bool Whether the rule succeeds or fails.
	 */
	protected function runRule(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		// TODO: Determine how this rule will work.
		$is_react = FALSE;
		if ($is_react)
		{
			/*
			 * spec for gforge 9922 demands that if the vendor running this rule
			 * is sold to and this rule has determined that the app is a react
			 * then a stat called 'vetting_react_sold' is hit, which must be 
			 * done outside blackbox (after the post). 
			 */
			$state_data->is_vetting_react = TRUE;
		}
		return FALSE;
	}
	
}

?>
