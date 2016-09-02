<?php
/**
 * VendorAPI_Blackbox_Rule_ReferencesCount class file.
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
class VendorAPI_Blackbox_Rule_ReferencesCount extends VendorAPI_Blackbox_Rule
{
	/**
	 * Always run this rule regardless of missing a rule value
	 *
	 * @param Blackbox_Data $data the data the rule is running against
	 * @param Blackbox_IStateData $state_data information about the state of the Blackbox_ITarget which desires to run the rule.
	 *
	 * @return bool
	 */
	protected function canRun(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		return TRUE;
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
		
		// If personal references don't exist, return false
		if (!isset($data->personal_reference) || !is_array($data->personal_reference))
		{
			return FALSE;
		}
		
		// Set the length validation parameters for each personal reference field
		$compare_array=array();
		for ($i = 0; $i < $sum_references_required; $i++)
		{
			$compare_array[$i] = array();
			$compare_array[$i]['name_full'] = 2;
			$compare_array[$i]['phone_home'] = 10;
			$compare_array[$i]['relationship'] = 2;
		}
		
		foreach ($compare_array as $i => $personal_reference)
		{
			foreach ($personal_reference as $field_name => $length)
			{
				if (!isset($data->personal_reference[$i][$field_name])) return FALSE;
				if (strlen($data->personal_reference[$i][$field_name]) < $length) return FALSE;
			}
		}
		
		return TRUE;
	}
	
}

?>
