<?php
/**
 * OLPBlackbox_Rule_ReferencesCount class file.
 *
 * @author Tym Feindel <timothy.feindel@sellingsource.com>
 */

/**
 * Checks to see if the target has rules regarding the minimum number of 
 * references required (currently 0,1,2) and whether the submitted lead
 * supplies the required number of references
 * 
 * NOTE: checks only for 0-2 required references
 *
 * @author Tym Feindel <timothy.feindel@sellingsource.com>
 */
class OLPBlackbox_Rule_ReferencesCount extends OLPBlackbox_Rule implements OLPBlackbox_Factory_Legacy_IReusableRule
{
	
	/**
	 * Returns whether the rule has sufficient data to run
	 * If the rule can't be run, onSkip() will be called
	 *
	 * @param Blackbox_Data $data the data the rule is running against
	 * @param Blackbox_IStateData $state_data information about the state of the Blackbox_ITarget which desires to run the rule.
	 *
	 * @return bool
	 */
	protected function canRun(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		return TRUE;  // as per Matt Piper, this should always be run
	}
	
	/**
	 * Runs the ReferencesCount rule.  Structure derived from MinimumAge rule.
	 *
	 * @param Blackbox_Data $data Data to run validation checks on
	 * @param Blackbox_IStateData $state_data an IStateData object which contains the caller's (Blackbox_ITarget) state.
	 * 
	 * @return bool
	 */	
	public function runRule(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		
		// If no required references are set (or set to 0), then return true.
		if (!$sum_references_required = $this->getRuleValue())
		{
			return TRUE;
		}
		
		// Make sure we are trying to work with a valid number.
		if (!is_numeric($sum_references_required))
		{
			throw new Blackbox_Exception(
				'Invalid Number of References Required in References Count'
			);
		}
		
		$compare_array=array();
		
		// Take the reference field(s) data and break it out into the chunks of data we need
		
		if ($sum_references_required >= 1)
		{
			// If one or more references are required, the first reference must be populated:
			$compare_array['ref_01_name_full'] = 2;
			$compare_array['ref_01_phone_home'] = 10;
			$compare_array['ref_01_relationship'] = 2;
		}
		
		if ($sum_references_required >= 2)
		{
			// If two references are required, (at least) the second reference must also be populated:
			$compare_array['ref_02_name_full'] = 2;
			$compare_array['ref_02_phone_home'] = 10;
			$compare_array['ref_02_relationship'] = 2;
		}
		
		foreach ($compare_array as $field_name => $length)
		{
			
			if (!isset($data->{$field_name})) return FALSE;
			if (strlen($data->{$field_name}) < $length) return FALSE;
		}
		
		return TRUE;
	}
	
}

?>
